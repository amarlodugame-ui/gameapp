<?php

namespace App\Services;

use App\Events\AviatorRoundState;
use App\Events\AviatorUserState;
use App\Models\AviatorBet;
use App\Models\AviatorRound;
use App\Models\AviatorSetting;
use App\Models\User;
use App\Support\AviatorMath;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
// use App\Jobs\Aviator\BroadcastAviatorUserState;

use App\Models\Transaction;


class AviatorRoundService
{
    public function refreshSettingsCache(): void
    {
        Cache::forget('aviator:settings');
    }

    protected function settings(): AviatorSetting
    {
        return Cache::remember('aviator:settings', 2, function () {
            return AviatorSetting::query()->firstOrCreate(
                ['id' => 1],
                [
                    'enabled' => true,
                    'betting_duration_ms' => 4000,
                    'result_duration_ms' => 4000,
                    'slow_factor' => 1.45,
                    'max_bet_amount' => 100000,
                    'max_payout_amount' => 5000000,
                    'house_edge_bps' => 100,
                    'max_crash_multiplier' => 1000.00,
                    'sound_enabled_by_default' => false,
                    'public_heartbeat_ms' => 250,
                    'engine_sleep_ms' => 100,
                ]
            );
        });
    }

    public function kickEngine(): void
    {
        $this->ensureBooted();
    }
protected function ensureUserHasSuccessfulDeposit(User $user): void
{
    $isDeposit = \App\Models\Deposit::query()
        ->where('user_id', $user->id)
        ->where('status', 1)
        ->exists();

    if (!$isDeposit) {
        throw ValidationException::withMessages([
            'amount' => 'Please make a deposit before placing bets.',
        ]);
    }
}
    public function ensureBooted(): void
    {
        Cache::lock('aviator:ensure-booted', 5)->get(function () {
            DB::transaction(function () {
                $settings = $this->settings();
                if (!$settings->enabled) {
                    return;
                }

                $round = $this->currentRoundForUpdate();
                if (!$round) {
                    $this->createNextRoundSafely(null);
                }
            }, 3);
        });
    }

    public function engineSleepMs(): int
    {
        return max(50, min(500, (int) ($this->settings()->engine_sleep_ms ?? 100)));
    }

    public function publicHeartbeatMs(): int
    {
        return max(100, min(1000, (int) ($this->settings()->public_heartbeat_ms ?? 250)));
    }



protected function shouldBroadcastPublicForPhase(AviatorRound $round, string $phase): bool
{
    $cacheKey = 'aviator:last-public-broadcast:' . $round->id . ':' . $phase;

    $nowMs = (int) floor(microtime(true) * 1000);
    $lastMs = (int) Cache::get($cacheKey, 0);

    $minIntervalMs = match ($phase) {
        'betting' => 250,
        'running' => 100,
        'crashed' => 100,
        default => 250,
    };

    if (($nowMs - $lastMs) < $minIntervalMs) {
        return false;
    }

    Cache::put($cacheKey, $nowMs, 15);

    return true;
}
public function tick(bool $forcePublicHeartbeat = false): void
{
    Cache::lock('aviator:engine-tick', 2)->get(function () use ($forcePublicHeartbeat) {
        $broadcastPublic = false;
        $broadcastUsers = [];
        $broadcastRound = null;
        $now = now();
            // logger()->info('AVIATOR TICK', [
            //     'now' => $this->isoMs($now),
            //     'forcePublicHeartbeat' => $forcePublicHeartbeat,
            // ]);
        DB::transaction(function () use (&$broadcastPublic, &$broadcastUsers, &$broadcastRound, $forcePublicHeartbeat, $now) {
            $settings = $this->settings();

            $this->finalizeExpiredRounds($now);

            if (!$settings->enabled) {
                return;
            }

            $round = $this->currentRoundForUpdate();

            if (!$round) {
                $round = $this->createNextRoundSafely(null);
                $broadcastPublic = true;
                $broadcastRound = $round;
                return;
            }

            $phase = AviatorMath::phase($round, $now);
            $broadcastRound = $round;

            if ($phase === 'betting') {
                if ($round->status !== 'betting') {
                    $round->status = 'betting';
                    $round->updated_at = $now;
                    $round->save();
                }


                        // logger()->info('AVIATOR BETTING BROADCAST', [
                        //     'round_id' => $round->id,
                        //     'round_no' => $round->round_no,
                        //     'now' => $this->isoMs($now),
                        //     'betting_starts_at' => $this->isoMs($round->betting_starts_at),
                        //     'flying_starts_at' => $this->isoMs($round->flying_starts_at),
                        // ]);


                $activatedUserIds = $this->activateQueuedBetsForRound($round, $now);
                $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $activatedUserIds)));

                if ($this->shouldBroadcastPublicForPhase($round, 'betting')) {
                    $broadcastPublic = true;
                    $broadcastRound = $round;
                }

                return;
            }

            if ($phase === 'running') {
                if ($round->status !== 'running') {
                    $round->status = 'running';
                    $round->updated_at = $now;
                    $round->save();
                    $broadcastPublic = true;
                    $broadcastRound = $round;
                }

                $this->findOrCreateNextRoundAfter($round);

                $currentMultiplier = AviatorMath::multiplier($round, $now);
                $autoCashoutUserIds = $this->settleAutoCashoutsUpTo($round, $currentMultiplier, $now);
                $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $autoCashoutUserIds)));

