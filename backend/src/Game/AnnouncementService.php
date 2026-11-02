<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;
use Turysta\Http\ApiException;

/** Tablica ogloszen: gracze widza opublikowane, administrator zarzadza wszystkimi. */
final class AnnouncementService
{
    public const HOME_LIMIT = 5;
    public const TITLE_MAX = 120;
    public const BODY_MAX = 2000;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function published(int $limit = self::HOME_LIMIT): array
    {
        $limit = max(1, min(50, $limit));
        $rows = $this->pdo->query(
            "SELECT * FROM announcements WHERE published = 1 ORDER BY pinned DESC, created_at DESC, id DESC LIMIT $limit"
        )->fetchAll();
        return array_map([$this, 'view'], $rows);
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM announcements ORDER BY pinned DESC, created_at DESC, id DESC')->fetchAll();
        return array_map([$this, 'view'], $rows);
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function create(int $authorId, array $in): array
    {
        [$title, $body, $published, $pinned, $updateId] = $this->validated($in);
        $this->pdo->prepare('INSERT INTO announcements (title, body, published, pinned, update_id, author_id) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$title, $body, $published, $pinned, $updateId, $authorId]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function update(int $id, array $in): array
    {
        $this->find($id);
        [$title, $body, $published, $pinned, $updateId] = $this->validated($in);
        $this->pdo->prepare('UPDATE announcements SET title = ?, body = ?, published = ?, pinned = ?, update_id = ? WHERE id = ?')
            ->execute([$title, $body, $published, $pinned, $updateId, $id]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
    }

    /** @return array<string,mixed> */
    private function find(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM announcements WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono ogłoszenia.');
        }
        return $this->view($row);
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:string,1:string,2:int,3:int,4:?int}
     */
    private function validated(array $in): array
    {
        $title = trim((string) ($in['title'] ?? ''));
        $body = trim((string) ($in['body'] ?? ''));
        $errors = [];
        if ($title === '' || mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'Tytuł musi mieć od 1 do ' . self::TITLE_MAX . ' znaków.';
        }
        if ($body === '' || mb_strlen($body) > self::BODY_MAX) {
            $errors['body'] = 'Treść musi mieć od 1 do ' . self::BODY_MAX . ' znaków.';
        }
        $updateId = isset($in['update_id']) && $in['update_id'] !== '' && $in['update_id'] !== null ? (int) $in['update_id'] : null;
        if ($updateId !== null && !(new UpdateService($this->pdo))->exists($updateId)) {
            $errors['update_id'] = 'Wybrana aktualizacja nie istnieje.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        return [$title, $body, empty($in['published']) ? 0 : 1, empty($in['pinned']) ? 0 : 1, $updateId];
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function view(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'title' => (string) $r['title'],
            'body' => (string) $r['body'],
            'published' => (bool) $r['published'],
            'pinned' => (bool) $r['pinned'],
            'update_id' => $r['update_id'] !== null ? (int) $r['update_id'] : null,
            'created_at' => Clock::iso((string) $r['created_at']),
            'updated_at' => Clock::iso((string) $r['updated_at']),
        ];
    }
}
