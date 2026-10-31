<?php
declare(strict_types=1);

namespace Turysta\Auth;

final class Validator
{
    /** @return array<string,string> bledy pol (pusta tablica = OK) */
    public static function registration(string $name, string $email, string $password, string $confirm): array
    {
        $errors = [];

        $len = mb_strlen($name);
        if ($len < 3 || $len > 20) {
            $errors['character_name'] = 'Nazwa postaci musi mieć od 3 do 20 znaków.';
        } elseif (!preg_match('/^[\p{L}\p{N}_\- ]+$/u', $name) || str_contains($name, '  ')) {
            $errors['character_name'] = 'Dozwolone są litery, cyfry, spacja, myślnik i podkreślenie.';
        }

        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Podaj poprawny adres e-mail.';
        }

        if (mb_strlen($password) < 10) {
            $errors['password'] = 'Hasło musi mieć co najmniej 10 znaków.';
        } elseif (strlen($password) > 128) {
            $errors['password'] = 'Hasło może mieć najwyżej 128 znaków.';
        }

        if (!isset($errors['password']) && $password !== $confirm) {
            $errors['password_confirm'] = 'Hasła muszą być takie same.';
        }

        return $errors;
    }

    /** @return array<string,string> */
    public static function email(string $email): array
    {
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['email' => 'Podaj poprawny adres e-mail.'];
        }
        return [];
    }

    /** @return array<string,string> */
    public static function newPassword(string $password, string $confirm): array
    {
        $errors = [];
        if (mb_strlen($password) < 10) {
            $errors['password'] = 'Hasło musi mieć co najmniej 10 znaków.';
        } elseif (strlen($password) > 128) {
            $errors['password'] = 'Hasło może mieć najwyżej 128 znaków.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Hasła muszą być takie same.';
        }
        return $errors;
    }
}
