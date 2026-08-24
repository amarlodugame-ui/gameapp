<?php

namespace App\Support;

use App\Models\AviatorRound;
use Carbon\CarbonInterface;

class AviatorMath
{
    // Base exponential constant; your slow_factor scales this.
    public const DEFAULT_GROWTH_TIME_CONSTANT_MS = 5500;

    public static function timeConstantMs(AviatorRound $round): int
    {
        $slow = (float) ($round->slow_factor ?? 1.0);
        $slow = max(0.2, min(10.0, $slow));

        return max(1, (int) round(self::DEFAULT_GROWTH_TIME_CONSTANT_MS * $slow));
    }

    public static function timeToReachMultiplierMs(float $multiplier, AviatorRound $round): int
    {
        $m = max(1.0, (float) $multiplier);
        return (int) round(log($m) * self::timeConstantMs($round));
    }

    public static function phase(AviatorRound $round, ?CarbonInterface $now = null): string
    {
        $now ??= now();

        if ($now->lt($round->flying_starts_at)) {
            return 'betting';
        }
        if ($now->lt($round->crashed_at)) {
            return 'running';
        }
        if ($now->lt($round->finished_at)) {
            return 'crashed';
        }
        return 'completed';
    }

    public static function multiplier(AviatorRound $round, ?CarbonInterface $now = null): float
    {
        $now ??= now();

        if ($now->lt($round->flying_starts_at)) {
            return 1.00;
        }

        if ($now->gte($round->crashed_at)) {
            return (float) $round->crash_multiplier;
        }

        $elapsedMs = $round->flying_starts_at->diffInMilliseconds($now);
        $tc = self::timeConstantMs($round);

        $raw = exp($elapsedMs / max(1, $tc));

        // Safety cap:
        $value = min($raw, (float) $round->crash_multiplier);
        return (float) number_format($value, 2, '.', '');
    }
}
