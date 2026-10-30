<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\AuthService;
use Turysta\Auth\Session;
use Turysta\Http\ApiException;
use Turysta\Http\Request;

final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function csrf(Request $r): array
    {
        Session::start($r);
        return [200, ['csrf_token' => Session::csrfToken()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function register(Request $r): array
    {
        $user = $this->auth->register($r->body, $r->ip());
        return [201, [
            'message' => 'Udało się stworzyć konto. Możesz się zalogować.',
            'user' => ['id' => $user['id'], 'character_name' => $user['character_name']],
        ]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function login(Request $r): array
    {
        $user = $this->auth->login($r->input('email'), $r->input('password'), $r->ip());
        $eventId = $this->auth->recordLogin((int) $user['id'], $r->ip(), $r->userAgent());
        Session::login((int) $user['id'], $eventId);
        return [200, [
            'csrf_token' => Session::csrfToken(),
            'user' => [
                'id' => (int) $user['id'],
                'character_name' => $user['character_name'],
                'tutorial_done' => $user['tutorial_done_at'] !== null,
            ],
        ]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function logout(Request $r): array
    {
        Session::start($r);
        if (!empty($_SESSION['login_event_id'])) {
            $this->auth->recordLogout((int) $_SESSION['login_event_id']);
        }
        Session::destroy();
        return [200, ['message' => 'Wylogowano.']];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function verifyEmail(Request $r): array
    {
        $token = (string) ($r->query['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new ApiException(400, 'invalid_token', 'Link jest nieprawidłowy lub wygasł.');
        }
        $this->auth->verifyEmail($token);
        return [200, ['message' => 'Adres e-mail został potwierdzony.']];
    }
}
