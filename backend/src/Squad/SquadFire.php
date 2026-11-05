<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;
use PDO;
use Turysta\Game\Clock;

/**
 * Ognisko oddzialu: pali sie, gdy danego dnia gry aktywnych jest co najmniej 30% czlonkow (min. 2 osoby).
 * Kazdy taki dzien daje zar na schronisko. Dni rozliczamy po polnocy (Europe/Warsaw), na podstawie user_active_days.
 */
final class SquadFire
{
    /** Maksymalna liczba zaleglych dni rozliczanych dla jednego oddzialu w jednym przebiegu. */
    private const CATCH_UP_DAYS = 14;

    /** Poziom plomienia (0-4) dla serii dni. */
    public static function level(int $streak): int
    {
        $level = 0;
        foreach (SquadRules::FLAME_STREAKS as $i => $min) {
            if ($streak >= $min) {
                $level = $i;
            }
        }
        return $level;
    }

    /** Potrzebna liczba aktywnych: 30% skladu w gore, minimum 2 osoby. */
    public static function needed(int $members): int
    {
        return max(SquadRules::FIRE_MIN_ACTIVE, (int) ceil($members * SquadRules::FIRE_THRESHOLD_PERCENT / 100));
    }

    /** Premia dnia zalezna od udzialu aktywnych (w promilach): 30-50% x1,0; 50-70% x1,25; od 70% x1,5. */
    public static function dayMultiplierPermille(int $active, int $members): int
    {
        if ($members <= 0) {
            return 1000;
        }
        $share = $active / $members;
        return $share >= 0.7 ? 1500 : ($share >= 0.5 ? 1250 : 1000);
    }

    /** Zar za dzien: 10 x premia dnia x premia serii (serii liczonej z tym dniem), zaokraglone do calosci. */
    public static function ember(int $active, int $members, int $streak): int
    {
        $bonus = SquadRules::FLAME_BONUS_PERCENT[self::level($streak)];
        return intdiv(SquadRules::FIRE_BASE_EMBER * self::dayMultiplierPermille($active, $members) * (100 + $bonus) + 50000, 100000);
    }

    /** Seria po nieudanym dniu: ognisko tli sie i spada o jeden poziom plomienia, ale nie do zera dni. */
    public static function streakAfterMiss(int $streak): int
    {
        $level = self::level($streak);
        return $level > 0 ? SquadRules::FLAME_STREAKS[$level - 1] : 0;
    }

    /**
     * Rozlicza zalegle dni wszystkich aktywnych oddzialow do wczoraj wlacznie.
     *
     * @return int liczba rozliczonych oddzialo-dni
     */
    public static function processPending(PDO $pdo, ?DateTimeImmutable $now = null): int
    {
        $now ??= Clock::now($pdo);
        $yesterday = (new DateTimeImmutable(Clock::gameDay($now)))->modify('-1 day')->format('Y-m-d');
        $stmt = $pdo->prepare(
            'SELECT id FROM squads WHERE archived_at IS NULL AND (last_fire_day IS NULL OR last_fire_day < ?)'
        );
        $stmt->execute([$yesterday]);
        $done = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $done += self::processSquad($pdo, (int) $id, $yesterday);
        }
        return $done;
    }

    /** Rozlicza dni jednego oddzialu do $upTo (Y-m-d) wlacznie. */
    public static function processSquad(PDO $pdo, int $squadId, string $upTo): int
    {
        $stmt = $pdo->prepare('SELECT * FROM squads WHERE id = ? AND archived_at IS NULL');
        $stmt->execute([$squadId]);
        $squad = $stmt->fetch();
        if (!$squad) {
            return 0;
        }

        $from = $squad['last_fire_day']
            ?? Clock::gameDay(Clock::parse((string) $squad['created_at']));
        $day = (new DateTimeImmutable((string) $from))->modify('+1 day');
        $last = new DateTimeImmutable($upTo);
        $streak = (int) $squad['flame_streak'];
        $best = (int) $squad['best_streak'];
        $litDays = (int) $squad['lit_days'];
        $ember = (int) $squad['ember'];
        $count = 0;
        $lastDone = (string) $from;

        while ($day <= $last && $count < self::CATCH_UP_DAYS) {
            $d = $day->format('Y-m-d');
            [$members, $active] = self::countFor($pdo, $squadId, $d);
            $needed = self::needed($members);
            $lit = $members >= SquadRules::FIRE_MIN_ACTIVE && $active >= $needed;
            $before = self::level($streak);
            $gain = 0;
            if ($lit) {
                $streak++;
                $best = max($best, $streak);
                $litDays++;
                $gain = self::ember($active, $members, $streak);
                $ember += $gain;
                if (self::level($streak) > $before) {
                    SquadChronicle::record($pdo, $squadId, 'fire_level', $d, ['level' => self::level($streak), 'streak' => $streak]);
                }
            } elseif ($streak > 0) {
                $streak = self::streakAfterMiss($streak);
                if (self::level($streak) < $before) {
                    SquadChronicle::record($pdo, $squadId, 'fire_dim', $d, ['level' => self::level($streak)]);
                }
            }
            $pdo->prepare(
                'INSERT IGNORE INTO squad_fire_days (squad_id, day, members, active, needed, lit, streak, ember) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$squadId, $d, $members, $active, $needed, $lit ? 1 : 0, $streak, $gain]);
            $lastDone = $d;
            $count++;
            $day = $day->modify('+1 day');
        }

        if ($count > 0) {
            $pdo->prepare(
                'UPDATE squads SET flame_streak = ?, best_streak = ?, lit_days = ?, ember = ?, last_fire_day = ? WHERE id = ?'
            )->execute([$streak, $best, $litDays, $ember, $lastDone, $squadId]);
        }
        return $count;
    }

    /**
     * Sklad i aktywni w danym dniu: liczymy czlonkow, ktorzy dolaczyli najpozniej tego dnia (wedlug czasu gry).
     *
     * @return array{0:int,1:int} [czlonkowie, aktywni]
     */
    public static function countFor(PDO $pdo, int $squadId, string $day): array
    {
        $stmt = $pdo->prepare('SELECT user_id, joined_at FROM squad_members WHERE squad_id = ?');
        $stmt->execute([$squadId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $m) {
            if (Clock::gameDay(Clock::parse((string) $m['joined_at'])) <= $day) {
                $ids[] = (int) $m['user_id'];
            }
        }
        if (!$ids) {
            return [0, 0];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_active_days WHERE day = ? AND user_id IN ($in)");
        $stmt->execute(array_merge([$day], $ids));
        return [count($ids), (int) $stmt->fetchColumn()];
    }
}
