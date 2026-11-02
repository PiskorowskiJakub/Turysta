<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Social\ActivityFeed;
use Turysta\Social\CompareService;
use Turysta\Social\FriendService;
use Turysta\Social\GiftService;

final class FriendController
{
    public function __construct(
        private FriendService $friends,
        private CompareService $compare,
        private GiftService $gifts,
        private PDO $pdo
    ) {
    }

    /** @return array<string,mixed> lista znajomych razem ze stanem prezentow */
    private function view(int $userId): array
    {
        return $this->friends->overview($userId) + ['gifts' => $this->gifts->state($userId)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function list(Request $r): array
    {
        $user = Session::requireUser($r);
        return [200, $this->view((int) $user['id'])];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function request(Request $r): array
    {
        $user = Session::requireUser($r);
        $result = $this->friends->request($user, $r->body, $r->ip());
        return [201, $result + $this->view((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function accept(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->friends->accept($user, $this->id($params));
        return [200, $this->view((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function remove(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $this->friends->remove((int) $user['id'], $this->id($params));
        return [200, $this->view((int) $user['id'])];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function gift(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        $sent = $this->gifts->send($user, $this->id($params));
        return [201, ['gift' => $sent] + $this->view((int) $user['id'])];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function activity(Request $r): array
    {
        $user = Session::requireUser($r);
        return [200, ActivityFeed::forUser($this->pdo, (int) $user['id'])];
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
