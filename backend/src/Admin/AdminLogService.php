<?php
declare(strict_types=1);

namespace Turysta\Admin;

use PDO;
use Turysta\Game\Clock;

/** Podglad logow: logowania, ksiega operacji portfela gracza i dziennik dzialan administratorow. */
final class AdminLogService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Logowania: wszystkie albo jednego gracza; q to fragment nazwy gracza lub adresu IP. @return array<string,mixed> */
    public function logins(int $page, ?int $userId, string $q): array
    {
        $where = [];
        $params = [];
        if ($userId !== null) {
            $where[] = 'e.user_id = ?';
            $params[] = $userId;
        }
        $q = trim(mb_substr($q, 0, 60));
        if ($q !== '') {
            $where[] = '(u.character_name LIKE ? OR e.ip LIKE ?)';
            $like = Paging::like($q);
            array_push($params, $like, $like);
        }
        $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = "FROM login_events e JOIN users u ON u.id = e.user_id$filter";

        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) $from",
            "SELECT e.id, e.user_id, u.character_name AS name, e.logged_in_at, e.logged_out_at, e.ip, e.user_agent $from ORDER BY e.id DESC",
            $params,
            $page,
            static fn(array $r): array => [
                'id' => (int) $r['id'],
                'user_id' => (int) $r['user_id'],
                'name' => (string) $r['name'],
                'logged_in_at' => Clock::iso((string) $r['logged_in_at']),
                'logged_out_at' => Clock::iso($r['logged_out_at']),
                'ip' => $r['ip'],
                'user_agent' => $r['user_agent'],
            ]
        );
    }

    /** @return array<string,mixed> */
    public function ledger(int $userId, int $page, string $currency): array
    {
        $params = [$userId];
        $filter = 'WHERE user_id = ?';
        if (in_array($currency, ['coins', 'tickets', 'points'], true)) {
            $filter .= ' AND currency = ?';
            $params[] = $currency;
        }
        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) FROM ledger_entries $filter",
            "SELECT id, currency, amount, balance_after, reason, created_at FROM ledger_entries $filter ORDER BY id DESC",
            $params,
            $page,
            static fn(array $r): array => [
                'id' => (int) $r['id'],
                'currency' => (string) $r['currency'],
                'amount' => (int) $r['amount'],
                'balance_after' => (int) $r['balance_after'],
                'reason' => (string) $r['reason'],
                'created_at' => Clock::iso((string) $r['created_at']),
            ]
        );
    }

    /** Dziennik dzialan administratorow; opcjonalnie tylko dotyczacy jednego gracza. @return array<string,mixed> */
    public function audit(int $page, ?int $targetUserId, string $action): array
    {
        $where = [];
        $params = [];
        if ($targetUserId !== null) {
            $where[] = 'a.target_user_id = ?';
            $params[] = $targetUserId;
        }
        $action = trim(mb_substr($action, 0, 40));
        if ($action !== '') {
            $where[] = 'a.action = ?';
            $params[] = $action;
        }
        $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = "FROM admin_audit a LEFT JOIN users adm ON adm.id = a.admin_id LEFT JOIN users tgt ON tgt.id = a.target_user_id$filter";

        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) $from",
            "SELECT a.id, a.action, a.admin_id, adm.character_name AS admin_name, a.target_user_id, tgt.character_name AS target_name,
                    a.details, a.ip, a.created_at $from ORDER BY a.id DESC",
            $params,
            $page,
            static fn(array $r): array => [
                'id' => (int) $r['id'],
                'action' => (string) $r['action'],
                'admin' => $r['admin_id'] !== null ? ['id' => (int) $r['admin_id'], 'name' => (string) $r['admin_name']] : null,
                'target' => $r['target_user_id'] !== null ? ['id' => (int) $r['target_user_id'], 'name' => (string) $r['target_name']] : null,
                'details' => $r['details'] !== null ? json_decode((string) $r['details'], true) : null,
                'ip' => $r['ip'],
                'created_at' => Clock::iso((string) $r['created_at']),
            ]
        );
    }
}