                if ($forcePublicHeartbeat && $this->shouldBroadcastPublicForPhase($round, 'running')) {
                    $broadcastPublic = true;
                    $broadcastRound = $round;
                }

                return;
            }

            if ($phase === 'crashed') {
                if ($round->status !== 'crashed') {
                    $affectedUserIds = $this->settleCrash($round, $now);

                    $round->status = 'crashed';
                    $round->updated_at = $now;
                    $round->save();

                    $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $affectedUserIds)));
                    $broadcastPublic = true;
                    $broadcastRound = $round;
                }

                $this->findOrCreateNextRoundAfter($round);

                return;
            }

            $next = $this->findOrCreateNextRoundAfter($round);
            $broadcastPublic = true;
            $broadcastRound = $next;
        }, 3);

        foreach (array_values(array_unique($broadcastUsers)) as $userId) {
            $this->broadcastUserState((int) $userId);
        }

        if ($broadcastPublic) {
            $this->broadcastPublicRoundState($broadcastRound, $now);
        }
    });
}

    // public function tick(bool $forcePublicHeartbeat = false): void
    // {
    //     Cache::lock('aviator:engine-tick', 2)->get(function () use ($forcePublicHeartbeat) {
    //         $broadcastPublic = false;
    //         $broadcastUsers = [];
    //         $broadcastRound = null;
    //         $now = now();


    //         logger()->info('AVIATOR TICK', [
    //             'now' => $this->isoMs($now),
    //             'forcePublicHeartbeat' => $forcePublicHeartbeat,
    //         ]);

    //         DB::transaction(function () use (&$broadcastPublic, &$broadcastUsers, &$broadcastRound, $forcePublicHeartbeat, $now) {
    //             $settings = $this->settings();

    //             $this->finalizeExpiredRounds($now);

    //             if (!$settings->enabled) {
    //                 return;
    //             }

    //             $round = $this->currentRoundForUpdate();

    //             if (!$round) {
    //                 $round = $this->createNextRoundSafely(null);
    //                 $broadcastPublic = true;
    //                 $broadcastRound = $round;
    //                 return;
    //             }

    //             $phase = AviatorMath::phase($round, $now);
    //             $broadcastRound = $round;
    //                 if ($phase === 'betting') {
    //                     if ($round->status !== 'betting') {
    //                         $round->status = 'betting';
    //                         $round->updated_at = $now;
    //                         $round->save();
    //                     }

    //                     $activatedUserIds = $this->activateQueuedBetsForRound($round, $now);
    //                     $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $activatedUserIds)));

    //                     $broadcastPublic = true;
    //                     $broadcastRound = $round;

    //                     logger()->info('AVIATOR BETTING BROADCAST', [
    //                         'round_id' => $round->id,
    //                         'round_no' => $round->round_no,
    //                         'now' => $this->isoMs($now),
    //                         'betting_starts_at' => $this->isoMs($round->betting_starts_at),
    //                         'flying_starts_at' => $this->isoMs($round->flying_starts_at),
    //                     ]);

    //                     return;
    //                 }

    //             if ($phase === 'running') {
    //                 if ($round->status !== 'running') {
    //                     $round->status = 'running';
    //                     $round->updated_at = $now;
    //                     $round->save();
    //                     $broadcastPublic = true;
    //                 }

    //                 $this->findOrCreateNextRoundAfter($round);

    //                 $currentMultiplier = AviatorMath::multiplier($round, $now);
    //                 $autoCashoutUserIds = $this->settleAutoCashoutsUpTo($round, $currentMultiplier, $now);
    //                 $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $autoCashoutUserIds)));

    //                 if ($forcePublicHeartbeat) {
    //                     $broadcastPublic = true;
    //                 }

    //                 return;
    //             }

    //             if ($phase === 'crashed') {
    //                 if ($round->status !== 'crashed') {
    //                     $affectedUserIds = $this->settleCrash($round, $now);

    //                     $round->status = 'crashed';
    //                     $round->updated_at = $now;
    //                     $round->save();

    //                     $broadcastUsers = array_values(array_unique(array_merge($broadcastUsers, $affectedUserIds)));
    //                     $broadcastPublic = true;
    //                 }

    //                 $this->findOrCreateNextRoundAfter($round);

    //                 return;
    //             }

    //             // If we get here, the old round is completed / expired.
    //             $next = $this->findOrCreateNextRoundAfter($round);
    //             $broadcastPublic = true;
    //             $broadcastRound = $next;
    //         }, 3);

    //         foreach (array_values(array_unique($broadcastUsers)) as $userId) {
    //             $this->broadcastUserState((int) $userId);
    //         }

    //         if ($broadcastPublic) {
    //             $this->broadcastPublicRoundState($broadcastRound, $now);
    //         }
    //     });
    // }

    protected function finalizeExpiredRounds($now): void
    {
        $expired = AviatorRound::query()
            ->where('status', '!=', 'completed')
            ->where('finished_at', '<=', $now)
            ->lockForUpdate()
            ->get();

        foreach ($expired as $round) {
            $round->status = 'completed';
            $round->settled_at ??= $now;
            $round->updated_at = $now;
            $round->save();
        }
    }

    protected function currentRoundForUpdate(): ?AviatorRound
    {
        return AviatorRound::query()
            ->where('finished_at', '>', now())
            ->orderBy('betting_starts_at')
            ->lockForUpdate()
            ->first();
    }

    public function currentRound(): ?AviatorRound
    {
        return AviatorRound::query()
            ->where('finished_at', '>', now())
            ->orderBy('betting_starts_at')
            ->first();
    }

    protected function resolveNextCrashMultiplier(AviatorSetting $settings): float
    {
        if (
            app()->environment(['local', 'testing', 'staging']) &&
            $settings->manual_next_crash_multiplier !== null
        ) {
            $value = round((float) $settings->manual_next_crash_multiplier, 2);

            AviatorSetting::query()
                ->whereKey($settings->id)
                ->update([
                    'manual_next_crash_multiplier' => null,
                    'updated_at' => now(),
                ]);

            $this->refreshSettingsCache();

            return max(1.01, min((float) ($settings->max_crash_multiplier ?? 1000.0), $value));
        }

        return $this->generateCrashMultiplier(houseEdgeBps: (int) $settings->house_edge_bps,maxCrashMultiplier: (float) ($settings->max_crash_multiplier ?? 1000.0));
    }

    protected function createNextRoundSafely(?AviatorRound $lastRound = null): AviatorRound
    {
        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($lastRound) {
                    $base = $lastRound
                        ? AviatorRound::query()
                            ->whereKey($lastRound->id)
                            ->lockForUpdate()
                            ->first()
                        : AviatorRound::query()
                            ->orderByDesc('round_no')
                            ->lockForUpdate()
                            ->first();

                    $nextRoundNo = $base ? ((int) $base->round_no + 1) : 1;

                    $existing = AviatorRound::query()
                        ->where('round_no', $nextRoundNo)
                        ->first();

                    if ($existing) {
                        return $existing;
                    }

                    return $this->createNextRoundFromBase($base, $nextRoundNo);
                }, 3);
            } catch (QueryException $e) {
                $attempts++;

                $message = $e->getMessage();
                $baseRoundNo = $lastRound ? (int) $lastRound->round_no : null;
                $expectedRoundNo = $baseRoundNo ? ($baseRoundNo + 1) : null;

                if (
                    str_contains($message, 'aviator_rounds_round_no_unique') ||
                    str_contains($message, 'Duplicate entry')
                ) {
                    if ($expectedRoundNo !== null) {
                        $existing = AviatorRound::query()
                            ->where('round_no', $expectedRoundNo)
                            ->first();

                        if ($existing) {
                            return $existing;
                        }
                    }

                    $latest = AviatorRound::query()->orderByDesc('round_no')->first();

                    if ($latest && $expectedRoundNo !== null && (int) $latest->round_no >= $expectedRoundNo) {
                        return $latest;
                    }
                }

                if ($attempts >= 5) {
                    throw $e;
                }

                usleep(100000);
            }
        }
    }

    protected function createNextRoundFromBase(?AviatorRound $lastRound, int $nextRoundNo): AviatorRound
    {
        $settings = $this->settings();

        $bettingDurationMs = (int) $settings->betting_duration_ms;
        $resultDurationMs = (int) $settings->result_duration_ms;
        $slowFactor = (float) ($settings->slow_factor ?? 1.45);

        $crashMultiplier = $this->resolveNextCrashMultiplier($settings);

        $now = now();

        $bettingStartsAt = $lastRound ? $lastRound->finished_at->copy() : $now;

        if ($bettingStartsAt->lt($now)) {
            $bettingStartsAt = $now->copy();
        }

        $flyingStartsAt = $bettingStartsAt->copy()->addMilliseconds($bettingDurationMs);

        $tmpRound = new AviatorRound([
            'flying_starts_at' => $flyingStartsAt,
            'crash_multiplier' => $crashMultiplier,
            'slow_factor' => $slowFactor,
        ]);

        $flightDurationMs = AviatorMath::timeToReachMultiplierMs($crashMultiplier, $tmpRound);

        $crashedAt = $flyingStartsAt->copy()->addMilliseconds($flightDurationMs);
        $finishedAt = $crashedAt->copy()->addMilliseconds($resultDurationMs);

        return AviatorRound::query()->create([
            'round_no' => $nextRoundNo,
            'status' => 'betting',
            'crash_multiplier' => $crashMultiplier,
            'betting_duration_ms' => $bettingDurationMs,
            'flight_duration_ms' => $flightDurationMs,
            'result_duration_ms' => $resultDurationMs,
            'slow_factor' => $slowFactor,
            'betting_starts_at' => $bettingStartsAt,
            'flying_starts_at' => $flyingStartsAt,
            'crashed_at' => $crashedAt,
            'finished_at' => $finishedAt,
        ]);
    }
    protected function generateCrashMultiplier(int $houseEdgeBps = 100, float $maxCrashMultiplier = 1000.0): float
    {
        $edge = max(0.0, min(0.20, $houseEdgeBps / 10000));
        $maxCrashMultiplier = max(1.01, min(1000.00, $maxCrashMultiplier));

        for ($i = 0; $i < 20; $i++) {
            $u = random_int(1, 1000000000000) / 1000000000000;
            $m = (1.0 - $edge) / max(1e-12, $u);

            if ($m >= 1.01 && $m <= $maxCrashMultiplier) {
                return round($m, 2);
            }
        }

        return round($maxCrashMultiplier, 2);
    }

    protected function findOrCreateNextRoundAfter(AviatorRound $round): AviatorRound
    {
        return Cache::lock('aviator:next-round-after:' . $round->id, 5)->block(2, function () use ($round) {
            $next = AviatorRound::query()
                ->where('betting_starts_at', '>=', $round->finished_at)
                ->orderBy('betting_starts_at')
                ->first();

            if ($next) {
                return $next;
            }

            return $this->createNextRoundSafely($round);
        });
    }

    protected function activateQueuedBetsForRound(AviatorRound $round, $now): array
    {
        $userIds = [];

        $queuedBets = AviatorBet::query()
            ->where('target_round_id', $round->id)
            ->where('status', 'queued')
            ->where('is_queued', true)
            ->lockForUpdate()
            ->get();

        foreach ($queuedBets as $bet) {
            $updated = AviatorBet::query()
                ->whereKey($bet->id)
                ->where('status', 'queued')
                ->update([
                    'round_id' => $round->id,
                    'status' => 'pending',
                    'is_queued' => false,
                    'updated_at' => $now,
                ]);

            if ($updated) {
                $userIds[] = (int) $bet->user_id;
            }
        }

        return array_values(array_unique($userIds));
    }

    protected function settleAutoCashoutsUpTo(AviatorRound $round, float $currentMultiplier, $now): array
    {
        $settings = $this->settings();
        $maxPayout = (float) ($settings->max_payout_amount ?? 0);

        $affected = [];

        $bets = AviatorBet::query()
            ->where('round_id', $round->id)
            ->where('status', 'pending')
            ->where('auto_cashout_enabled', true)
            ->whereNotNull('auto_cashout_multiplier')
            ->where('auto_cashout_multiplier', '<=', round($currentMultiplier, 2))
            ->lockForUpdate()
            ->get(['id', 'user_id', 'amount', 'auto_cashout_multiplier']);

        foreach ($bets as $bet) {
            $mult = round((float) $bet->auto_cashout_multiplier, 2);
            $payout = round(((float) $bet->amount) * $mult, 2);

            if ($maxPayout > 0 && $payout > $maxPayout) {
                $payout = round($maxPayout, 2);
                $mult = round($payout / max(0.01, (float) $bet->amount), 2);
            }

            $updated = AviatorBet::query()
                ->whereKey($bet->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cashed_out',
                    'cashout_multiplier' => $mult,
                    'cashout_amount' => $payout,
                    'cashed_out_at' => $now,
                    'settled_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($updated) {
                User::query()->whereKey($bet->user_id)->increment('balance', $payout);
                $affected[] = (int) $bet->user_id;
            }
        }

        return array_values(array_unique($affected));
    }

    protected function settleCrash(AviatorRound $round, $now): array
    {
        $settings = $this->settings();
        $maxPayout = (float) ($settings->max_payout_amount ?? 0);
        $crash = round((float) $round->crash_multiplier, 2);

        $affected = [];

        // Anything strictly below crash is still allowed to auto-cashout.
        $autoBets = AviatorBet::query()
            ->where('round_id', $round->id)
            ->where('status', 'pending')
            ->where('auto_cashout_enabled', true)
            ->whereNotNull('auto_cashout_multiplier')
            ->where('auto_cashout_multiplier', '<', $crash)
            ->lockForUpdate()
            ->get(['id', 'user_id', 'amount', 'auto_cashout_multiplier']);

        foreach ($autoBets as $bet) {
            $mult = round((float) $bet->auto_cashout_multiplier, 2);
            $payout = round(((float) $bet->amount) * $mult, 2);

            if ($maxPayout > 0 && $payout > $maxPayout) {
                $payout = round($maxPayout, 2);
                $mult = round($payout / max(0.01, (float) $bet->amount), 2);
            }

            $updated = AviatorBet::query()
                ->whereKey($bet->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cashed_out',
                    'cashout_multiplier' => $mult,
                    'cashout_amount' => $payout,
                    'cashed_out_at' => $now,
                    'settled_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($updated) {
                User::query()->whereKey($bet->user_id)->increment('balance', $payout);
                $affected[] = (int) $bet->user_id;
            }
        }

        $lostUserIds = AviatorBet::query()
            ->where('round_id', $round->id)
            ->where('status', 'pending')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        AviatorBet::query()
            ->where('round_id', $round->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'lost',
                'settled_at' => $now,
                'updated_at' => $now,
            ]);

        return array_values(array_unique(array_merge($affected, $lostUserIds)));
    }

    public function placeOrQueueBet(
        User $user,
        int $slot,
        float $amount,
        bool $autoCashoutEnabled = false,
        ?float $autoCashoutMultiplier = null
    ): array {
        $settings = $this->settings();
        $this->ensureUserHasSuccessfulDeposit($user);
        if (!$settings->enabled) {
            throw ValidationException::withMessages([
                'amount' => 'Game is disabled.',
            ]);
        }

        if (!in_array($slot, [1, 2], true)) {
            throw ValidationException::withMessages([
                'slot' => 'Invalid slot.',
            ]);
        }

        $amount = round(max(1, $amount), 2);

        $maxBet = (float) ($settings->max_bet_amount ?? 0);
        if ($maxBet > 0 && $amount > $maxBet) {
            throw ValidationException::withMessages([
                'amount' => "Max bet is {$maxBet}.",
            ]);
        }

        $round = $this->currentRound();

        if (!$round) {
            $this->ensureBooted();
            $round = $this->currentRound();
        }

        if (!$round) {
            throw ValidationException::withMessages([
                'amount' => 'Round is not available.',
            ]);
        }

        $phase = AviatorMath::phase($round);

        if ($phase === 'betting') {
            $bet = $this->placeBet(
                user: $user,
                slot: $slot,
                amount: $amount,
                autoCashoutEnabled: $autoCashoutEnabled,
                autoCashoutMultiplier: $autoCashoutMultiplier,
            );

            return [
                'mode' => 'placed',
                'bet_id' => $bet->id,
                'round_id' => $bet->round_id,
            ];
        }

        $nextRound = $this->findOrCreateNextRoundAfter($round);

        DB::transaction(function () use (
            $user,
            $slot,
            $amount,
            $autoCashoutEnabled,
            $autoCashoutMultiplier,
            $nextRound
        ) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedUser) {
                throw ValidationException::withMessages([
                    'amount' => 'User not found.',
                ]);
            }

            if ((float) $lockedUser->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient balance.',
                ]);
            }

            $existingQueued = AviatorBet::query()
                ->where('user_id', $user->id)
                ->where('target_round_id', $nextRound->id)
                ->where('slot', $slot)
                ->whereIn('status', ['queued', 'pending'])
                ->lockForUpdate()
                ->first();

            if ($existingQueued) {
                throw ValidationException::withMessages([
                    'slot' => 'This slot already has an active/queued bet for next round.',
                ]);
            }

            $finalAutoCashoutMultiplier = null;
            if ($autoCashoutEnabled) {
                $finalAutoCashoutMultiplier = round(
                    max(1.01, (float) $autoCashoutMultiplier),
                    2
                );
            }

            $lockedUser->decrement('balance', $amount);
            requiredTurnoverAmount($lockedUser, $amount, '-');

            AviatorBet::query()->create([
                'round_id' => $nextRound->id,
                'target_round_id' => $nextRound->id,
                'user_id' => $user->id,
                'is_demo' => $lockedUser->role === 'demo',
                'bet_key' => 'AVQ-' . $nextRound->round_no . '-' . $user->id . '-' . $slot . '-' . Str::upper(Str::random(6)),
                'slot' => $slot,
                'amount' => $amount,
                'status' => 'queued',
                'is_queued' => true,
                'auto_cashout_enabled' => $autoCashoutEnabled,
                'auto_cashout_multiplier' => $finalAutoCashoutMultiplier,
                'placed_at' => now(),
            ]);
        }, 3);

        $this->broadcastUserState((int) $user->id);

        return [
            'mode' => 'queued',
            'round_id' => $nextRound->id,
            'round_no' => $nextRound->round_no,
        ];
    }

    public function placeBet(
        User $user,
        int $slot,
        float $amount,
        bool $autoCashoutEnabled = false,
        ?float $autoCashoutMultiplier = null
    ): AviatorBet {
        $settings = $this->settings();
 $this->ensureUserHasSuccessfulDeposit($user);
        if (!$settings->enabled) {
            throw ValidationException::withMessages([
                'amount' => 'Game is disabled.',
            ]);
        }

        if (!in_array($slot, [1, 2], true)) {
            throw ValidationException::withMessages([
                'slot' => 'Invalid slot.',
            ]);
        }

        $amount = round(max(1, $amount), 2);

        $maxBet = (float) ($settings->max_bet_amount ?? 0);
        if ($maxBet > 0 && $amount > $maxBet) {
            throw ValidationException::withMessages([
                'amount' => "Max bet is {$maxBet}.",
            ]);
        }

        $round = $this->currentRound();

        if (!$round || AviatorMath::phase($round) !== 'betting') {
            throw ValidationException::withMessages([
                'amount' => 'Betting window closed.',
            ]);
        }

        $bet = DB::transaction(function () use (
            $user,
            $round,
            $slot,
            $amount,
            $autoCashoutEnabled,
            $autoCashoutMultiplier
        ) {
            $lockedRound = AviatorRound::query()
                ->whereKey($round->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedRound || AviatorMath::phase($lockedRound) !== 'betting') {
                throw ValidationException::withMessages([
                    'amount' => 'Betting window closed.',
                ]);
            }

            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedUser) {
                throw ValidationException::withMessages([
                    'amount' => 'User not found.',
                ]);
            }

            if ((float) $lockedUser->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient balance.',
                ]);
            }

            $existing = AviatorBet::query()
                ->where('round_id', $lockedRound->id)
                ->where('user_id', $lockedUser->id)
                ->where('slot', $slot)
                ->whereIn('status', ['pending', 'queued'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'slot' => 'This slot already has an active bet.',
                ]);
            }

            $finalAutoCashoutMultiplier = null;
            if ($autoCashoutEnabled) {
                $finalAutoCashoutMultiplier = round(
                    max(1.01, (float) $autoCashoutMultiplier),
                    2
                );
            }

            $lockedUser->decrement('balance', $amount);

            return AviatorBet::query()->create([
                'round_id' => $lockedRound->id,
                'target_round_id' => $lockedRound->id,
                'user_id' => $lockedUser->id,
                'is_demo' => $lockedUser->role === 'demo',
                'bet_key' => 'AV-' . $lockedRound->round_no . '-' . $lockedUser->id . '-' . $slot . '-' . Str::upper(Str::random(6)),
                'slot' => $slot,
                'amount' => $amount,
                'status' => 'pending',
                'is_queued' => false,
                'auto_cashout_enabled' => $autoCashoutEnabled,
                'auto_cashout_multiplier' => $finalAutoCashoutMultiplier,
                'placed_at' => now(),
            ]);
        }, 3);

        $this->broadcastUserState((int) $user->id);

        return $bet;
    }
