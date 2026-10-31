<?php
declare(strict_types=1);

namespace Turysta\Admin;

use PDO;

/** Wspolne stronicowanie list w panelu administratora. */
final class Paging
{
    public const PER_PAGE = 25;

    /**
     * @param list<mixed> $params
     * @param callable(array<string,mixed>):array<string,mixed> $map
     * @return array{total:int,page:int,pages:int,per_page:int,rows:list<array<string,mixed>>}
     */
    public static function run(PDO $pdo, string $countSql, string $listSql, array $params, int $page, callable $map, int $perPage = self::PER_PAGE): array
    {
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("$listSql LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        return [
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'rows' => array_map($map, $stmt->fetchAll()),
        ];
    }

    /** Ucieka znaki specjalne wzorca LIKE. */
    public static function like(string $s): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s) . '%';
    }
}
