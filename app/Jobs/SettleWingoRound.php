<?php

namespace App\Jobs;

use App\Events\WingoRoundUpdated;
use App\Events\WingoUserUpdated;
use App\Models\Bet;
use App\Models\GameControl;
use App\Models\GameRound;
use App\Support\WingoRules;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SettleWingoRound implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, Queueable;

    public int $uniqueFor = 3600;

    private int $queueBuffer = 10;

    public function __construct(public int $roundId)
    {
    }

    public function uniqueId(): string
    {
        return 'settle-wingo-round-' . $this->roundId;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $round = GameRound::query()
                ->lockForUpdate()
                ->findOrFail($this->roundId);

            if ($round->status === 'ended') {
                return;
            }

            $resultNumber = $this->pickResultNumber($round);

            $round->result_number = $resultNumber;

            $colors = WingoRules::numberToColors($resultNumber);
            $round->result_color = in_array('violet', $colors, true) ? 'violet' : $colors[0];
            $round->result_bigsmall = WingoRules::numberToBigSmall($resultNumber);
            $round->status = 'ended';
            $round->save();

            $bets = Bet::query()
                ->where('round_id', $round->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            foreach ($bets as $bet) {
                $fee = (float) $bet->amount * WingoRules::FEE_RATE;
                $contract = (float) $bet->amount - $fee;

                $payout = WingoRules::calcPayout(
                    $bet->bet_type,
                    $bet->bet_value,
                    $resultNumber,
                    $contract
                );

                $bet->payout = $payout;
                $bet->status = $payout > 0 ? 'win' : 'lose';
                $bet->save();

                if ($payout > 0) {
                    $bet->user()->lockForUpdate()->increment('balance', $payout);
                    requiredTurnoverAmount($bet->user, $bet->amount, '-');
                }

                try {
                    event(new WingoUserUpdated($bet->user_id, [
                        'type' => 'bet_settled',
                        'bet_id' => $bet->id,
                        'round_id' => $round->id,
                        'period_id' => $round->period_id,
                        'status' => $bet->status,
                        'payout' => (float) $bet->payout,
                    ]));
                } catch (\Throwable $e) {
                    Log::error('Broadcast failed', ['error' => $e->getMessage()]);
                }
            }

            try {
                event(new WingoRoundUpdated($round->game_type, [
                    'type' => 'round_ended',
                    'round' => [
                        'id' => $round->id,
                        'period_id' => $round->period_id,
                        'game_type' => $round->game_type,
                        'start_time' => $round->start_time?->toISOString(),
                        'end_time' => $round->end_time?->toISOString(),
                        'result_number' => $round->result_number,
                        'colors' => WingoRules::numberToColors($round->result_number),
                        'bigsmall' => WingoRules::numberToBigSmall($round->result_number),
                    ],
                    'server_time' => now()->toISOString(),
                ]));
            } catch (\Throwable $e) {
                Log::error('Broadcast failed 2', ['error' => $e->getMessage()]);
            }

            // first queued -> running
            $next = GameRound::query()
                ->where('game_type', $round->game_type)
                ->where('status', 'queued')
                ->orderBy('start_time')
                ->lockForUpdate()
                ->first();

            if ($next) {
                $next->status = 'running';
                $next->save();
            } else {
                $start = now();

                if ($round->end_time && $round->end_time->greaterThan(now())) {
                    $start = $round->end_time->copy();
                }

                $next = $this->createRoundAt($round->game_type, $start, 'running');
            }

            // keep queue buffer full
            $queuedCount = GameRound::query()
                ->where('game_type', $round->game_type)
                ->where('status', 'queued')
                ->count();

            $last = GameRound::query()
                ->where('game_type', $round->game_type)
                ->orderByDesc('end_time')
                ->lockForUpdate()
                ->first();

            try {
                event(new WingoRoundUpdated($next->game_type, [
                    'type' => 'round_started',
                    'round' => [
                        'id' => $next->id,
                        'period_id' => $next->period_id,
                        'game_type' => $next->game_type,
                        'start_time' => $next->start_time?->toISOString(),
                        'end_time' => $next->end_time?->toISOString(),
                    ],
                    'server_time' => now()->toISOString(),
                ]));
            } catch (\Throwable $e) {
                Log::error('Broadcast failed 3', ['error' => $e->getMessage()]);
            }

            $delaySeconds = max(1, now()->diffInSeconds($next->end_time, false));
            self::dispatch($next->id)->delay(now()->addSeconds($delaySeconds));
        });
    }

    /**
     * Priority:
     * 1) queued/running round preset result_number
     * 2) control mode = profit_control/manual/random
     */
    private function pickResultNumber(GameRound $round): int
    {
        if ($round->result_number !== null) {
            return (int) $round->result_number;
        }

        $control = GameControl::query()
            ->where('game_type', $round->game_type)
            ->first();

        if (!$control) {
            return random_int(0, 9);
        }

        return match ($control->mode) {
            'profit_control' => $this->pickProfitControlled($round),
            'manual' => $this->pickManual($control),
            default => random_int(0, 9),
        };
    }

    private function pickManual(GameControl $control): int
    {
        $n = (int) ($control->manual_number ?? -1);

        if ($n >= 0 && $n <= 9) {
            return $n;
        }

        return random_int(0, 9);
    }

    private function pickProfitControlled(GameRound $round): int
    {
        $bets = Bet::query()
            ->where('round_id', $round->id)
            ->where('status', 'pending')
            ->with(['user:id,role'])
            ->get();

        $realBets = $bets->filter(fn ($b) => ($b->user?->role ?? 'user') === 'user');

        if ($realBets->isEmpty()) {
            return random_int(0, 9);
        }

        $feeRate = WingoRules::FEE_RATE;
        $bestN = 0;
        $minTotalPayout = INF;
        $candidates = [];

        for ($n = 0; $n <= 9; $n++) {
            $totalPayout = 0.0;

            foreach ($realBets as $bet) {
                $amount = (float) $bet->amount;
                $contract = $amount - ($amount * $feeRate);

                $totalPayout += WingoRules::calcPayout(
                    $bet->bet_type,
                    (string) $bet->bet_value,
                    $n,
                    $contract
                );
            }

            if ($totalPayout < $minTotalPayout - 0.000001) {
                $minTotalPayout = $totalPayout;
                $bestN = $n;
                $candidates = [$n];
            } elseif (abs($totalPayout - $minTotalPayout) <= 0.000001) {
                $candidates[] = $n;
            }
        }

        if (count($candidates) > 1) {
            return $candidates[array_rand($candidates)];
        }

        return $bestN;
    }

    private function createRoundAt(string $type, CarbonInterface $start, string $status): GameRound
    {
        $end = $start->copy()->addSeconds(WingoRules::durationSeconds($type));

        return GameRound::create([
            'period_id' => $this->buildNextPeriodId($type, $start),
            'game_type' => $type,
            'start_time' => $start,
            'end_time' => $end,
            'result_number' => null,
            'result_color' => null,
            'result_bigsmall' => null,
            'status' => $status,
        ]);
    }

    private function buildNextPeriodId(string $gameType, CarbonInterface $start): string
    {
        $date = $start->format('Ymd');
        $typeCode = $this->gameTypeCode($gameType);

        $latest = GameRound::query()
            ->where('game_type', $gameType)
            ->where('period_id', 'like', $date . $typeCode . '%')
            ->orderByDesc('period_id')
            ->lockForUpdate()
            ->first();

        $prevSeq = 0;

        if ($latest && preg_match('/^\d{8}\d{2}(\d{6})$/', (string) $latest->period_id, $m)) {
            $prevSeq = (int) $m[1];
        }

        $nextSeq = $prevSeq + 1;

        return sprintf('%s%s%06d', $date, $typeCode, $nextSeq);
    }

    private function gameTypeCode(string $gameType): string
    {
        return match ($gameType) {
            '30s' => '01',
            '1m'  => '02',
            '3m'  => '03',
            '5m'  => '04',
            default => '99',
        };
    }
}