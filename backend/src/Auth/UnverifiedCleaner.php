<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use Throwable;
use Turysta\Game\Clock;

/** Usuwa konta graczy, ktorzy nie potwierdzili adresu e-mail w ciagu RETENTION_DAYS dni. Administratorow i moderatorow nie rusza. */
final class UnverifiedCleaner
{
    public const RETENTION_DAYS = 30;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Koniec terminu potwierdzenia adresu (czas bazy, UTC). Liczony od zalozenia konta albo od ostatniej zmiany adresu e-mail.
     *
     * @param array<string,mixed> $user wiersz users
     */
    public static function deadline(array $user): string
    {
        $since = (string) ($user['email_unverified_since'] ?? $user['created_at']);
        return Clock::parse($since)->modify('+' . self::RETENTION_DAYS . ' days')->format('Y-m-d H:i:s');
    }

    /** @return list<int> identyfikatory kont, ktore minely termin */
    public function candidates(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM users
             WHERE role = 'player' AND email_verified_at IS NULL
               AND COALESCE(email_unverified_since, created_at) < UTC_TIMESTAMP() - INTERVAL ? DAY
             ORDER BY id"
        );
        $stmt->execute([self::RETENTION_DAYS]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param list<int> $ids
     * @return int liczba usunietych kont
     */
    public function purge(array $ids): int
    {
        $deleter = new UserDeleter($this->pdo);
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $deleter->delete($id);
                $deleted++;
            } catch (Throwable $e) {
                error_log("cleanup: user $id: " . $e->getMessage());
            }
        }
        return $deleted;
    }

    public function run(): int
    {
        return $this->purge($this->candidates());
    }
}
