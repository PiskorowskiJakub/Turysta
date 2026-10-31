<?php
declare(strict_types=1);

namespace Turysta\Admin;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Turysta\Game\Clock;

/** Statystyki strony dla administratora: konta, aktywnosc, ekonomia i rozklad graczy po swiatach. */
final class AdminStatsService
{
    public const SERIES_DAYS = 30;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function overview(): array
    {
        $now = Clock::now($this->pdo);
        $today = Clock::gameDay($now);

        $val = function (string $sql, array $params = []): int {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        };

        $accounts = [
            'total' => $val('SELECT COUNT(*) FROM users'),
            'active' => $val("SELECT COUNT(*) FROM users WHERE status = 'active' AND (suspended_until IS NULL OR suspended_until <= UTC_TIMESTAMP())"),
            'suspended' => $val("SELECT COUNT(*) FROM users WHERE status <> 'banned' AND suspended_until > UTC_TIMESTAMP()"),
            'banned' => $val("SELECT COUNT(*) FROM users WHERE status = 'banned'"),
            'unverified' => $val('SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL'),
            'admins' => $val("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
            'new_24h' => $val('SELECT COUNT(*) FROM users WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 1 DAY'),
            'new_7d' => $val('SELECT COUNT(*) FROM users WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY'),
            'new_30d' => $val('SELECT COUNT(*) FROM users WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY'),
            'tutorial_done' => $val('SELECT COUNT(*) FROM users WHERE tutorial_done_at IS NOT NULL'),
            'referred' => $val('SELECT COUNT(*) FROM referrals'),
        ];

        // "Aktywni" = gracze z dniem aktywnosci (user_stats.last_active_day) w danym oknie (doba wg czasu gry).
        $activeSince = static fn(int $days): string => (new DateTimeImmutable($today))->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $activity = [
            'dau' => $val('SELECT COUNT(*) FROM user_stats WHERE last_active_day >= ?', [$activeSince(1)]),
            'wau' => $val('SELECT COUNT(*) FROM user_stats WHERE last_active_day >= ?', [$activeSince(7)]),
            'mau' => $val('SELECT COUNT(*) FROM user_stats WHERE last_active_day >= ?', [$activeSince(30)]),
            'online_now' => $val('SELECT COUNT(DISTINCT user_id) FROM sessions WHERE user_id IS NOT NULL AND last_activity >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE'),
            'logins_24h' => $val('SELECT COUNT(*) FROM login_events WHERE logged_in_at >= UTC_TIMESTAMP() - INTERVAL 1 DAY'),
            'logins_7d' => $val('SELECT COUNT(*) FROM login_events WHERE logged_in_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY'),
            'avg_active_days' => round((float) $this->pdo->query('SELECT COALESCE(AVG(active_days), 0) FROM user_stats')->fetchColumn(), 1),
            'daily_claims_today' => $val('SELECT COUNT(*) FROM daily_rewards WHERE last_claim_day = ?', [$today]),
            'daily_claims_total' => $val('SELECT COALESCE(SUM(claims_total), 0) FROM daily_rewards'),
        ];

        $wallets = $this->pdo->query('SELECT COALESCE(SUM(coins_cents), 0) AS coins, COALESCE(SUM(tickets), 0) AS tickets, COALESCE(SUM(points), 0) AS points FROM wallets')->fetch();
        $economy = [
            'coins_cents' => (int) $wallets['coins'],
            'tickets' => (int) $wallets['tickets'],
            'points' => (int) $wallets['points'],
            'jobs_done_24h' => $val("SELECT COUNT(*) FROM job_runs WHERE status = 'done' AND finished_at >= UTC_TIMESTAMP() - INTERVAL 1 DAY"),
            'jobs_done_total' => $val("SELECT COUNT(*) FROM job_runs WHERE status = 'done'"),
            'trainings_done_24h' => $val("SELECT COUNT(*) FROM training_queue WHERE status = 'done' AND finished_at >= UTC_TIMESTAMP() - INTERVAL 1 DAY"),
            'trainings_done_total' => $val("SELECT COUNT(*) FROM training_queue WHERE status = 'done'"),
            'badges_earned' => $val('SELECT COUNT(*) FROM user_badges'),
        ];

        $worlds = [];
        foreach ($this->pdo->query(
            'SELECT w.ordinal, COUNT(*) AS n FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id
             GROUP BY w.ordinal ORDER BY w.ordinal'
        )->fetchAll() as $row) {
            $worlds[] = ['world' => (int) $row['ordinal'], 'players' => (int) $row['n']];
        }

        return [
            'generated_at' => Clock::iso(Clock::fmt($now)),
            'accounts' => $accounts,
            'activity' => $activity,
            'economy' => $economy,
            'worlds' => $worlds,
            'registrations' => $this->series('users', 'created_at'),
            'logins' => $this->series('login_events', 'logged_in_at'),
        ];
    }

    /**
     * Liczba zdarzen na dobe (czas polski) z ostatnich SERIES_DAYS dni, wraz z dniami bez zdarzen.
     *
     * @return list<array{day:string,count:int}>
     */
    private function series(string $table, string $column): array
    {
        $tz = new DateTimeZone(Clock::GAME_TZ);
        $days = [];
        $end = new DateTimeImmutable('today', $tz);
        for ($i = self::SERIES_DAYS - 1; $i >= 0; $i--) {
            $days[$end->modify("-$i days")->format('Y-m-d')] = 0;
        }
        $from = $end->modify('-' . (self::SERIES_DAYS - 1) . ' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("SELECT $column AS t FROM $table WHERE $column >= ?");
        $stmt->execute([$from]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
            $day = Clock::gameDay(Clock::parse((string) $t));
            if (isset($days[$day])) {
                $days[$day]++;
            }
        }
        $out = [];
        foreach ($days as $day => $count) {
            $out[] = ['day' => $day, 'count' => $count];
        }
        return $out;
    }
}
