<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\AccountService;
use Turysta\Auth\Session;
use Turysta\Http\Request;

final class AccountController
{
    public function __construct(private AccountService $account, private MeController $me)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function updateProfile(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->updateProfile($user, $r->body['gender'] ?? null, $r->body['avatar_id'] ?? null);
        return $this->me->show($r);
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function updatePrivacy(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->setPrivacy($user, $r->body['share_activity'] ?? null);
        return $this->me->show($r);
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function changeEmail(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->changeEmail($user, $r->input('current_password'), $r->input('email'), $r->ip());
        return $this->me->show($r);
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function resendVerification(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->resendVerification($user, $r->ip());
        return [200, ['message' => 'Wysłaliśmy nowy link potwierdzający na Twój adres e-mail.']];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function deleteAccount(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->deleteAccount($user, $r->input('current_password'), $r->ip());
        Session::destroy();
        return [200, ['message' => 'Konto zostało usunięte.']];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function changePassword(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->account->changePassword(
            $user,
            $r->input('current_password'),
            $r->input('password'),
            $r->input('password_confirm'),
            $r->ip(),
            session_id() ?: null
        );
        return [200, ['message' => 'Hasło zostało zmienione. Pozostałe sesje zostały wylogowane.']];
    }
}
