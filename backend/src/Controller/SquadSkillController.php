<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\Request;
use Turysta\Squad\SquadSkills;

/** API umiejetnosci oddzialowych gracza (osobny slot treningowy). */
final class SquadSkillController
{
    public function __construct(private SquadSkills $skills)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function show(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['squad_skills' => $this->skills->state((int) $user['id'])]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function train(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['squad_skills' => $this->skills->train((int) $user['id'], (string) ($r->body['skill'] ?? ''))]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function cancel(Request $r, array $params = []): array
    {
        $user = Session::requireUser($r);
        return [200, ['squad_skills' => $this->skills->cancel((int) $user['id'], (int) ($params['id'] ?? 0))]];
    }
}
