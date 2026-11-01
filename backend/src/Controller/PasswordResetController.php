<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\PasswordResetService;
use Turysta\Http\Request;

final class PasswordResetController
{
    public function __construct(private PasswordResetService $reset)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function forgot(Request $r): array
    {
        $this->reset->request($r->input('email'), $r->ip());
        return [200, ['message' => 'Jeśli konto o tym adresie istnieje, wysłaliśmy na nie link do ustawienia nowego hasła.']];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function reset(Request $r): array
    {
        $this->reset->reset($r->input('token'), $r->input('password'), $r->input('password_confirm'), $r->ip());
        return [200, ['message' => 'Hasło zostało ustawione. Możesz się zalogować.']];
    }
}
