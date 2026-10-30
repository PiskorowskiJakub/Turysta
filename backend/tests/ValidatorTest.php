<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Turysta\Auth\Validator;

final class ValidatorTest extends TestCase
{
    public function testValidRegistrationHasNoErrors(): void
    {
        $this->assertSame([], Validator::registration('Jan Kowalski', 'jan@example.com', 'bardzo-dlugie-haslo', 'bardzo-dlugie-haslo'));
    }

    public function testShortNameAndPasswordAreRejected(): void
    {
        $errors = Validator::registration('ab', 'jan@example.com', 'krotkie', 'krotkie');
        $this->assertArrayHasKey('character_name', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordMismatchAndBadEmail(): void
    {
        $errors = Validator::registration('Turysta', 'nie-email', 'bardzo-dlugie-haslo', 'inne-dlugie-haslo');
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password_confirm', $errors);
    }

    public function testPolishLettersInNameAreAllowed(): void
    {
        $this->assertSame([], Validator::registration('Żółw_Łukasz', 'a@b.pl', '1234567890', '1234567890'));
    }
}
