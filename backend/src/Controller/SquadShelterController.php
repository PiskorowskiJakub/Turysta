<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Squad\SquadShelter;

/** API schroniska oddzialu (magazyn, wplaty, rozbudowa pomieszczen) - tylko dla czlonkow. */
final class SquadShelterController
{
    public function __construct(private SquadShelter $shelter)
    {
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function show(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['shelter' => $this->shelter->view($user, $this->id($params))]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deposit(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $coins = $r->body['coins'] ?? null;
        $this->shelter->deposit($user, $id, is_int($coins) ? $coins : (is_string($coins) && ctype_digit($coins) ? (int) $coins : 0));
        return [200, ['shelter' => $this->shelter->view($user, $id)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function upgrade(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $id = $this->id($params);
        $this->shelter->upgrade($user, $id, (string) ($r->body['room'] ?? ''));
        return [200, ['shelter' => $this->shelter->view($user, $id)]];
    }

    /** @param array<string,string> $params */
    private function id(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono oddziału.');
        }
        return $id;
    }
}
