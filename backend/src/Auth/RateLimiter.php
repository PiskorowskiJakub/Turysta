<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;

final class RateLimiter
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Liczy probe i zwraca true, gdy limit w oknie zostal przekroczony. */
    public function hit(string $key, int $max, int $windowSeconds): bool
    {
        $hash = hash('sha256', $key);
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limits (bucket_key, hits, window_start) VALUES (?, 1, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               hits = IF(window_start < UTC_TIMESTAMP() - INTERVAL ? SECOND, 1, hits + 1),
               window_start = IF(window_start < UTC_TIMESTAMP() - INTERVAL ? SECOND, UTC_TIMESTAMP(), window_start)'
        );
        $stmt->execute([$hash, $windowSeconds, $windowSeconds]);

        $sel = $this->pdo->prepare('SELECT hits FROM rate_limits WHERE bucket_key = ?');
        $sel->execute([$hash]);
        return (int) $sel->fetchColumn() > $max;
    }

    public function clear(string $key): void
    {
        $this->pdo->prepare('DELETE FROM rate_limits WHERE bucket_key = ?')->execute([hash('sha256', $key)]);
    }
}