// public function cancelBet(User $user, int $slot): void
// {
//     DB::transaction(function () use ($user, $slot) {
//         $bet = AviatorBet::query()
//             ->where('user_id', $user->id)
//             ->where('slot', $slot)
//             ->whereIn('status', ['queued', 'pending'])
//             ->orderByDesc('id')
//             ->lockForUpdate()
//             ->first();

//         if (!$bet) {
//             throw ValidationException::withMessages([
//                 'slot' => 'No pending or queued bet found for this slot.',
//             ]);
//         }

//         if ($bet->status === 'pending') {
//             $round = AviatorRound::query()
//                 ->whereKey($bet->round_id)
//                 ->lockForUpdate()
//                 ->first();

//             if (!$round || AviatorMath::phase($round) !== 'betting') {
//                 throw ValidationException::withMessages([
//                     'slot' => 'Bet can only be cancelled during betting phase.',
//                 ]);
//             }
//         }

//         $lockedUser = User::query()
//             ->whereKey($user->id)
//             ->lockForUpdate()
//             ->first();

//         if (!$lockedUser) {
//             throw ValidationException::withMessages([
//                 'slot' => 'User not found.',
//             ]);
//         }

//         $lockedUser->increment('balance', (float) $bet->amount);
//         requiredTurnoverAmount($lockedUser, (float) $bet->amount, '+');
//         $bet->update([
//             'status' => 'cancelled',
//             'is_queued' => false,
//             'target_round_id' => null,
//             'settled_at' => now(),
//             'updated_at' => now(),
//         ]);
//     }, 3);

