<?php

namespace App\Jobs;

use App\Models\GameRound;
use App\Support\WingoRules;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

final class EnsureWingoRunning implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** @var array<int,string> */
    private array $types = ['30s', '1m', '3m', '5m'];

    public function handle(): void
    {
        foreach ($this->types as $type) {
            DB::transaction(function () use ($type) {
                $running = GameRound::query()
                    ->where('game_type', $type)
                    ->where('status', 'running')
                    ->orderBy('start_time')
                    ->lockForUpdate()
                    ->first();

                if ($running) {
                    return;
                }
                $queued = GameRound::query()
                    ->where('game_type', $type)
                    ->where('status', 'queued')
                    ->orderBy('start_time')
                    ->lockForUpdate()
                    ->first();

                if ($queued) {
                    $queued->status = 'running';
                    $queued->save();

                    $delaySeconds = max(1, now()->diffInSeconds($queued->end_time, false));
                    SettleWingoRound::dispatch($queued->id)->delay(now()->addSeconds($delaySeconds));
                    return;
                }
                $start = now();
                $end = $start->copy()->addSeconds(WingoRules::durationSeconds($type));

                $running = GameRound::create([
                    'period_id' => $this->buildNextPeriodId($type, $start),
                    'game_type' => $type,
                    'start_time' => $start,
                    'end_time' => $end,
                    'result_number' => null,
                    'result_color' => null,
                    'result_bigsmall' => null,
                    'status' => 'running',
                ]);

                $delaySeconds = max(1, now()->diffInSeconds($running->end_time, false));
                SettleWingoRound::dispatch($running->id)->delay(now()->addSeconds($delaySeconds));
            });
        }
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