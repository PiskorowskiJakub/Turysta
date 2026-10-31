<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

/** Stopnie awansu: swiaty podzielone na rozdzialy, awans wymaga poziomow umiejetnosci i dukatow. */
final class ChapterService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function current(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, w.ordinal AS w_ordinal, w.name AS w_name
             FROM player_progress p
             JOIN chapters c ON c.id = p.chapter_id
             JOIN worlds w ON w.id = c.world_id
             WHERE p.user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** Nastepny rozdzial w kolejnosci swiat -> rozdzial. @return array<string,mixed>|null */
    public function next(int $userId): ?array
    {
        $cur = $this->current($userId);
        if (!$cur) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT c.*, w.ordinal AS w_ordinal, w.name AS w_name
             FROM chapters c JOIN worlds w ON w.id = c.world_id
             WHERE w.ordinal > ? OR (w.ordinal = ? AND c.ordinal > ?)
             ORDER BY w.ordinal, c.ordinal LIMIT 1'
        );
        $stmt->execute([$cur['w_ordinal'], $cur['w_ordinal'], $cur['ordinal']]);
        return $stmt->fetch() ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function requirements(int $userId, int $chapterId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.name, r.level AS required, COALESCE(us.level, 0) AS level
             FROM chapter_requirements r
             JOIN skill_definitions s ON s.id = r.skill_id
             LEFT JOIN user_skills us ON us.skill_id = s.id AND us.user_id = ?
             WHERE r.chapter_id = ? ORDER BY s.id'
        );
        $stmt->execute([$userId, $chapterId]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[] = [
                'skill_id' => (int) $r['id'],
                'code' => $r['code'],
                'name' => $r['name'],
                'required' => (int) $r['required'],
                'level' => (int) $r['level'],
                'met' => (int) $r['level'] >= (int) $r['required'],
            ];
        }
        return $out;
    }

    /**
     * Wymaganie umiejetnosci swiata: awans w obrebie swiata wymaga stopniowego rozwoju jego umiejetnosci
     * (rozdzial n: ceil(max_level * (n-1) / 10) na kazdej), a przejscie do nowego swiata - wszystkich na maksimum.
     *
     * @param array<string,mixed> $target rozdzial docelowy (wiersz chapters + w_ordinal)
     * @return array<string,mixed>|null null gdy swiat nie ma umiejetnosci
     */
    public function worldRequirement(int $userId, array $target): ?array
    {
        $ordinal = (int) $target['ordinal'];
        $transition = $ordinal === 1 && (int) $target['w_ordinal'] > 1;
        $worldOrdinal = $transition ? (int) $target['w_ordinal'] - 1 : (int) $target['w_ordinal'];

        $stmt = $this->pdo->prepare(
            'SELECT s.max_level, COALESCE(us.level, 0) AS level
             FROM skill_definitions s
             LEFT JOIN user_skills us ON us.skill_id = s.id AND us.user_id = ?
             WHERE s.world_ordinal = ?'
        );
        $stmt->execute([$userId, $worldOrdinal]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return null;
        }

        $required = 0;
        $done = 0;
        foreach ($rows as $r) {
            $max = (int) ($r['max_level'] ?? 0);
            $need = $transition ? $max : min($max, (int) ceil($max * ($ordinal - 1) / 10));
            $required = max($required, $need);
            if ((int) $r['level'] >= $need) {
                $done++;
            }
        }
        $stmt = $this->pdo->prepare('SELECT name FROM worlds WHERE ordinal = ?');
        $stmt->execute([$worldOrdinal]);

        return [
            'world_ordinal' => $worldOrdinal,
            'world_name' => (string) $stmt->fetchColumn(),
            'required' => $required,
            'done' => $done,
            'total' => count($rows),
            'complete' => $transition,
            'met' => $done === count($rows),
        ];
    }

    /** Czy gracz spelnia wszystkie wymagania awansu (poziomy podstawowe i umiejetnosci swiata). @param array<string,mixed> $target */
    public function requirementsMet(int $userId, array $target): bool
    {
        foreach ($this->requirements($userId, (int) $target['id']) as $req) {
            if (!$req['met']) {
                return false;
            }
        }
        $world = $this->worldRequirement($userId, $target);
        return $world === null || $world['met'];
    }

    public function inQueue(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM training_queue WHERE user_id = ? AND chapter_id IS NOT NULL AND status IN ('active','queued')"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return array<string,mixed> */
    public function overview(int $userId): array
    {
        $cur = $this->current($userId);
        if (!$cur) {
            return ['server_time' => gmdate('c'), 'world' => null, 'chapters' => [], 'current' => null, 'next' => null];
        }
        $queued = $this->inQueue($userId);
        $next = $this->next($userId);

        $stmt = $this->pdo->prepare('SELECT id, ordinal, name FROM chapters WHERE world_id = ? ORDER BY ordinal');
        $stmt->execute([$cur['world_id']]);
        $chapters = [];
        foreach ($stmt->fetchAll() as $c) {
            $status = 'locked';
            if ((int) $c['ordinal'] < (int) $cur['ordinal']) {
                $status = 'done';
            } elseif ((int) $c['ordinal'] === (int) $cur['ordinal']) {
                $status = 'current';
            } elseif ($next && (int) $c['id'] === (int) $next['id']) {
                $status = $queued ? 'in_queue' : 'next';
            }
            $chapters[] = ['id' => (int) $c['id'], 'ordinal' => (int) $c['ordinal'], 'name' => $c['name'], 'status' => $status];
        }

        $nextView = null;
        if ($next) {
            $reqs = $this->requirements($userId, (int) $next['id']);
            $worldReq = $this->worldRequirement($userId, $next);
            $nextView = [
                'id' => (int) $next['id'],
                'ordinal' => (int) $next['ordinal'],
                'name' => $next['name'],
                'world_name' => $next['w_name'],
                'new_world' => (int) $next['world_id'] !== (int) $cur['world_id'],
                'duration_seconds' => (int) $next['duration_seconds'],
                'cost_cents' => (int) $next['cost_cents'],
                'requirements' => $reqs,
                'world_requirement' => $worldReq,
                'requirements_met' => !in_array(false, array_column($reqs, 'met'), true) && ($worldReq === null || $worldReq['met']),
                'in_queue' => $queued,
            ];
        }

        return [
            'server_time' => gmdate('c'),
            'world' => ['id' => (int) $cur['world_id'], 'ordinal' => (int) $cur['w_ordinal'], 'name' => $cur['w_name']],
            'chapters' => $chapters,
            'current' => ['id' => (int) $cur['id'], 'ordinal' => (int) $cur['ordinal'], 'name' => $cur['name'], 'story_text' => $cur['story_text']],
            'next' => $nextView,
        ];
    }

    /** Punkty za awans: 50 x numer swiata, a za wejscie do nowego swiata (rozdzial 1) 1000. */
    public function points(int $chapterId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.ordinal AS wo, c.ordinal AS co FROM chapters c JOIN worlds w ON w.id = c.world_id WHERE c.id = ?'
        );
        $stmt->execute([$chapterId]);
        $row = $stmt->fetch();
        return $row ? Economy::chapterPoints((int) $row['wo'], (int) $row['co']) : 0;
    }
}
