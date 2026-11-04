<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Squad\SquadTrail;

/** API szlakow (tygodniowych wypraw) oddzialu - tylko dla czlonkow. */
final class SquadTrailController
{
    public function __construct(private SquadTrail $trails)
    {
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function show(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['trail' => $this->trails->view($user, $this->id($params))]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function join(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->trails->join($user, $id);
        return [200, ['trail' => $this->trails->view($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function march(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->trails->march($user, $id);
        return [200, ['trail' => $this->trails->view($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function difficulty(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->trails->setDifficulty($user, $id, $r->body);
        return [200, ['trail' => $this->trails->view($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function history(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['items' => $this->trails->history($user, $this->id($params))]];
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
}
