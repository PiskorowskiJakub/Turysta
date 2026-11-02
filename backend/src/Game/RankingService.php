<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

/** Ranking graczy wg lacznej sumy punktow (remisy: to samo miejsce, kolejnosc wg rejestracji). */
final class RankingService
{
    public const PER_PAGE_OPTIONS = [25, 50, 100];
    public const DEFAULT_PER_PAGE = 50;

    public function __construct(private PDO $pdo)
    {
    }

    public const FRIEND_PERIODS = ['week', 'month', 'all'];

    /** Poczatek okresu (UTC) liczony wg kalendarza w strefie gry: tydzien od poniedzialku, miesiac od 1. dnia. */
    public static function periodStart(string $period, DateTimeImmutable $now): ?string
    {
        $local = $now->setTimezone(new DateTimeZone(Clock::GAME_TZ));
        $start = match ($period) {
            'week' => $local->modify('monday this week')->setTime(0, 0),
            'month' => $local->modify('first day of this month')->setTime(0, 0),
            default => null,
        };
        return $start?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Ranking gracza i jego znajomych: punkty zdobyte w okresie (tydzien/miesiac) albo lacznie ('all').
     * Do okresu nie wliczamy korekt administratora ani jednorazowego przeliczenia punktow.
     *
     * @return array<string,mixed>
     */
    public function friends(int $userId, string $period): array
    {
        $period = in_array($period, self::FRIEND_PERIODS, true) ? $period : 'week';
        $now = Clock::now($this->pdo);
        $start = self::periodStart($period, $now);

        $members = "(u.id = ? OR u.id IN (SELECT IF(user_a = ?, user_b, user_a) FROM friendships WHERE status = 'accepted' AND (user_a = ? OR user_b = ?)))";
        if ($start === null) {
            $sql = "SELECT u.id, u.character_name AS name, w.points AS gained, w.points AS total
                    FROM users u JOIN wallets w ON w.user_id = u.id
                    WHERE u.status = 'active' AND $members";
            $params = [$userId, $userId, $userId, $userId];
        } else {
            $sql = "SELECT u.id, u.character_name AS name, w.points AS total,
                           COALESCE(SUM(CASE WHEN l.amount > 0 AND l.reason NOT IN ('points_rescale', 'admin_adjust') THEN l.amount END), 0) AS gained
                    FROM users u JOIN wallets w ON w.user_id = u.id
                    LEFT JOIN ledger_entries l ON l.user_id = u.id AND l.currency = 'points' AND l.created_at >= ?
                    WHERE u.status = 'active' AND $members
                    GROUP BY u.id, u.character_name, w.points";
            $params = [$start, $userId, $userId, $userId, $userId];
        }
        $stmt = $this->pdo->prepare($sql . ' ORDER BY gained DESC, total DESC, u.id');
        $stmt->execute($params);

        $rows = [];
        $rank = 0;
        $prev = null;
        foreach ($stmt->fetchAll() as $i => $r) {
            $gained = (int) $r['gained'];
            if ($prev === null || $gained !== $prev) {
                $rank = $i + 1;
                $prev = $gained;
            }
            $rows[] = [
                'id' => (int) $r['id'],
                'rank' => $rank,
                'name' => (string) $r['name'],
                'points' => $gained,
                'total' => (int) $r['total'],
                'me' => (int) $r['id'] === $userId,
            ];
        }

        return [
            'period' => $period,
            'since' => $start !== null ? Clock::iso($start) : null,
            'rows' => $rows,
        ];
    }

    /** Miejsce gracza w rankingu (remis = to samo miejsce, wszyscy aktywni gracze). */
    public function rankOf(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 + COUNT(*) FROM wallets w JOIN users u ON u.id = w.user_id
             WHERE u.status = 'active' AND w.points > (SELECT points FROM wallets WHERE user_id = ?)"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Czolowka rankingu (miejsca z remisami jak w page()).
     *
     * @return list<array<string,mixed>>
     */
    public function top(int $n, int $viewerId): array
    {
        $n = max(1, min(20, $n));
        $stmt = $this->pdo->query(
            "SELECT u.id, u.character_name AS name, w.points,
                    RANK() OVER (ORDER BY w.points DESC) AS rnk,
                    ROW_NUMBER() OVER (ORDER BY w.points DESC, u.id) AS pos
             FROM users u JOIN wallets w ON w.user_id = u.id
             WHERE u.status = 'active'
             ORDER BY pos LIMIT $n"
        );
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'rank' => (int) $row['rnk'],
                'name' => (string) $row['name'],
                'points' => (int) $row['points'],
                'me' => (int) $row['id'] === $viewerId,
            ];
        }
        return $rows;
    }

    /**
     * @return array<string,mixed>
     */
    public function page(int $userId, int $page, int $perPage, string $search, int $world): array
    {
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE;
        $search = trim(mb_substr($search, 0, 40));
        $world = max(0, $world);

        // Miejsce liczymy po wszystkich aktywnych graczach, zanim zastosujemy filtry (wyszukiwanie nie zmienia miejsc).
        $base = "SELECT u.id, u.character_name AS name, w.points,
                    RANK() OVER (ORDER BY w.points DESC) AS rnk,
                    ROW_NUMBER() OVER (ORDER BY w.points DESC, u.id) AS pos,
                    wo.ordinal AS world, c.ordinal AS chapter
                 FROM users u
                 JOIN wallets w ON w.user_id = u.id
                 LEFT JOIN player_progress p ON p.user_id = u.id
                 LEFT JOIN chapters c ON c.id = p.chapter_id
                 LEFT JOIN worlds wo ON wo.id = c.world_id
                 WHERE u.status = 'active'";

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = 'name LIKE ?';
            $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
        }
        if ($world > 0) {
            $where[] = 'world = ?';
            $params[] = $world;
        }
        $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("WITH r AS ($base) SELECT COUNT(*) FROM r$filter");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("WITH r AS ($base) SELECT * FROM r$filter ORDER BY pos LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'rank' => (int) $row['rnk'],
                'name' => (string) $row['name'],
                'points' => (int) $row['points'],
                'world' => $row['world'] !== null ? (int) $row['world'] : null,
                'chapter' => $row['chapter'] !== null ? (int) $row['chapter'] : null,
                'me' => (int) $row['id'] === $userId,
            ];
        }

        $stmt = $this->pdo->prepare("WITH r AS ($base) SELECT rnk, pos, points FROM r WHERE id = ?");
        $stmt->execute([$userId]);
        $me = $stmt->fetch();

        return [
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'per_page_options' => self::PER_PAGE_OPTIONS,
            'rows' => $rows,
            'me' => $me ? [
                'rank' => (int) $me['rnk'],
                'points' => (int) $me['points'],
                'page' => (int) ceil(((int) $me['pos']) / $perPage),
            ] : null,
        ];
    }
}
