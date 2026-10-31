<?php
declare(strict_types=1);

namespace Turysta\Game;

/** Wzory ekonomii. Parametry siedza w skill_definitions; po poziomie 50 koszt i czas rosna kwadratowo (koszt proporcjonalny do czasu, wiec pieniadze nie sa scianka). */
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
        return (int) floor($c50 * (($level / self::FLAT_FROM) ** 2));
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

    /** Punkty to ranking i prestiz, nie waluta. Rok gry to rzad wielkosci kilkudziesieciu tysiecy. */
    public const POINTS_TRAINING_SECONDS = 1200;   // 1 punkt za kazde rozpoczete 20 minut szkolenia
    public const POINTS_CHAPTER_PER_WORLD = 50;    // zwykly awans: 50 x numer swiata
    public const POINTS_WORLD_TRANSITION = 1000;   // wejscie do nowego swiata
    public const POINTS_JOB_CENTS = 5000;          // 1 punkt za kazde pelne 50 dukatow z pracy

    /**
     * Codzienna nagroda za odebranie: dni serii 1-7, kazdy kolejny wart wiecej; po 7. dniu seria zaczyna sie od 1.
     * Kolejnosc: [dukaty w setnych, punkty].
     */
    public const DAILY_REWARDS = [
        1 => [20, 1],
        2 => [30, 2],
        3 => [40, 3],
        4 => [50, 4],
        5 => [70, 5],
        6 => [100, 7],
        7 => [200, 10],
    ];

    /**
     * Dukaty z codziennej nagrody rosna z postepem gracza: tygodniowa suma to 30% kosztu zwyklego awansu w jego biezacym rozdziale
     * (dla dalszych swiatow dziesiatki, a potem setki dukatow), ale nigdy mniej niz suma z DAILY_REWARDS (poczatek gry).
     * Dzien serii dostaje swoj udzial z DAILY_REWARDS (dzien 7 ok. 39% tygodnia). Wynik zaokraglamy do 0,10 dk.
     */
    public const DAILY_COINS_SHARE_PERMILLE = 300;

    public static function dailyCoins(int $day, int $referenceCostCents): int
    {
        $total = array_sum(array_map(static fn(array $r): int => $r[0], self::DAILY_REWARDS));
        $weekly = max($total, intdiv(max(0, $referenceCostCents) * self::DAILY_COINS_SHARE_PERMILLE, 1000));
        $coins = intdiv($weekly * self::DAILY_REWARDS[$day][0], $total);
        return intdiv($coins + 5, 10) * 10;
    }

    /** Punkty za ukonczone szkolenie umiejetnosci: proporcjonalne do jego czasu, minimum 1. */
    public static function trainingPoints(int $durationSeconds): int
    {
        return max(1, intdiv(max(0, $durationSeconds) + self::POINTS_TRAINING_SECONDS - 1, self::POINTS_TRAINING_SECONDS));
    }

    public static function jobPoints(int $payoutCents): int
    {
        return intdiv($payoutCents, self::POINTS_JOB_CENTS);
    }

    /** Punkty za awans do rozdzialu o podanym numerze w swiecie (1-10); rozdzial 1 to wejscie do nowego swiata. */
    public static function chapterPoints(int $worldOrdinal, int $chapterOrdinal): int
    {
        return $chapterOrdinal === 1
            ? self::POINTS_WORLD_TRANSITION
            : self::POINTS_CHAPTER_PER_WORLD * $worldOrdinal;
    }
}
