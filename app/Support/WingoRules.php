<?php

namespace App\Support;

final class WingoRules
{
    public const FEE_RATE = 0.02; // 2%
// public const FEE_RATE = 0.20; // 20%
    public static function durationSeconds(string $gameType): int
    {
        return match ($gameType) {
            '30s' => 30,
            '1m'  => 60,
            '3m'  => 180,
            '5m'  => 300,
            default => throw new \InvalidArgumentException("Invalid gameType: {$gameType}"),
        };
    }

    /** @return array{0:string,1?:string} */
    public static function numberToColors(int $n): array
    {
        // 0 => red + violet, 5 => green + violet
        if ($n === 0) return ['red', 'violet'];
        if ($n === 5) return ['green', 'violet'];
        if (in_array($n, [1,3,7,9], true)) return ['green'];
        return ['red']; // 2,4,6,8
    }

    public static function numberToBigSmall(int $n): string
    {
        return $n >= 5 ? 'big' : 'small';
    }

    /**
     * Mirrors your Vue calcPayout(): payout is based on "contract amount" = amount - fee.
     *
     * $betType: color|number|bigsmall
     * $betValue: green|red|violet OR 0..9 OR big|small
     */
    public static function calcPayout(string $betType, string $betValue, int $resultNumber, float $contractAmount): float
    {
        $n = $resultNumber;
        $isBig = $n >= 5;
        $isSmall = $n <= 4;

        $colors = self::numberToColors($n);
        $isGreen = in_array('green', $colors, true);
        $isRed = in_array('red', $colors, true);
        $isViolet = in_array('violet', $colors, true);

        if ($betType === 'color') {
            if ($betValue === 'green') {
                if ($isGreen) return $contractAmount * ($n === 5 ? 1.5 : 2.0);
                return 0.0;
            }
            if ($betValue === 'red') {
                if ($isRed) return $contractAmount * ($n === 0 ? 1.5 : 2.0);
                return 0.0;
            }
            if ($betValue === 'violet') {
                return $isViolet ? $contractAmount * 4.5 : 0.0;
            }
        }

        if ($betType === 'bigsmall') {
            if ($betValue === 'big') return $isBig ? $contractAmount * 2.0 : 0.0;
            if ($betValue === 'small') return $isSmall ? $contractAmount * 2.0 : 0.0;
        }

        if ($betType === 'number') {
            return ((int)$betValue === $n) ? $contractAmount * 9.0 : 0.0;
        }

        return 0.0;
    }
}
