<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

final class DbSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(private PDO $pdo, private string $ip = '', private string $userAgent = '')
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT data FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, user_id, data, ip, user_agent) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), data = VALUES(data), last_activity = UTC_TIMESTAMP()'
        );
        return $stmt->execute([$id, $userId, $data, $this->ip ?: null, $this->userAgent ?: null]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < UTC_TIMESTAMP() - INTERVAL ? SECOND');
        $stmt->execute([$max_lifetime]);
        return $stmt->rowCount();
    }

    public function validateId(string $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->pdo->prepare('UPDATE sessions SET last_activity = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
    }
}
