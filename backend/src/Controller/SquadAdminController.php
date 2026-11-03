<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Admin\AdminAudit;
use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Squad\SquadAdminService;

/** Panel administratora: moderacja oddzialow. Kazda akcja wymaga roli admin, a zmiany trafiaja do dziennika. */
final class SquadAdminController
{
    public function __construct(private SquadAdminService $service, private PDO $pdo)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function list(Request $r): array
    {
        Session::requireAdmin($r);
        $q = $r->query;
        return [200, $this->service->list(
            (int) ($q['page'] ?? 1),
            $this->str($q['q'] ?? ''),
            $this->str($q['sort'] ?? ''),
            $this->str($q['dir'] ?? ''),
            $this->str($q['state'] ?? '')
        )];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function show(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        return [200, $this->service->show($this->id($params))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function forceName(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $this->service->forceName($id);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'squad.force_name', null, ['squad_id' => $id], $r->ip());
        return [200, $this->service->show($id)];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function lockName(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $locked = ($r->body['locked'] ?? null) === true;
        $this->service->lockName($id, $locked);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'squad.lock_name', null, ['squad_id' => $id, 'locked' => $locked], $r->ip());
        return [200, $this->service->show($id)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function reports(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, $this->service->reports((int) ($r->query['page'] ?? 1), $this->str($r->query['status'] ?? ''))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function setReportStatus(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $status = $this->str($r->body['status'] ?? '');
        $this->service->setReportStatus($id, $status);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'squad.report_status', null, ['report_id' => $id, 'status' => $status], $r->ip());
        return [200, ['message' => 'Status zapisany.']];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function bannedWords(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, $this->service->bannedWords()];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function addBannedWord(Request $r): array
    {
        $admin = Session::requireAdmin($r);
        $key = $this->service->addBannedWord($this->str($r->body['word'] ?? ''), (int) $admin['id']);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'bannedword.add', null, ['word' => $key], $r->ip());
        return [201, $this->service->bannedWords()];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deleteBannedWord(Request $r, array $params = []): array
    {
        $admin = Session::requireAdmin($r);
        $id = $this->id($params);
        $this->service->deleteBannedWord($id);
        AdminAudit::log($this->pdo, (int) $admin['id'], 'bannedword.delete', null, ['id' => $id], $r->ip());
        return [200, $this->service->bannedWords()];
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
        return is_string($v) ? trim($v) : '';
    }
}
