<?php
declare(strict_types=1);

namespace Turysta\Support;

use PDO;
use Turysta\Admin\Paging;
use Turysta\Auth\RateLimiter;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;

/** Zgloszenia bledow od graczy: gracz je wysyla, administrator przeglada i zmienia status. */
final class BugReportService
{
    public const MESSAGE_MIN = 10;
    public const MESSAGE_MAX = 2000;
    public const STATUSES = ['new', 'in_progress', 'resolved'];

    public function __construct(private PDO $pdo, private RateLimiter $limiter)
    {
    }

    /**
     * @param array<string,mixed> $user wiersz users
     * @param array<string,mixed> $in
     */
    public function create(array $user, array $in, string $ip, ?string $userAgent): int
    {
        if ($this->limiter->hit('bug:' . $user['id'], 5, 3600) || $this->limiter->hit('bug:ip:' . $ip, 20, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Wysłałeś dużo zgłoszeń. Spróbuj za godzinę.');
        }

        $message = trim((string) ($in['message'] ?? ''));
        $len = mb_strlen($message);
        if ($len < self::MESSAGE_MIN) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['message' => 'Opisz problem w kilku słowach (min. ' . self::MESSAGE_MIN . ' znaków).']);
        }
        if ($len > self::MESSAGE_MAX) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['message' => 'Opis może mieć najwyżej ' . self::MESSAGE_MAX . ' znaków.']);
        }

        $page = null;
        $rawPage = is_string($in['page'] ?? null) ? trim($in['page']) : '';
        if ($rawPage !== '' && $rawPage[0] === '/') {
            $page = mb_substr((string) (parse_url($rawPage, PHP_URL_PATH) ?: ''), 0, 255) ?: null;
        }

        $this->pdo->prepare('INSERT INTO bug_reports (user_id, message, page, user_agent) VALUES (?, ?, ?, ?)')
            ->execute([$user['id'], $message, $page, $userAgent !== null ? mb_substr($userAgent, 0, 255) : null]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string,mixed> */
    public function list(int $page, string $status): array
    {
        $params = [];
        $filter = '';
        if (in_array($status, self::STATUSES, true)) {
            $filter = ' WHERE b.status = ?';
            $params[] = $status;
        }
        $from = "FROM bug_reports b JOIN users u ON u.id = b.user_id$filter";

        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) $from",
            "SELECT b.id, b.user_id, u.character_name AS name, b.message, b.page, b.user_agent, b.status, b.created_at, b.updated_at
             $from ORDER BY (b.status = 'resolved'), b.id DESC",
            $params,
            $page,
            static fn(array $r): array => [
                'id' => (int) $r['id'],
                'user' => ['id' => (int) $r['user_id'], 'name' => (string) $r['name']],
                'message' => (string) $r['message'],
                'page' => $r['page'],
                'user_agent' => $r['user_agent'],
                'status' => (string) $r['status'],
                'created_at' => Clock::iso((string) $r['created_at']),
                'updated_at' => Clock::iso((string) $r['updated_at']),
            ]
        );
    }

    public function countNew(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM bug_reports WHERE status = 'new'")->fetchColumn();
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['status' => 'Nieprawidłowy status.']);
        }
        $this->find($id);
        $this->pdo->prepare('UPDATE bug_reports SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->pdo->prepare('DELETE FROM bug_reports WHERE id = ?')->execute([$id]);
    }

    private function find(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM bug_reports WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono zgłoszenia.');
        }
    }
}
