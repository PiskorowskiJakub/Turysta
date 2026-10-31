<?php
declare(strict_types=1);

namespace Turysta\Game;

/** Wzory ekonomii. Parametry siedza w skill_definitions; po poziomie 50 krzywa jest splaszczona. */
final class Economy
{
    public const FLAT_FROM = 50;

    /** @param array<string,mixed> $skill wiersz skill_definitions */
    public static function cost(array $skill, int $level): int
    {
        $k0 = (int) $skill['base_cost_cents'];
        $g = (float) $skill['cost_growth'];
        if ($level <= self::FLAT_FROM) {
            return (int) floor($k0 * ($g ** ($level - 1)));
        }
        $c50 = floor($k0 * ($g ** (self::FLAT_FROM - 1)));
        return (int) floor($c50 * (($level / self::FLAT_FROM) ** 3));
    }

    /** @param array<string,mixed> $skill */
    public static function seconds(array $skill, int $level): int
    {
        $t0 = (int) $skill['base_seconds'];
        $g = (float) $skill['time_growth'];
        if ($level <= self::FLAT_FROM) {
            return max(1, (int) round($t0 * ($g ** ($level - 1))));
        }
        $t50 = $t0 * ($g ** (self::FLAT_FROM - 1));
        return max(1, (int) round($t50 * (($level / self::FLAT_FROM) ** 2)));
    }

    /** Maksymalny bonus Handlu w promilach (asymptota +120%) i poziom, przy ktorym daje polowe tego bonusu. */
    public const TRADE_BONUS_MAX_PERMILLE = 1200;
    public const TRADE_BONUS_HALF_LEVEL = 120;

    /** Bonus do wyplat w promilach: 1200 * L / (L + 120). Poziom 30 = +24%, 100 = +54,5%, 200 = +75%. */
    public static function tradeBonusPermille(int $tradeLevel): int
    {
        $l = max(0, $tradeLevel);
        return intdiv(self::TRADE_BONUS_MAX_PERMILLE * $l, $l + self::TRADE_BONUS_HALF_LEVEL);
    }

    /** Wyplata za prace po uwzglednieniu poziomu Handlu. */
    public static function boostedReward(int $baseCents, int $tradeLevel): int
    {
        return intdiv($baseCents * (1000 + self::tradeBonusPermille($tradeLevel)), 1000);
    }

    public static function trainingPoints(int $targetLevel): int
    {
        return 10 * $targetLevel;
    }

    public static function jobPoints(int $payoutCents): int
    {
        return intdiv($payoutCents, 1000); // 1 punkt za kazde pelne 10 monet
    }
}
