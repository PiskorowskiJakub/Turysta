<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateInterval;
use PDO;
use Throwable;
use Turysta\Http\ApiException;

final class TrainingService
{
    public function __construct(private PDO $pdo, private Settler $settler)
    {
    }

    /** @return array<string,mixed> */
    public function skills(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, COALESCE(us.level, 0) AS level,
                    (SELECT COUNT(*) FROM training_queue q
                     WHERE q.user_id = ? AND q.skill_id = s.id AND q.status IN ('active','queued')) AS in_queue
             FROM skill_definitions s
             LEFT JOIN user_skills us ON us.skill_id = s.id AND us.user_id = ?
             ORDER BY s.id"
        );
        $stmt->execute([$userId, $userId]);

        $skills = [];
        foreach ($stmt->fetchAll() as $s) {
            $next = (int) $s['level'] + (int) $s['in_queue'] + 1;
            $maxed = $s['max_level'] !== null && $next > (int) $s['max_level'];
            $skills[] = [
                'id' => (int) $s['id'],
                'code' => $s['code'],
                'name' => $s['name'],
                'description' => $s['description'],
                'level' => (int) $s['level'],
                'in_queue' => (int) $s['in_queue'],
                'max_level' => $s['max_level'] === null ? null : (int) $s['max_level'],
                'next_level' => $maxed ? null : $next,
                'next_cost_cents' => $maxed ? null : Economy::cost($s, $next),
                'next_duration_seconds' => $maxed ? null : Economy::seconds($s, $next),
            ];
        }
        return ['server_time' => gmdate('c'), 'skills' => $skills];
    }

    /** @return array<string,mixed> */
    public function state(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT q.*, s.code AS skill_code, s.name AS skill_name FROM training_queue q
             JOIN skill_definitions s ON s.id = q.skill_id
             WHERE q.user_id = ? AND q.status IN ('active','queued')
             ORDER BY (q.status = 'active') DESC, q.position, q.id"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $maxTarget = [];
        foreach ($rows as $r) {
            $maxTarget[$r['skill_id']] = max($maxTarget[$r['skill_id']] ?? 0, (int) $r['target_level']);
        }

        $active = null;
        $queue = [];
        foreach ($rows as $r) {
            $refund = $r['status'] === 'active'
                ? intdiv((int) $r['cost_cents'] * TrainingQueue::ACTIVE_REFUND_PERCENT, 100)
                : (int) $r['cost_cents'];
            $item = [
                'id' => (int) $r['id'],
                'skill' => ['id' => (int) $r['skill_id'], 'code' => $r['skill_code'], 'name' => $r['skill_name']],
                'target_level' => (int) $r['target_level'],
                'cost_cents' => (int) $r['cost_cents'],
                'duration_seconds' => (int) $r['duration_seconds'],
                'started_at' => Clock::iso($r['started_at']),
                'ends_at' => Clock::iso($r['ends_at']),
                'cancellable' => (int) $r['target_level'] === $maxTarget[$r['skill_id']],
                'refund_cents' => $refund,
            ];
            if ($r['status'] === 'active') {
                $active = $item;
            } else {
                $queue[] = $item;
            }
        }

        return [
            'server_time' => gmdate('c'),
            'max_slots' => TrainingQueue::MAX_SLOTS,
            'active' => $active,
            'queue' => $queue,
        ];
    }

    /** @return array<string,mixed> */
    public function add(int $userId, int $skillId): array
    {
        $this->settler->settleUser($userId);
        $this->pdo->beginTransaction();
        try {
            $this->lockUser($userId);
            $now = Clock::now($this->pdo);

            $stmt = $this->pdo->prepare('SELECT * FROM skill_definitions WHERE id = ?');
            $stmt->execute([$skillId]);
            $skill = $stmt->fetch();
            if (!$skill) {
                throw new ApiException(404, 'not_found', 'Nie ma takiej umiejętności.');
            }

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM training_queue WHERE user_id = ? AND status IN ('active','queued')");
            $stmt->execute([$userId]);
            if ((int) $stmt->fetchColumn() >= TrainingQueue::MAX_SLOTS) {
                throw new ApiException(409, 'queue_full', 'Kolejka szkoleń jest pełna.');
            }

            $stmt = $this->pdo->prepare('SELECT level FROM user_skills WHERE user_id = ? AND skill_id = ? FOR UPDATE');
            $stmt->execute([$userId, $skillId]);
            $level = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM training_queue WHERE user_id = ? AND skill_id = ? AND status IN ('active','queued')"
            );
            $stmt->execute([$userId, $skillId]);
            $target = $level + (int) $stmt->fetchColumn() + 1;

            if ($skill['max_level'] !== null && $target > (int) $skill['max_level']) {
                throw new ApiException(409, 'max_level', 'Ta umiejętność ma już maksymalny poziom.');
            }

            $cost = Economy::cost($skill, $target);
            $seconds = Economy::seconds($skill, $target);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM training_queue WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $hasActive = (int) $stmt->fetchColumn() > 0;

            if ($hasActive) {
                $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM training_queue WHERE user_id = ? AND status = 'queued'");
                $stmt->execute([$userId]);
                $this->insert($userId, $skillId, $target, $cost, $seconds, 'queued', (int) $stmt->fetchColumn(), null, null);
            } else {
                $ends = $now->add(new DateInterval('PT' . $seconds . 'S'));
                $this->insert($userId, $skillId, $target, $cost, $seconds, 'active', 0, Clock::fmt($now), Clock::fmt($ends));
            }
            $id = (int) $this->pdo->lastInsertId();

            Wallet::apply($this->pdo, $userId, 'coins', -$cost, 'training_cost', 'training', $id);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
        return $this->state($userId);
    }

    /** @return array<string,mixed> */
    public function remove(int $userId, int $trainingId): array
    {
        $this->settler->settleUser($userId);
        $this->pdo->beginTransaction();
        try {
            $this->lockUser($userId);
            $now = Clock::now($this->pdo);

            $stmt = $this->pdo->prepare(
                "SELECT * FROM training_queue WHERE id = ? AND user_id = ? AND status IN ('active','queued') FOR UPDATE"
            );
            $stmt->execute([$trainingId, $userId]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new ApiException(404, 'not_found', 'Nie znaleziono takiego szkolenia.');
            }

            // Kolejne poziomy tej samej umiejetnosci zalezaly od tego szkolenia, wiec usuwamy tylko ostatnie.
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM training_queue
                 WHERE user_id = ? AND skill_id = ? AND status IN ('active','queued') AND target_level > ?"
            );
            $stmt->execute([$userId, $row['skill_id'], $row['target_level']]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new ApiException(422, 'not_last_of_skill', 'Najpierw usuń późniejsze szkolenia tej umiejętności.');
            }

            $wasActive = $row['status'] === 'active';
            $refund = $wasActive
                ? intdiv((int) $row['cost_cents'] * TrainingQueue::ACTIVE_REFUND_PERCENT, 100)
                : (int) $row['cost_cents'];

            $this->pdo->prepare("UPDATE training_queue SET status = 'cancelled', finished_at = ? WHERE id = ?")
                ->execute([Clock::fmt($now), $trainingId]);
            if ($refund > 0) {
                Wallet::apply($this->pdo, $userId, 'coins', $refund, 'training_refund', 'training', $trainingId);
            }
            if ($wasActive) {
                TrainingQueue::startNext($this->pdo, $userId, $now);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
        return $this->state($userId);
    }

    private function insert(int $userId, int $skillId, int $target, int $cost, int $seconds, string $status, int $position, ?string $start, ?string $end): void
    {
        $this->pdo->prepare(
            'INSERT INTO training_queue (user_id, skill_id, target_level, cost_cents, duration_seconds, status, position, started_at, ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $skillId, $target, $cost, $seconds, $status, $position, $start, $end]);
    }

    private function lockUser(int $userId): void
    {
        $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
