<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

/** Odznaki: przyznawanie (z data zdobycia) i lista zdobytych. Odznaka zostaje na stale. */
final class BadgeService
{
    /**
     * Przyznaje wszystkie odznaki, ktorych warunki gracz juz spelnia (idempotentne). $at to data zdobycia
     * (np. koniec szkolenia, a przy rozliczaniu z opoznieniem nie chwila rozliczenia).
     * Bledy nie przerywaja zadania, z ktorego jest wywolane.
     */
    public static function sync(PDO $pdo, int $userId, DateTimeImmutable $at): void
    {
        try {
            $when = Clock::fmt($at);
            $pdo->prepare(
                "INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
                 SELECT ?, b.id, ? FROM badge_definitions b
                 JOIN skill_definitions sd ON sd.code = b.skill_code
                 JOIN user_skills us ON us.skill_id = sd.id AND us.user_id = ? AND us.level >= b.threshold
                 WHERE b.condition_type = 'skill_level'"
            )->execute([$userId, $when, $userId]);
            $pdo->prepare(
                "INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
                 SELECT ?, b.id, ? FROM badge_definitions b
                 JOIN user_stats st ON st.user_id = ? AND st.longest_streak >= b.threshold
                 WHERE b.condition_type = 'streak_days'"
            )->execute([$userId, $when, $userId]);
            $pdo->prepare(
                "INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at)
                 SELECT ?, b.id, ? FROM badge_definitions b
                 JOIN user_stats st ON st.user_id = ? AND st.active_days >= b.threshold
                 WHERE b.condition_type = 'active_days'"
            )->execute([$userId, $when, $userId]);
        } catch (Throwable $e) {
            error_log('BadgeService: ' . $e->getMessage());
        }
    }

    /**
     * Zdobyte odznaki gracza (kazdy stopien osobno). 'group' to umiejetnosc albo odznaka specjalna,
     * 'min' to wymagany poziom lub liczba dni.
     * @return list<array{group:string,tier:string,min:int,earned_at:string}>
     */
    public static function earned(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare(
            'SELECT b.code, b.tier, b.threshold, ub.earned_at FROM user_badges ub
             JOIN badge_definitions b ON b.id = ub.badge_id
             WHERE ub.user_id = ? ORDER BY ub.earned_at, b.id'
        );
        $stmt->execute([$userId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'group' => explode('.', (string) $row['code'])[0],
                'tier' => (string) $row['tier'],
                'min' => (int) $row['threshold'],
                'earned_at' => (new DateTimeImmutable((string) $row['earned_at'], new DateTimeZone('UTC')))->format(DATE_ATOM),
            ];
        }
        return $out;
    }
}
