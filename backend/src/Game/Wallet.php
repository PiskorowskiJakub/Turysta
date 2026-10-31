<?php
declare(strict_types=1);

namespace Turysta\Game;

use InvalidArgumentException;
use PDO;
use Turysta\Http\ApiException;

final class Wallet
{
    private const COLUMNS = ['coins' => 'coins_cents', 'tickets' => 'tickets', 'points' => 'points'];

    /** Zmiana salda + wpis w ksiedze. Wywolywac wewnatrz transakcji. Zwraca nowe saldo. */
    public static function apply(
        PDO $pdo,
        int $userId,
        string $currency,
        int $amount,
        string $reason,
        ?string $refType = null,
        ?int $refId = null,
        ?string $idempotencyKey = null
    ): int {
        $column = self::COLUMNS[$currency] ?? throw new InvalidArgumentException("Nieznana waluta: $currency");

        $stmt = $pdo->prepare("SELECT $column FROM wallets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $balance = (int) $stmt->fetchColumn();

        $new = $balance + $amount;
        if ($new < 0) {
            throw new ApiException(422, 'insufficient_funds', 'Nie masz wystarczająco dukatów.');
        }

        $pdo->prepare("UPDATE wallets SET $column = ? WHERE user_id = ?")->execute([$new, $userId]);
        $pdo->prepare(
            'INSERT INTO ledger_entries (user_id, currency, amount, balance_after, reason, ref_type, ref_id, idempotency_key)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $currency, $amount, $new, $reason, $refType, $refId, $idempotencyKey]);

        return $new;
    }

    /**
     * Wplyw dukatow ze zrodla zarobku (praca, a w przyszlosci m.in. sprzedaz, nagrody): saldo + ksiega + licznik w user_earnings.
     * Zwroty i premie startowe nie sa zarobkiem - te ida przez apply().
     * $source: np. "job:market"; nowe zrodla dopisuja sie same i wchodza do sumy w statystykach.
     */
    public static function earn(
        PDO $pdo,
        int $userId,
        string $source,
        int $cents,
        string $reason,
        ?string $refType = null,
        ?int $refId = null
    ): int {
        $new = self::apply($pdo, $userId, 'coins', $cents, $reason, $refType, $refId);
        $pdo->prepare(
            'INSERT INTO user_earnings (user_id, source, total_cents) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE total_cents = total_cents + VALUES(total_cents)'
        )->execute([$userId, $source, $cents]);
        return $new;
    }
}
