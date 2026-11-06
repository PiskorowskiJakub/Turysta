<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadView;

/** API oddzialow dla graczy. */
final class SquadController
{
    public function __construct(private SquadService $squads, private SquadView $view)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function catalog(Request $r): array
    {
        Session::requireUser($r);
        return [200, $this->view->catalog($r->query) + ['meta' => $this->view->meta()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function mine(Request $r): array
    {
        $user = Session::requireUser($r);
        return [200, $this->view->mine($user)];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function show(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, $this->view->profile($user, $this->id($params))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function create(Request $r): array
    {
        $user = Session::requireUser($r);
        $created = $this->squads->create($user, $r->body, $r->ip());
        return [201, $created + $this->view->mine($this->fresh($user))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function join(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $result = $this->squads->join($user, $this->id($params));
        return [200, ['result' => $result] + $this->view->mine($this->fresh($user))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function dropRequest(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->squads->dropOwnRequest((int) $user['id'], $this->id($params));
        return [200, $this->view->mine($user)];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function acceptInvite(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->squads->acceptInvite($user, $this->id($params));
        return [200, $this->view->mine($this->fresh($user))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function leave(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->squads->leave($user);
        return [200, $this->view->mine($this->fresh($user))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function invite(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $result = $this->squads->invite($user, $id, $r->body);
        return [201, $result + ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function approve(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->approveRequest($user, $id, $this->uid($params));
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function dismiss(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->dismissRequest($user, $id, $this->uid($params));
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function update(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->update($user, $id, $r->body);
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * Ksiega wypraw (tylko dla czlonkow oddzialu).
     *
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function book(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        return [200, ['book' => $this->squads->book($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function setRole(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->setRole($user, $id, $this->uid($params), $r->body);
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function kick(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->kick($user, $id, $this->uid($params), $r->body);
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function transfer(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->squads->transfer($user, $id, $r->body);
        return [200, ['squad' => $this->view->profile($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function disband(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->squads->disband($user, $this->id($params));
        return [200, $this->view->mine($this->fresh($user))];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function report(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->squads->reportName($user, $this->id($params), $r->body, $r->ip());
        return [201, ['message' => 'Dziękujemy! Zgłoszenie trafiło do administratora.']];
    }

    /** Aktualny wiersz gracza po zmianie czlonkostwa (squad_left_at). @param array<string,mixed> $user @return array<string,mixed> */
    private function fresh(array $user): array
    {
        $stmt = \Turysta\Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        return $stmt->fetch() ?: $user;
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

    /** @param array<string,string> $params */
    private function uid(array $params): int
    {
        $id = (int) ($params['uid'] ?? 0);
        if ($id < 1) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono.');
        }
        return $id;
    }
}
