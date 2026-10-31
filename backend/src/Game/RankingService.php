<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

/** Ranking graczy wg lacznej sumy punktow (remisy: to samo miejsce, kolejnosc wg rejestracji). */
final class RankingService
{
    public const PER_PAGE_OPTIONS = [25, 50, 100];
    public const DEFAULT_PER_PAGE = 50;

    public function __construct(private PDO $pdo)
    {
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
