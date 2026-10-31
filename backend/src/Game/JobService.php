<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;
use Turysta\Http\ApiException;

final class JobService
{
    public function __construct(private PDO $pdo, private Settler $settler)
    {
    }

    /** @return array<string,mixed> */
    public function list(int $userId): array
    {
        $now = Clock::now($this->pdo);
        $jobs = [];
        $tradeLevel = $this->tradeLevel($userId);
        foreach ($this->pdo->query('SELECT * FROM job_definitions ORDER BY duration_seconds')->fetchAll() as $j) {
            $jobs[] = $this->jobView($j, Economy::boostedReward((int) $j['reward_cents'], $tradeLevel));
        }
        $avail = $this->availableAt($userId, $now);
        return [
            'server_time' => gmdate('c'),
            'jobs' => $jobs,
            'trade' => ['level' => $tradeLevel, 'bonus_permille' => Economy::tradeBonusPermille($tradeLevel)],
            'available_at' => $avail ? Clock::iso(Clock::fmt($avail)) : null,
            'active' => $this->active($userId),
        ];
    }

    /** @return array<string,mixed>|null */
    public function active(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*, d.code, d.name, d.description, d.duration_seconds, d.reward_cents, d.cancel_policy, d.cooldown_seconds
             FROM job_runs r JOIN job_definitions d ON d.id = r.job_id
             WHERE r.user_id = ? AND r.status = 'active' LIMIT 1"
        );
        $stmt->execute([$userId]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }
        $now = Clock::now($this->pdo);
        return [
            'id' => (int) $r['id'],
            'job' => $this->jobView(['id' => $r['job_id']] + $r, $this->rewardOf($r)),
            'started_at' => Clock::iso($r['started_at']),
            'ends_at' => Clock::iso($r['ends_at']),
            'earned_cents' => $r['cancel_policy'] === 'proportional' ? $this->earned($r, $now) : null,
        ];
    }

    /** @return array<string,mixed> */
    public function start(int $userId, int $jobId): array
    {
        $this->settler->settleUser($userId);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            $now = Clock::now($this->pdo);

            $stmt = $this->pdo->prepare('SELECT * FROM job_definitions WHERE id = ?');
            $stmt->execute([$jobId]);
            $job = $stmt->fetch();
            if (!$job) {
                throw new ApiException(404, 'not_found', 'Nie ma takiej pracy.');
            }

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM job_runs WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new ApiException(409, 'job_active', 'Już pracujesz. Dokończ lub przerwij obecną pracę.');
            }

            $avail = $this->availableAt($userId, $now);
            if ($avail !== null) {
                throw new ApiException(409, 'job_cooldown', 'Musisz odpocząć przed kolejną pracą.');
            }

            $ends = $now->add(new DateInterval('PT' . (int) $job['duration_seconds'] . 'S'));
            $reward = Economy::boostedReward((int) $job['reward_cents'], $this->tradeLevel($userId));
            $this->pdo->prepare('INSERT INTO job_runs (user_id, job_id, started_at, ends_at, reward_snapshot_cents) VALUES (?, ?, ?, ?, ?)')
                ->execute([$userId, $jobId, Clock::fmt($now), Clock::fmt($ends), $reward]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return $this->list($userId);
    }

    /**
     * Przerwanie pracy. proportional: wyplata za przepracowany czas. none: brak wyplaty, wymaga potwierdzenia.
     *
     * @return array<string,mixed>
     */
    public function stop(int $userId, bool $confirm): array
    {
        $this->settler->settleUser($userId);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            $now = Clock::now($this->pdo);

            $stmt = $this->pdo->prepare(
                "SELECT r.*, d.code AS job_code, d.reward_cents, d.cancel_policy FROM job_runs r
                 JOIN job_definitions d ON d.id = r.job_id
                 WHERE r.user_id = ? AND r.status = 'active' FOR UPDATE"
            );
            $stmt->execute([$userId]);
            $run = $stmt->fetch();
            if (!$run) {
                throw new ApiException(404, 'no_active_job', 'Nie masz aktywnej pracy.');
            }

            if ($run['cancel_policy'] === 'none') {
                if (!$confirm) {
                    throw new ApiException(409, 'job_cancel_forfeit', 'Jeśli przerwiesz tę pracę, nie dostaniesz nic.');
                }
                $payout = 0;
            } else {
                $payout = $this->earned($run, $now);
            }

            $this->pdo->prepare("UPDATE job_runs SET status = 'cancelled', finished_at = ?, payout_cents = ? WHERE id = ?")
                ->execute([Clock::fmt($now), $payout, $run['id']]);
            if ($payout > 0) {
                Wallet::earn($this->pdo, $userId, 'job:' . $run['job_code'], $payout, 'job_payout', 'job_run', (int) $run['id']);
                $points = Economy::jobPoints($payout);
                if ($points > 0) {
                    Wallet::apply($this->pdo, $userId, 'points', $points, 'job_points', 'job_run', (int) $run['id']);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return ['payout_cents' => $payout] + $this->list($userId);
    }

    /** @param array<string,mixed> $run wiersz z started_at, ends_at i reward_cents */
    private function earned(array $run, DateTimeImmutable $now): int
    {
        $start = Clock::parse((string) $run['started_at'])->getTimestamp();
        $end = Clock::parse((string) $run['ends_at'])->getTimestamp();
        $duration = max(1, $end - $start);
        $elapsed = max(0, min($duration, $now->getTimestamp() - $start));
        return intdiv($this->rewardOf($run) * $elapsed, $duration);
    }

    /** @param array<string,mixed> $run wiersz job_runs (opcjonalnie z reward_cents definicji) */
    private function rewardOf(array $run): int
    {
        return (int) ($run['reward_snapshot_cents'] ?? $run['reward_cents']);
    }

    private function tradeLevel(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT us.level FROM user_skills us JOIN skill_definitions s ON s.id = us.skill_id
             WHERE us.user_id = ? AND s.code = 'trade'"
        );
        $stmt->execute([$userId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function availableAt(int $userId, DateTimeImmutable $now): ?DateTimeImmutable
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.finished_at, d.cooldown_seconds FROM job_runs r
             JOIN job_definitions d ON d.id = r.job_id
             WHERE r.user_id = ? AND r.status <> 'active' AND r.finished_at IS NOT NULL
               AND NOT (r.status = 'cancelled' AND d.cancel_policy = 'proportional')
             ORDER BY r.finished_at DESC, r.id DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        $last = $stmt->fetch();
        if (!$last) {
            return null;
        }
        $at = Clock::parse((string) $last['finished_at'])->add(new DateInterval('PT' . (int) $last['cooldown_seconds'] . 'S'));
        return $at > $now ? $at : null;
    }

    /**
     * @param array<string,mixed> $j
     * @return array<string,mixed>
     */
    private function jobView(array $j, ?int $reward = null): array
    {
        return [
            'id' => (int) $j['id'],
            'code' => $j['code'],
            'name' => $j['name'],
            'description' => $j['description'],
            'duration_seconds' => (int) $j['duration_seconds'],
            'reward_cents' => $reward ?? (int) $j['reward_cents'],
            'base_reward_cents' => (int) $j['reward_cents'],
            'cooldown_seconds' => (int) $j['cooldown_seconds'],
            'cancel_policy' => $j['cancel_policy'],
        ];
    }
}
