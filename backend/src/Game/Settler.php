<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use PDO;
use Throwable;

/** Rozlicza zakonczone szkolenia i prace. Idempotentne: drugie wywolanie nic nie zmienia. */
final class Settler
{
    public function __construct(private PDO $pdo)
    {
    }

    public function settleUser(int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            $now = Clock::now($this->pdo);
            $this->settleTraining($userId, $now);
            $this->settleJob($userId, $now);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function settleTraining(int $userId, DateTimeImmutable $now): void
    {
        while (true) {
            $stmt = $this->pdo->prepare(
                "SELECT q.*, s.name AS skill_name FROM training_queue q
                 JOIN skill_definitions s ON s.id = q.skill_id
                 WHERE q.user_id = ? AND q.status = 'active' AND q.ends_at <= ? LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$userId, Clock::fmt($now)]);
            $row = $stmt->fetch();
            if (!$row) {
                return;
            }

            $this->pdo->prepare("UPDATE training_queue SET status = 'done', finished_at = ends_at WHERE id = ?")
                ->execute([$row['id']]);
            $this->pdo->prepare('UPDATE user_skills SET level = level + 1 WHERE user_id = ? AND skill_id = ?')
                ->execute([$userId, $row['skill_id']]);
            Wallet::apply(
                $this->pdo,
                $userId,
                'points',
                Economy::trainingPoints((int) $row['target_level']),
                'training_points',
                'training',
                (int) $row['id']
            );
            Notifier::add($this->pdo, $userId, 'training_finished', [
                'skill' => $row['skill_name'],
                'level' => (int) $row['target_level'],
            ]);

            // Nastepne startuje dokladnie w chwili konca poprzedniego, nawet gdy rozliczamy pozniej.
            TrainingQueue::startNext($this->pdo, $userId, Clock::parse((string) $row['ends_at']));
        }
    }

    private function settleJob(int $userId, DateTimeImmutable $now): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*, d.name AS job_name, d.reward_cents FROM job_runs r
             JOIN job_definitions d ON d.id = r.job_id
             WHERE r.user_id = ? AND r.status = 'active' AND r.ends_at <= ? LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([$userId, Clock::fmt($now)]);
        $run = $stmt->fetch();
        if (!$run) {
            return;
        }

        $payout = (int) $run['reward_cents'];
        $this->pdo->prepare("UPDATE job_runs SET status = 'done', finished_at = ends_at, payout_cents = ? WHERE id = ?")
            ->execute([$payout, $run['id']]);
        Wallet::apply($this->pdo, $userId, 'coins', $payout, 'job_payout', 'job_run', (int) $run['id']);
        $points = Economy::jobPoints($payout);
        if ($points > 0) {
            Wallet::apply($this->pdo, $userId, 'points', $points, 'job_points', 'job_run', (int) $run['id']);
        }
        Notifier::add($this->pdo, $userId, 'job_finished', [
            'job' => $run['job_name'],
            'payout_cents' => $payout,
        ]);
    }
}
