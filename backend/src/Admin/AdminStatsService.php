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
            'gifts_total' => $val('SELECT COUNT(*) FROM friend_gifts'),
            'gifts_24h' => $val('SELECT COUNT(*) FROM friend_gifts WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 1 DAY'),
            'gifts_cents_total' => $val('SELECT COALESCE(SUM(cents), 0) FROM friend_gifts'),
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
    private function series(string $table, string $column, int $span = self::SERIES_DAYS): array
    {
        $tz = new DateTimeZone(Clock::GAME_TZ);
        $days = [];
        $end = new DateTimeImmutable('today', $tz);
        for ($i = $span - 1; $i >= 0; $i--) {
            $days[$end->modify("-$i days")->format('Y-m-d')] = 0;
        }
        $from = $end->modify('-' . ($span - 1) . ' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

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

    public const HOUR_SPANS = [24, 48, 72, 168];
    public const DAY_SPANS = [7, 30, 90, 180, 365];

    /**
     * Szereg czasowy aktywnosci: co godzine (ostatnie 24-168 h, klucze to poczatek godziny UTC) albo co dobe
     * (ostatnie 7-365 dni wg czasu gry). Kazdy punkt ma liczbe aktywnych graczy, logowan i nowych kont.
     *
     * @return array<string,mixed>
     */
    public function activity(string $view, int $span, string $group = 'day'): array
    {
        if ($view === 'hours') {
            $span = in_array($span, self::HOUR_SPANS, true) ? $span : 24;
            return $this->hourly($span);
        }
        $span = in_array($span, self::DAY_SPANS, true) ? $span : 30;
        return $this->daily($span, in_array($group, ['day', 'week', 'month'], true) ? $group : 'day');
    }

    /** @return array<string,mixed> */
    private function hourly(int $hours): array
    {
        $end = new DateTimeImmutable(gmdate('Y-m-d H:00:00'), new DateTimeZone('UTC'));
        $keys = [];
        for ($i = $hours - 1; $i >= 0; $i--) {
            $keys[$end->modify("-$i hours")->format('Y-m-d H:00:00')] = ['active' => 0, 'logins' => 0, 'registrations' => 0];
        }
        $from = array_key_first($keys);

        $fill = function (string $sql, string $field) use (&$keys, $from): void {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$from]);
            foreach ($stmt->fetchAll() as $r) {
                if (isset($keys[$r['h']])) {
                    $keys[$r['h']][$field] = (int) $r['c'];
                }
            }
        };
        $fill('SELECT hour AS h, COUNT(*) AS c FROM user_active_hours WHERE hour >= ? GROUP BY hour', 'active');
        $fill("SELECT DATE_FORMAT(logged_in_at, '%Y-%m-%d %H:00:00') AS h, COUNT(*) AS c FROM login_events WHERE logged_in_at >= ? GROUP BY h", 'logins');
        $fill("SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS h, COUNT(*) AS c FROM users WHERE created_at >= ? GROUP BY h", 'registrations');

        $since = $this->pdo->query('SELECT MIN(hour) FROM user_active_hours')->fetchColumn();
        $points = [];
        foreach ($keys as $h => $v) {
            $points[] = ['key' => Clock::iso($h)] + $v;
        }
        return [
            'view' => 'hours',
            'span' => $hours,
            'tracking_since' => $since ? Clock::iso((string) $since) : null,
            'points' => $points,
        ];
    }

    /**
     * Szereg dzienny; przy $group = week lub month punkty to tygodnie (od poniedzialku) albo miesiace.
     * Aktywni gracze w grupie to unikalni gracze, a logowania i nowe konta sa sumowane.
     *
     * @return array<string,mixed>
     */
    private function daily(int $days, string $group): array
    {
        $tz = new DateTimeZone(Clock::GAME_TZ);
        $end = new DateTimeImmutable('today', $tz);
        $bucket = static function (string $day) use ($group): string {
            $d = new DateTimeImmutable($day);
            if ($group === 'week') {
                return $d->modify('monday this week')->format('Y-m-d');
            }
            return $group === 'month' ? $d->format('Y-m-01') : $day;
        };
        $keys = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $keys[$bucket($end->modify("-$i days")->format('Y-m-d'))] = ['active' => 0, 'logins' => 0, 'registrations' => 0];
        }
        $from = $end->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $sqlBucket = match ($group) {
            'week' => 'DATE_SUB(day, INTERVAL WEEKDAY(day) DAY)',
            'month' => "DATE_FORMAT(day, '%Y-%m-01')",
            default => 'day',
        };
        $stmt = $this->pdo->prepare("SELECT $sqlBucket AS b, COUNT(DISTINCT user_id) AS c FROM user_active_days WHERE day >= ? GROUP BY b");
        $stmt->execute([$from]);
        foreach ($stmt->fetchAll() as $r) {
            if (isset($keys[$r['b']])) {
                $keys[$r['b']]['active'] = (int) $r['c'];
            }
        }
        foreach (['logins' => ['login_events', 'logged_in_at'], 'registrations' => ['users', 'created_at']] as $field => [$table, $column]) {
            foreach ($this->series($table, $column, $days) as $d) {
                $k = $bucket($d['day']);
                if (isset($keys[$k])) {
                    $keys[$k][$field] += $d['count'];
                }
            }
        }
        $since = $this->pdo->query('SELECT MIN(day) FROM user_active_days')->fetchColumn();
        $points = [];
        foreach ($keys as $day => $v) {
            $points[] = ['key' => (string) $day] + $v;
        }
        return [
            'view' => 'days',
            'span' => $days,
            'group' => $group,
            'tracking_since' => $since ? (string) $since : null,
            'points' => $points,
        ];
    }
}
