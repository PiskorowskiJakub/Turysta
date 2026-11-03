<?php
declare(strict_types=1);

namespace Turysta\Social;

use PDO;
use Throwable;
use Turysta\Game\Clock;

/**
 * Aktywnosc znajomych na stronie glownej: zdarzenia (nowy swiat, odznaka od zlotej wzwyz) zapisujemy dla kazdego gracza,
 * a pokazujemy je tylko znajomym. Przelacznik prywatnosci (users.share_activity) dziala w obie strony: kto go wylacza,
 * ten nie pokazuje swoich zdarzen i sam nie widzi cudzych.
 */
final class ActivityFeed
{
    public const WINDOW_DAYS = 14;
    public const MAX_EVENTS = 10;

    /** @param array<string,mixed> $payload */
    public static function record(PDO $pdo, int $userId, string $type, string $refKey, array $payload): void
    {
        try {
            $pdo->prepare('INSERT IGNORE INTO activity_events (user_id, type, ref_key, payload) VALUES (?, ?, ?, ?)')
                ->execute([$userId, $type, $refKey, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable $e) {
            error_log('ActivityFeed: ' . $e->getMessage());
        }
    }

    /**
     * @return array{enabled:bool,events:list<array<string,mixed>>}
     */
    public static function forUser(PDO $pdo, int $userId, int $limit = self::MAX_EVENTS): array
    {
        $stmt = $pdo->prepare('SELECT share_activity FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        if ((int) $stmt->fetchColumn() !== 1) {
            return ['enabled' => false, 'events' => []];
        }

        $stmt = $pdo->prepare(
            "SELECT COALESCE(w.ordinal, 1) FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id WHERE p.user_id = ?"
        );
        $stmt->execute([$userId]);
        $myWorld = (int) ($stmt->fetchColumn() ?: 1);

        $limit = max(1, min(30, $limit));
        $stmt = $pdo->prepare(
            "SELECT e.id, e.type, e.payload, e.created_at, u.id AS uid, u.character_name
             FROM friendships f
             JOIN users u ON u.id = IF(f.user_a = ?, f.user_b, f.user_a)
             JOIN activity_events e ON e.user_id = u.id
             WHERE f.status = 'accepted' AND (f.user_a = ? OR f.user_b = ?)
               AND u.share_activity = 1 AND u.status = 'active'
               AND e.created_at > UTC_TIMESTAMP() - INTERVAL " . self::WINDOW_DAYS . " DAY
             ORDER BY e.created_at DESC, e.id DESC LIMIT $limit"
        );
        $stmt->execute([$userId, $userId, $userId]);

        $names = $pdo->query('SELECT code, name FROM skill_definitions')->fetchAll(PDO::FETCH_KEY_PAIR);
        $events = [];
        foreach ($stmt->fetchAll() as $r) {
            $payload = json_decode((string) $r['payload'], true);
            $payload = is_array($payload) ? $payload : [];
            // Nie zdradzamy umiejetnosci ze swiatow, do ktorych gracz jeszcze nie dotarl: zostaje sam numer swiata.
            if (isset($payload['world']) && (int) $payload['world'] > $myWorld) {
                $payload['group'] = null;
            }
            // Czytelna nazwa umiejetnosci (odznaki specjalne tlumaczy frontend).
            if (is_string($payload['group'] ?? null) && isset($names[$payload['group']])) {
                $payload['badge_name'] = (string) $names[$payload['group']];
            }
            $events[] = [
                'id' => (int) $r['id'],
                'type' => (string) $r['type'],
                'user' => ['id' => (int) $r['uid'], 'name' => (string) $r['character_name']],
                'payload' => $payload,
                'created_at' => Clock::iso((string) $r['created_at']),
            ];
        }
        return ['enabled' => true, 'events' => $events];
    }
}
