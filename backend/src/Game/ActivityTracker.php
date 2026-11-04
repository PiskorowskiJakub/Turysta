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
            $stmt = $pdo->prepare('SELECT last_active_day, last_active_hour FROM user_stats WHERE user_id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            $last = $row ? $row['last_active_day'] : false;
            // Godzina aktywnosci (UTC) do wykresow admina: zapis raz na godzine na gracza, nie przy kazdym zadaniu.
            $hour = substr(Clock::fmt(Clock::now($pdo)), 0, 13) . ':00:00';
            if (!$row || $row['last_active_hour'] !== $hour) {
                $pdo->prepare('INSERT IGNORE INTO user_stats (user_id) VALUES (?)')->execute([$userId]);
                $pdo->prepare('INSERT IGNORE INTO user_active_hours (user_id, hour) VALUES (?, ?)')->execute([$userId, $hour]);
                $pdo->prepare('UPDATE user_stats SET last_active_hour = ? WHERE user_id = ?')->execute([$hour, $userId]);
            }
            if ($last === $day) {
                return;
            }
            $pdo->prepare('INSERT IGNORE INTO user_stats (user_id) VALUES (?)')->execute([$userId]);
            // Dzien aktywnosci zapisujemy osobno (oddzialy licza, ilu czlonkow bylo aktywnych danego dnia).
            $pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$userId, $day]);
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
