<?php
declare(strict_types=1);

namespace Turysta\Auth;

use DateTimeZone;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;

/** Blokada i zawieszenie konta: wspolna kontrola przy logowaniu i przy kazdym zadaniu zalogowanego gracza. */
final class AccountStatus
{
    /**
     * Rzuca 403, gdy konto jest zablokowane albo zawieszone (z powodem i data konca zawieszenia).
     *
     * @param array<string,mixed> $user wiersz users
     */
    public static function assertCanPlay(array $user): void
    {
        $reason = trim((string) ($user['status_reason'] ?? ''));
        $reasonText = $reason !== '' ? " Powód: $reason." : '';

        if ($user['status'] === 'banned') {
            throw new ApiException(403, 'banned', 'To konto zostało zablokowane.' . $reasonText);
        }
        $until = $user['suspended_until'] ?? null;
        if ($until !== null && Clock::parse((string) $until) > new \DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            $when = Clock::parse((string) $until)->setTimezone(new DateTimeZone(Clock::GAME_TZ))->format('d.m.Y H:i');
            throw new ApiException(403, 'suspended', "Konto jest zawieszone do $when." . $reasonText);
        }
    }
}
