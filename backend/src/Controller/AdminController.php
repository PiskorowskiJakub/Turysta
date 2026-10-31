<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Admin\AdminAudit;
use Turysta\Admin\AdminLogService;
use Turysta\Admin\AdminStatsService;
use Turysta\Admin\AdminUserService;
use Turysta\Admin\FactService;
use Turysta\Auth\Session;
use Turysta\Game\AnnouncementService;
use Turysta\Http\ApiException;
use Turysta\Http\Request;

/** Panel administratora: statystyki, gracze, logi i tresci (ogloszenia, ciekawostki). Kazda akcja wymaga roli admin, a zmiany trafiaja do dziennika. */
final class AdminController
{
    private AnnouncementService $announcements;
    private FactService $facts;
    private AdminUserService $users;
    private AdminLogService $logs;
    private AdminStatsService $stats;

    public function __construct(private PDO $pdo)
    {
        $this->announcements = new AnnouncementService($pdo);
        $this->facts = new FactService($pdo);
        $this->users = new AdminUserService($pdo);
        $this->logs = new AdminLogService($pdo);
        $this->stats = new AdminStatsService($pdo);
    }

    // ---- statystyki ----

    /** @return array{0:int,1:array<string,mixed>} */
    public function stats(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, $this->stats->overview()];
    }

    // ---- gracze ----

    /** @return array{0:int,1:array<string,mixed>} */
    public function users(Request $r): array
    {
        Session::requireAdmin($r);
        $q = $r->query;
        return [200, $this->users->list(
            (int) ($q['page'] ?? 1),
            $this->str($q['q'] ?? ''),
            $this->str($q['status'] ?? ''),
            $this->str($q['role'] ?? ''),
            $this->str($q['sort'] ?? '')
        )];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function user(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        return [200, ['user' => $this->users->detail($this->id($params))]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function updateUser(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $changes = $this->users->update((int) $admin['id'], $id, $r->body);
        if ($changes) {
            AdminAudit::log($this->pdo, (int) $admin['id'], 'user.update', $id, $changes, $r->ip());
        }
        return [200, ['user' => $this->users->detail($id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function setStatus(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $action = $this->str($r->body['action'] ?? '');
        $details = $this->users->setStatus((int) $admin['id'], $id, $action, $this->str($r->body['reason'] ?? ''), (int) ($r->body['days'] ?? 0));
        AdminAudit::log($this->pdo, (int) $admin['id'], 'user.' . $action, $id, $details, $r->ip());
        return [200, ['user' => $this->users->detail($id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function adjustWallet(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $amount = $r->body['amount'] ?? 0;
        $details = $this->users->adjustWallet(
            (int) $admin['id'],
            $id,
            $this->str($r->body['currency'] ?? ''),
            is_int($amount) ? $amount : 0,
            $this->str($r->body['reason'] ?? '')
        );
        AdminAudit::log($this->pdo, (int) $admin['id'], 'user.wallet', $id, $details, $r->ip());
        return [200, ['user' => $this->users->detail($id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function logoutUser(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $this->users->user($id);
        $closed = $this->users->endSessions($id);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'user.logout', $id, ['sessions' => $closed], $r->ip());
        return [200, ['user' => $this->users->detail($id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function userLogins(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        $id = $this->id($params);
        $this->users->user($id);
        return [200, $this->logs->logins((int) ($r->query['page'] ?? 1), $id, '')];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function userLedger(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        $id = $this->id($params);
        $this->users->user($id);
        return [200, $this->logs->ledger($id, (int) ($r->query['page'] ?? 1), $this->str($r->query['currency'] ?? ''))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function userAudit(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        $id = $this->id($params);
        $this->users->user($id);
        return [200, $this->logs->audit((int) ($r->query['page'] ?? 1), $id, '')];
    }

    // ---- logi globalne ----

    /** @return array{0:int,1:array<string,mixed>} */
    public function logins(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, $this->logs->logins((int) ($r->query['page'] ?? 1), null, $this->str($r->query['q'] ?? ''))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function audit(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, $this->logs->audit((int) ($r->query['page'] ?? 1), null, $this->str($r->query['action'] ?? ''))];
    }

    // ---- ogloszenia ----

    /** @return array{0:int,1:array<string,mixed>} */
    public function announcements(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, ['announcements' => $this->announcements->all()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function createAnnouncement(Request $r): array
    {
        $admin = Session::requireAdmin($r);
        $a = $this->announcements->create((int) $admin['id'], $r->body);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'announcement.create', null, ['id' => $a['id'], 'title' => $a['title']], $r->ip());
        return [201, ['announcement' => $a]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function updateAnnouncement(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $a = $this->announcements->update($this->id($params), $r->body);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'announcement.update', null, ['id' => $a['id'], 'title' => $a['title']], $r->ip());
        return [200, ['announcement' => $a]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deleteAnnouncement(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $this->announcements->delete($id);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'announcement.delete', null, ['id' => $id], $r->ip());
        return [200, ['message' => 'Ogłoszenie usunięto.']];
    }

    // ---- ciekawostki dnia ----

    /** @return array{0:int,1:array<string,mixed>} */
    public function facts(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, ['facts' => $this->facts->all()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function createFact(Request $r): array
    {
        $admin = Session::requireAdmin($r);
        $f = $this->facts->create($r->body);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'fact.create', null, ['id' => $f['id']], $r->ip());
        return [201, ['fact' => $f]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function updateFact(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $f = $this->facts->update($this->id($params), $r->body);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'fact.update', null, ['id' => $f['id']], $r->ip());
        return [200, ['fact' => $f]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deleteFact(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $this->facts->delete($id);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'fact.delete', null, ['id' => $id], $r->ip());
        return [200, ['message' => 'Ciekawostkę usunięto.']];
    }

    /** @param array<string,string> $params */
    private function id(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono.');
        }
        return $id;
    }

    private function str(mixed $v): string
    {
        return is_string($v) ? $v : '';
    }
}
