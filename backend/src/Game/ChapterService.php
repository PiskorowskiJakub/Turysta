<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

/** Stopnie awansu: swiaty podzielone na rozdzialy, awans wymaga poziomow umiejetnosci i monet. */
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
            $nextView = [
                'id' => (int) $next['id'],
                'ordinal' => (int) $next['ordinal'],
                'name' => $next['name'],
                'world_name' => $next['w_name'],
                'new_world' => (int) $next['world_id'] !== (int) $cur['world_id'],
                'duration_seconds' => (int) $next['duration_seconds'],
                'cost_cents' => (int) $next['cost_cents'],
                'requirements' => $reqs,
                'requirements_met' => !in_array(false, array_column($reqs, 'met'), true),
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

    /** Punkty za awans: 100 razy numer rozdzialu licony przez wszystkie swiaty. */
    public function points(int $chapterId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM chapters c JOIN worlds w ON w.id = c.world_id
             JOIN (SELECT c2.ordinal AS co, w2.ordinal AS wo FROM chapters c2 JOIN worlds w2 ON w2.id = c2.world_id WHERE c2.id = ?) t
             WHERE w.ordinal < t.wo OR (w.ordinal = t.wo AND c.ordinal <= t.co)'
        );
        $stmt->execute([$chapterId]);
        return 100 * (int) $stmt->fetchColumn();
    }
}
