<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use PDO;

/** Statystyki gracza widoczne w profilu. */
final class StatsService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $createdAt = (string) $stmt->fetchColumn();
        $now = Clock::now($this->pdo);
        // Numer dnia gry liczony wlacznie z dniem zalozenia konta (pierwszy dzien = 1), tak jak "Dni aktywnosci".
        $accountDays = 1 + (int) (new DateTimeImmutable(Clock::gameDay(Clock::parse($createdAt))))
            ->diff(new DateTimeImmutable(Clock::gameDay($now)))->days;

        $stmt = $this->pdo->prepare('SELECT active_days, current_streak, longest_streak FROM user_stats WHERE user_id = ?');
        $stmt->execute([$userId]);
        $s = $stmt->fetch() ?: ['active_days' => 0, 'current_streak' => 0, 'longest_streak' => 0];

        $stmt = $this->pdo->prepare('SELECT source, total_cents FROM user_earnings WHERE user_id = ?');
        $stmt->execute([$userId]);
        $bySource = [];
        foreach ($stmt->fetchAll() as $row) {
            $bySource[(string) $row['source']] = (int) $row['total_cents'];
        }

        // Wszystkie prace zawsze na liscie (takze z zerem); reszta zrodel (przyszle dodatki) trafia do "other".
        $jobs = [];
        $jobsSum = 0;
        foreach ($this->pdo->query('SELECT code, name FROM job_definitions ORDER BY duration_seconds')->fetchAll() as $j) {
            $cents = $bySource['job:' . $j['code']] ?? 0;
            $jobsSum += $cents;
            $jobs[] = ['code' => $j['code'], 'name' => $j['name'], 'cents' => $cents];
        }
        $total = array_sum($bySource);

        return [
            'registered_at' => Clock::iso($createdAt),
            'account_days' => $accountDays,
            'active_days' => (int) $s['active_days'],
            'current_streak' => (int) $s['current_streak'],
            'longest_streak' => (int) $s['longest_streak'],
            'rank' => (new RankingService($this->pdo))->rankOf($userId),
            'earnings' => [
                'total_cents' => $total,
                'jobs' => $jobs,
                'other_cents' => $total - $jobsSum,
            ],
        ];
    }
}
