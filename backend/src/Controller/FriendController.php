<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Social\CompareService;
use Turysta\Social\FriendService;

final class FriendController
{
    public function __construct(private FriendService $friends, private CompareService $compare)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function list(Request $r): array
    {
        $user = Session::requireUser($r);
        return [200, $this->friends->overview((int) $user['id'])];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function request(Request $r): array
    {
        $user = Session::requireUser($r);
        $result = $this->friends->request($user, $r->body, $r->ip());
        return [201, $result + $this->friends->overview((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function accept(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->friends->accept($user, $this->id($params));
        return [200, $this->friends->overview((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function remove(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->friends->remove((int) $user['id'], $this->id($params));
        return [200, $this->friends->overview((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function compare(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, $this->compare->compare((int) $user['id'], $this->id($params))];
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
