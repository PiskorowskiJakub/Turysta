<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use PDO;
use Turysta\Http\ApiException;

/** Aktualizacje gry (dziennik zmian): gracze czytaja opublikowane, administrator zarzadza wszystkimi. */
final class UpdateService
{
    public const TITLE_MAX = 120;
    public const BODY_MAX = 8000;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function published(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $rows = $this->pdo->query(
            "SELECT * FROM updates WHERE published = 1 ORDER BY released_on DESC, id DESC LIMIT $limit"
        )->fetchAll();
        return array_map([$this, 'view'], $rows);
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM updates ORDER BY released_on DESC, id DESC')->fetchAll();
        return array_map([$this, 'view'], $rows);
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function create(?int $authorId, array $in): array
    {
        [$title, $body, $date, $published] = $this->validated($in);
        $this->pdo->prepare('INSERT INTO updates (title, body, released_on, published, author_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([$title, $body, $date, $published, $authorId]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function update(int $id, array $in): array
    {
        $this->find($id);
        [$title, $body, $date, $published] = $this->validated($in);
        $this->pdo->prepare('UPDATE updates SET title = ?, body = ?, released_on = ?, published = ? WHERE id = ?')
            ->execute([$title, $body, $date, $published, $id]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->pdo->prepare('DELETE FROM updates WHERE id = ?')->execute([$id]);
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM updates WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<string,mixed> */
    private function find(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM updates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono aktualizacji.');
        }
        return $this->view($row);
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:string,1:string,2:string,3:int}
     */
    private function validated(array $in): array
    {
        $title = trim((string) ($in['title'] ?? ''));
        $body = trim((string) ($in['body'] ?? ''));
        $date = trim((string) ($in['released_on'] ?? ''));
        $errors = [];
        if ($title === '' || mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'Tytuł musi mieć od 1 do ' . self::TITLE_MAX . ' znaków.';
        }
        if ($body === '' || mb_strlen($body) > self::BODY_MAX) {
            $errors['body'] = 'Treść musi mieć od 1 do ' . self::BODY_MAX . ' znaków.';
        }
        if ($date === '') {
            $date = gmdate('Y-m-d');
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            $errors['released_on'] = 'Podaj poprawną datę.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        return [$title, $body, $date, empty($in['published']) ? 0 : 1];
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
            'released_on' => (string) $r['released_on'],
            'published' => (bool) $r['published'],
            'created_at' => Clock::iso((string) $r['created_at']),
            'updated_at' => Clock::iso((string) $r['updated_at']),
        ];
    }
}