//     BroadcastAviatorUserState::dispatch((int) $user->id)->afterCommit();
// }
    public function cancelBet(User $user, int $slot): void
    {
        DB::transaction(function () use ($user, $slot) {
            $bet = AviatorBet::query()
                ->where('user_id', $user->id)
                ->where('slot', $slot)
                ->whereIn('status', ['queued', 'pending'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$bet) {
                throw ValidationException::withMessages([
                    'slot' => 'No pending or queued bet found for this slot.',
                ]);
            }

            if ($bet->status === 'pending') {
                $round = AviatorRound::query()
                    ->whereKey($bet->round_id)
                    ->lockForUpdate()
                    ->first();

                if (!$round || AviatorMath::phase($round) !== 'betting') {
                    throw ValidationException::withMessages([
                        'slot' => 'Bet can only be cancelled during betting phase.',
                    ]);
                }
            }

            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (!$lockedUser) {
                throw ValidationException::withMessages([
                    'slot' => 'User not found.',
                ]);
            }

            $lockedUser->increment('balance', $bet->amount);
            requiredTurnoverAmount($lockedUser, (float) $bet->amount, '-');
            $bet->update([
                'status' => 'cancelled',
                'is_queued' => false,
                'target_round_id' => null,
                'settled_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        $this->broadcastUserState((int) $user->id);
    }

    public function cashOut(User $user, int $betId): void
    {
        $this->cashOutPendingBet($betId, null, (int) $user->id);
        $this->broadcastUserState((int) $user->id);
    }

    protected function cashOutPendingBet(int $betId, ?float $forceMultiplier = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($betId, $forceMultiplier, $userId) {
            $settings = $this->settings();
            $maxPayout = (float) ($settings->max_payout_amount ?? 0);

            $bet = AviatorBet::query()
                ->with('round')
                ->whereKey($betId)
                ->lockForUpdate()
                ->first();

            if (!$bet || $bet->status !== 'pending') {
                return;
            }

            if ($userId !== null && (int) $bet->user_id !== (int) $userId) {
                throw ValidationException::withMessages([
                    'bet_id' => 'Invalid bet.',
                ]);
            }

            $round = AviatorRound::query()
                ->whereKey($bet->round_id)
                ->lockForUpdate()
                ->first();

            if (!$round || AviatorMath::phase($round) !== 'running') {
                throw ValidationException::withMessages([
                    'bet_id' => 'Cash out not available now.',
                ]);
            }

            $multiplier = $forceMultiplier ?? AviatorMath::multiplier($round);
            $multiplier = round((float) $multiplier, 2);

            if ($multiplier >= (float) $round->crash_multiplier) {
                throw ValidationException::withMessages([
                    'bet_id' => 'Cash out not available now.',
                ]);
            }

            $cashoutAmount = round(((float) $bet->amount) * $multiplier, 2);

            if ($maxPayout > 0 && $cashoutAmount > $maxPayout) {
                $cashoutAmount = round($maxPayout, 2);
                $multiplier = round($cashoutAmount / max(0.01, (float) $bet->amount), 2);
            }

            $now = now();

            $bet->update([
                'status' => 'cashed_out',
                'cashout_multiplier' => $multiplier,
                'cashout_amount' => $cashoutAmount,
                'cashed_out_at' => $now,
                'settled_at' => $now,
            ]);

            $lockedUser = User::query()->whereKey($bet->user_id)->lockForUpdate()->first();
            $lockedUser?->increment('balance', $cashoutAmount);
            requiredTurnoverAmount($lockedUser, $cashoutAmount, '-');
        }, 3);
    }

    protected function isoMs(?CarbonInterface $value): ?string
    {
        return $value?->format('Y-m-d\TH:i:s.vP');
    }

public function buildState(User $user): array
{
    $round = $this->currentRound();
    $now = now();

    $myBets = AviatorBet::query()
        ->where('user_id', $user->id)
        ->whereIn('status', ['queued', 'pending', 'cashed_out', 'lost', 'cancelled'])
        ->orderByDesc('id')
        ->limit(20)
        ->get()
        ->map(function (AviatorBet $bet) {
            return [
                'id' => $bet->id,
                'round_id' => (int) $bet->round_id,
                'target_round_id' => $bet->target_round_id ? (int) $bet->target_round_id : null,
                'bet_key' => $bet->bet_key,
                'slot' => $bet->slot,
                'amount' => (float) $bet->amount,
                'status' => $bet->status,
                'is_queued' => (bool) ($bet->is_queued ?? false),
                'auto_cashout_enabled' => (bool) $bet->auto_cashout_enabled,
                'auto_cashout_multiplier' => $bet->auto_cashout_multiplier !== null ? (float) $bet->auto_cashout_multiplier : null,
                'cashout_multiplier' => $bet->cashout_multiplier !== null ? (float) $bet->cashout_multiplier : null,
                'cashout_amount' => $bet->cashout_amount !== null ? (float) $bet->cashout_amount : null,
            ];
        })
        ->values();

    if (!$round) {
        return [
            'server_time' => $this->isoMs($now),
            'balance' => (float) $user->fresh()->balance,
            'round' => null,
            'my_bets' => $myBets,
            'history' => $this->recentRoundHistory(),
        ];
    }

    $phase = AviatorMath::phase($round, $now);
    $multiplier = AviatorMath::multiplier($round, $now);

    return [
        'server_time' => $this->isoMs($now),
        'balance' => (float) $user->fresh()->balance,
        'round' => $this->buildPublicRoundPayload($round, $now, $phase, $multiplier),
        'my_bets' => $myBets,
        'history' => $this->recentRoundHistory(),
    ];
}
protected function recentRoundHistory(int $limit = 30): array
{
    return AviatorRound::query()
        ->whereNotNull('crash_multiplier')
        ->whereIn('status', ['crashed', 'completed'])
        ->orderByDesc('round_no')
        ->limit($limit)
        ->get(['id', 'round_no', 'crash_multiplier'])
        ->map(fn (AviatorRound $round) => [
            'id' => (int) $round->id,
            'round_no' => (int) $round->round_no,
            'crash_multiplier' => (float) $round->crash_multiplier,
        ])
        ->values()
        ->all();
}
    protected function buildPublicRoundPayload(AviatorRound $round, $now, string $phase, float $multiplier): array
    {
        $base = [
            'id' => (int) $round->id,
            'round_no' => (int) $round->round_no,
            'status' => $phase,
            'betting_starts_at' => $this->isoMs($round->betting_starts_at),
            'flying_starts_at' => $this->isoMs($round->flying_starts_at),
            'betting_duration_ms' => (int) $round->betting_duration_ms,
            'result_duration_ms' => (int) $round->result_duration_ms,
            'flight_duration_ms' => (int) $round->flight_duration_ms,
            'slow_factor' => (float) $round->slow_factor,
            'time_constant_ms' => (int) AviatorMath::timeConstantMs($round),
            'current_multiplier' => (float) round($multiplier, 2),
            'can_bet' => $phase === 'betting',
            'can_cashout' => $phase === 'running',
        ];

        if (in_array($phase, ['crashed', 'completed'], true)) {
            $base['crash_multiplier'] = (float) $round->crash_multiplier;
            $base['crashed_at'] = $this->isoMs($round->crashed_at);
            $base['finished_at'] = $this->isoMs($round->finished_at);
        } else {
            $base['crash_multiplier'] = null;
            $base['crashed_at'] = null;
            $base['finished_at'] = null;
        }

        return $base;
    }

protected function broadcastPublicRoundState(?AviatorRound $round = null, $now = null): void
{
    $now ??= now();

    $round ??= AviatorRound::query()
        ->where('finished_at', '>', $now)
        ->orderBy('betting_starts_at')
        ->first();

    $payload = [
        'server_time' => $this->isoMs($now),
        'round' => null,
        'history' => $this->recentRoundHistory(),
    ];

    if ($round) {
        $phase = AviatorMath::phase($round, $now);
        $multiplier = AviatorMath::multiplier($round, $now);
        $payload['round'] = $this->buildPublicRoundPayload($round, $now, $phase, $multiplier);
    }

    event(new AviatorRoundState($payload));
}

    public function broadcastUserState(int $userId): void
    {
        $user = User::query()->find($userId);

        if (!$user) {
            return;
        }

        event(new AviatorUserState(
            userId: (int) $user->id,
            payload: $this->buildState($user)
        ));
    }
}