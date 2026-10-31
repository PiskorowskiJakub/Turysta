<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;
use Throwable;

/** Dni aktywnosci i serie: dzien liczy sie, gdy gracz wykonal jakiekolwiek zalogowane zadanie (doba wg Clock::GAME_TZ). */
final class ActivityTracker
{
    /** Zapisuje aktywnosc z dzisiaj (lub z $day, w testach). Idempotentne w obrebie doby; bledy nie przerywaja zadania. */
    public static function touch(PDO $pdo, int $userId, ?string $day = null): void
    {
        try {
            $day ??= Clock::gameDay(Clock::now($pdo));
            $stmt = $pdo->prepare('SELECT last_active_day FROM user_stats WHERE user_id = ?');
            $stmt->execute([$userId]);
            $last = $stmt->fetchColumn();
            if ($last === $day) {
                return;
            }
            $pdo->prepare('INSERT IGNORE INTO user_stats (user_id) VALUES (?)')->execute([$userId]);
            // MySQL wykonuje przypisania od lewej: seria liczona z poprzedniego last_active_day, ten zmieniamy na koncu.
            $pdo->prepare(
                'UPDATE user_stats SET
                    current_streak = IF(last_active_day = DATE_SUB(?, INTERVAL 1 DAY), current_streak + 1, 1),
                    longest_streak = GREATEST(longest_streak, current_streak),
                    active_days = active_days + 1,
                    last_active_day = ?
                 WHERE user_id = ? AND (last_active_day IS NULL OR last_active_day < ?)'
            )->execute([$day, $day, $userId, $day]);
            BadgeService::sync($pdo, $userId, Clock::now($pdo));
        } catch (Throwable $e) {
            error_log('ActivityTracker: ' . $e->getMessage());
        }
    }
}
