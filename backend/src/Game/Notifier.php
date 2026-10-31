<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

final class Notifier
{
    /** @param array<string,mixed> $payload */
    public static function add(PDO $pdo, int $userId, string $type, array $payload): void
    {
        $pdo->prepare('INSERT INTO notifications (user_id, type, payload) VALUES (?, ?, ?)')
            ->execute([$userId, $type, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }
}
