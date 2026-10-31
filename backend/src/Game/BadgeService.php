<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;
use Turysta\Http\ApiException;

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

    public const MAX_FEATURED = 5;

    /** Wyroznione odznaki (grupy) w kolejnosci wybranej przez gracza. @return list<string> */
    public static function featured(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT badge_group FROM user_featured_badges WHERE user_id = ? ORDER BY position');
        $stmt->execute([$userId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Zapisuje wyroznione odznaki: do 5, bez powtorzen, tylko takie, ktore gracz juz zdobyl.
     * @param mixed $groups
     * @return list<string>
     */
    public static function setFeatured(PDO $pdo, int $userId, mixed $groups): array
    {
        if (!is_array($groups) || count($groups) > self::MAX_FEATURED) {
            throw new ApiException(422, 'validation_failed', 'Możesz wyróżnić najwyżej 5 odznak.', ['groups' => 'Wybierz najwyżej 5 odznak.']);
        }
        $groups = array_values($groups);
        $have = array_unique(array_map(fn($b) => $b['group'], self::earned($pdo, $userId)));
        foreach ($groups as $g) {
            if (!is_string($g) || !in_array($g, $have, true)) {
                throw new ApiException(422, 'validation_failed', 'Wyróżnić można tylko zdobyte odznaki.', ['groups' => 'Wybierz zdobyte odznaki.']);
            }
        }
        if (count(array_unique($groups)) !== count($groups)) {
            throw new ApiException(422, 'validation_failed', 'Odznaki nie mogą się powtarzać.', ['groups' => 'Odznaki nie mogą się powtarzać.']);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_featured_badges WHERE user_id = ?')->execute([$userId]);
            $insert = $pdo->prepare('INSERT INTO user_featured_badges (user_id, position, badge_group) VALUES (?, ?, ?)');
            foreach ($groups as $i => $g) {
                $insert->execute([$userId, $i + 1, $g]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        return $groups;
    }
}
