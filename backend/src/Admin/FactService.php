<?php
declare(strict_types=1);

namespace Turysta\Admin;

use DateTimeImmutable;
use PDO;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;

/** Ciekawostki dnia (pasek u gory strony): lista, dodawanie, edycja, wlaczanie i usuwanie. */
final class FactService
{
    /** Tyle znakow miesci sie w pasku (2 linie na komputerze); dluzsze tresci blokuje formularz i API. */
    public const TEXT_MAX = 160;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Ciekawostka na dzis: jedna na dobe gry (od polnocy czasu polskiego), taka sama dla wszystkich graczy.
     * Wybor zapisujemy w daily_fact_picks przy pierwszym pytaniu danego dnia, wiec pozniejsze zmiany listy (dodanie, wylaczenie)
     * go nie zmieniaja. Kolejne doby przechodza po wlaczonych ciekawostkach wg id; null, gdy zadnej nie ma.
     */
    public function today(?DateTimeImmutable $now = null): ?string
    {
        $now ??= Clock::now($this->pdo);
        $day = Clock::gameDay($now);
        $sel = $this->pdo->prepare('SELECT f.text FROM daily_fact_picks p JOIN daily_facts f ON f.id = p.fact_id WHERE p.day = ?');
        $sel->execute([$day]);
        $text = $sel->fetchColumn();
        if ($text !== false) {
            return (string) $text;
        }

        $ids = $this->pdo->query('SELECT id FROM daily_facts WHERE active = 1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        if (!$ids) {
            return null;
        }
        $number = intdiv((new DateTimeImmutable($day . ' 00:00:00 UTC'))->getTimestamp(), 86400);
        // INSERT IGNORE: gdy dwa zapytania wyscigna sie o ten sam dzien, wygrywa pierwsze, a drugie odczytuje jego wybor.
        $this->pdo->prepare('INSERT IGNORE INTO daily_fact_picks (day, fact_id) VALUES (?, ?)')->execute([$day, (int) $ids[$number % count($ids)]]);
        $sel->execute([$day]);
        $text = $sel->fetchColumn();
        return $text === false ? null : (string) $text;
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return array_map([$this, 'view'], $this->pdo->query('SELECT id, text, active FROM daily_facts ORDER BY id DESC')->fetchAll());
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function create(array $in): array
    {
        [$text, $active] = $this->validated($in);
        $this->pdo->prepare('INSERT INTO daily_facts (text, active) VALUES (?, ?)')->execute([$text, $active]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function update(int $id, array $in): array
    {
        $this->find($id);
        if (!array_key_exists('text', $in)) {
            // Samo wlaczanie/wylaczanie nie rusza tresci, wiec dziala tez dla starych, zbyt dlugich ciekawostek.
            $this->pdo->prepare('UPDATE daily_facts SET active = ? WHERE id = ?')->execute([empty($in['active']) ? 0 : 1, $id]);
            return $this->find($id);
        }
        [$text, $active] = $this->validated($in);
        $this->pdo->prepare('UPDATE daily_facts SET text = ?, active = ? WHERE id = ?')->execute([$text, $active, $id]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->pdo->prepare('DELETE FROM daily_facts WHERE id = ?')->execute([$id]);
    }

    /** @return array<string,mixed> */
    private function find(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, active FROM daily_facts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono ciekawostki.');
        }
        return $this->view($row);
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:string,1:int}
     */
    private function validated(array $in): array
    {
        $text = trim((string) ($in['text'] ?? ''));
        if ($text === '' || mb_strlen($text) > self::TEXT_MAX) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['text' => 'Treść musi mieć od 1 do ' . self::TEXT_MAX . ' znaków, żeby zmieściła się w pasku.']);
        }
        return [$text, empty($in['active']) ? 0 : 1];
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function view(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'text' => (string) $r['text'],
            'active' => (bool) $r['active'],
            'too_long' => mb_strlen((string) $r['text']) > self::TEXT_MAX,
        ];
    }
}
