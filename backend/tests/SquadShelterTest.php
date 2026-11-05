<?php
declare(strict_types=1);

namespace Turysta\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Squad\SquadChronicle;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadShelter;

/** Schronisko oddzialow (Etap 3): koszty bez maksymalnego poziomu, magazyn, wplaty, budowa i efekty (prawdziwy MySQL). */
final class SquadShelterTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private SquadService $squads;
    private SquadShelter $shelter;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM squad_rooms LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $this->auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->squads = new SquadService($this->pdo, new RateLimiter($this->pdo));
        $this->shelter = new SquadShelter($this->pdo, $this->squads);
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> */
    private function user(string $prefix): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.8.' . random_int(1, 250) . '.' . random_int(1, 250));
        $id = (int) $created['id'];
        $this->pdo->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP(), created_at = UTC_TIMESTAMP() - INTERVAL 10 DAY WHERE id = ?')->execute([$id]);
        $this->pdo->prepare('UPDATE wallets SET coins_cents = 100000 WHERE user_id = ?')->execute([$id]);
        return $this->pdo->query("SELECT * FROM users WHERE id = $id")->fetch();
    }

    private function coins(int $userId): int
    {
        return (int) $this->pdo->query("SELECT coins_cents FROM wallets WHERE user_id = $userId")->fetchColumn();
    }

    private function at(string $day, string $time = '10:00:00'): DateTimeImmutable
    {
        return new DateTimeImmutable("$day $time", new DateTimeZone('UTC'));
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>,2:int} przywodca, czlonek, id oddzialu */
    private function squad(): array
    {
        $lead = $this->user('SchLid');
        $mem = $this->user('SchCz');
        $id = $this->squads->create($lead, ['name' => "Schron {$this->suffix}", 'specialization' => 'gorska', 'join_mode' => 'open'], '10.8.1.1')['id'];
        $this->squads->join($mem, $id);
        return [$lead, $mem, $id];
    }

    public function testCostsGrowWithoutLimitAndEffectsSaturate(): void
    {
        $prev = SquadShelter::cost('kitchen', 1);
        $this->assertSame(['ember' => 240, 'stamps' => 6, 'cents' => 60000], $prev);
        for ($level = 2; $level <= 60; $level++) {
            $cost = SquadShelter::cost('kitchen', $level);
            $this->assertGreaterThan($prev['ember'], $cost['ember']);
            $this->assertGreaterThan($prev['stamps'], $cost['stamps']);
            $this->assertGreaterThan($prev['cents'], $cost['cents']);
            $this->assertGreaterThan(SquadShelter::buildHours($level - 1), SquadShelter::buildHours($level));
            $prev = $cost;
        }
        $this->assertSame(360, SquadShelter::cost('memory', 1)['cents'] / 100, 'Izba pamięci ma 60% ceny.');
        $this->assertNull(SquadShelter::effectPercent('pantry', 5));
        $this->assertSame(0.0, SquadShelter::effectPercent('kitchen', 0));
        $this->assertSame(2.5, SquadShelter::effectPercent('kitchen', 7));
        $this->assertLessThan(5.0, SquadShelter::effectPercent('kitchen', 1000));
        $this->assertSame(12.5, SquadShelter::effectPercent('mapping', 10));
        // Limit wplat rosnie z cenami: przy poziomie 1 to 9 dk, potem wiecej.
        $zero = array_fill_keys(['kitchen', 'pantry', 'infirmary', 'mapping', 'memory'], 0);
        $this->assertSame(900, SquadShelter::depositLimitCents($zero));
        $this->assertGreaterThan(900, SquadShelter::depositLimitCents(array_fill_keys(array_keys($zero), 20)));
    }

    public function testDepositLimitAndVaultCap(): void
    {
        [$lead, $mem, $id] = $this->squad();
        $now = $this->at('2026-03-10');
        $before = $this->coins((int) $mem['id']);

        $this->shelter->deposit($mem, $id, 5, $now);
        $this->assertSame($before - 500, $this->coins((int) $mem['id']));
        $view = $this->shelter->view($mem, $id, $now);
        $this->assertSame(500, $view['vault']['cents']);
        $this->assertSame(400, $view['deposit']['remaining_cents']);

        try {
            $this->shelter->deposit($mem, $id, 5, $now);
            $this->fail('Dzienny limit wpłat powinien zadziałać.');
        } catch (ApiException $e) {
            $this->assertSame('deposit_limit', $e->errorCode);
        }
        // Kolejnego dnia limit sie odnawia.
        $this->shelter->deposit($mem, $id, 9, $this->at('2026-03-11'));
        $this->assertSame(1400, $this->shelter->view($lead, $id, $this->at('2026-03-11'))['vault']['cents']);

        try {
            $this->shelter->deposit($mem, $id, 0, $now);
            $this->fail('Wpłata 0 dk jest nieprawidłowa.');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->status);
        }

        // Magazyn pelny (najdrozsza kolejna rozbudowa to 600 dk): dalsze wplaty sa odrzucane.
        $this->pdo->prepare('UPDATE squads SET vault_cents = 60000 WHERE id = ?')->execute([$id]);
        try {
            $this->shelter->deposit($lead, $id, 1, $this->at('2026-03-12'));
            $this->fail('Pełny magazyn nie przyjmuje dukatów.');
        } catch (ApiException $e) {
            $this->assertSame('vault_full', $e->errorCode);
        }
    }

    public function testDepositRecordGoesToChronicle(): void
    {
        [$lead, $mem, $id] = $this->squad();
        $this->pdo->prepare('UPDATE squads SET vault_cents = 0 WHERE id = ?')->execute([$id]);
        $day = $this->at('2026-03-15');
        $this->shelter->deposit($lead, $id, 9, $day);
        $this->assertNotContains('deposit_record', array_column(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), 'type'), 'Poniżej 50 dk nie ma rekordu.');
        $this->shelter->deposit($mem, $id, 9, $day);
        $this->assertSame(0, (int) $this->pdo->query("SELECT deposit_record_cents FROM squads WHERE id = $id")->fetchColumn());
        // Rekord liczy się dopiero od 50 dk w ciągu dnia, więc dokładamy kolejne osoby (6 x 9 dk = 54 dk).
        $extra = $this->user('SchEx');
        $this->squads->join($extra, $id);
        for ($i = 0; $i < 3; $i++) {
            $u = $this->user('SchX' . $i);
            $this->squads->join($u, $id);
            $this->shelter->deposit($u, $id, 9, $day);
        }
        $this->shelter->deposit($extra, $id, 9, $day);
        $types = array_column(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), 'type');
        $this->assertContains('deposit_record', $types);
        $this->assertGreaterThanOrEqual(5000, (int) $this->pdo->query("SELECT deposit_record_cents FROM squads WHERE id = $id")->fetchColumn());
    }

    public function testUpgradeFlow(): void
    {
        [$lead, $mem, $id] = $this->squad();
        $start = $this->at('2026-03-20', '08:00:00');

        try {
            $this->shelter->upgrade($mem, $id, 'mapping', $start);
            $this->fail('Zwykły członek nie może rozbudowywać.');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->status);
        }
        try {
            $this->shelter->upgrade($lead, $id, 'mapping', $start);
            $this->fail('Bez składników nie można rozbudować.');
        } catch (ApiException $e) {
            $this->assertSame('not_enough_resources', $e->errorCode);
        }

        $this->pdo->prepare('UPDATE squads SET ember = 5000, ember_spent = 0, stamps = 20, vault_cents = 200000 WHERE id = ?')->execute([$id]);
        $this->shelter->upgrade($lead, $id, 'mapping', $start);
        $view = $this->shelter->view($lead, $id, $start);
        $this->assertSame(5000 - 240, $view['vault']['ember']);
        $this->assertSame(14, $view['vault']['stamps']);
        $this->assertSame(200000 - 60000, $view['vault']['cents']);
        $this->assertNotNull($view['build']);
        $this->assertSame(0, array_column($view['rooms'], 'level', 'code')['mapping'], 'Poziom przybywa dopiero po zakończeniu budowy.');

        try {
            $this->shelter->upgrade($lead, $id, 'kitchen', $start);
            $this->fail('Jedna budowa naraz.');
        } catch (ApiException $e) {
            $this->assertSame('build_in_progress', $e->errorCode);
        }

        // Poziom 1 buduje sie 12 godzin.
        $this->assertSame(0, SquadShelter::finalizeDue($this->pdo, $this->at('2026-03-20', '19:00:00'), $id));
        $this->assertSame(1, SquadShelter::finalizeDue($this->pdo, $this->at('2026-03-20', '21:00:00'), $id));
        $after = $this->shelter->view($lead, $id, $this->at('2026-03-20', '21:00:00'));
        $this->assertNull($after['build']);
        $this->assertSame(1, array_column($after['rooms'], 'level', 'code')['mapping']);
        $types = array_column(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), 'type');
        $this->assertContains('shelter_start', $types);
        $this->assertContains('shelter_done', $types);

        // Kolejny poziom Mapowni jest drozszy i dluzszy.
        $next = array_column($after['rooms'], null, 'code')['mapping']['next'];
        $this->assertGreaterThan(240, $next['ember']);
        $this->assertSame(24, $next['hours']);
    }

    public function testKitchenBonusAndMappingStamps(): void
    {
        [$lead, $mem, $id] = $this->squad();
        $this->assertSame(0, SquadShelter::kitchenBonusCents($this->pdo, (int) $mem['id'], 10000), 'Bez poziomu Kuchni nie ma premii.');
        $this->pdo->prepare("INSERT INTO squad_rooms (squad_id, room, level) VALUES (?, 'kitchen', 7)")->execute([$id]);
        $this->assertSame(250, SquadShelter::kitchenBonusCents($this->pdo, (int) $mem['id'], 10000));
        $before = $this->coins((int) $mem['id']);
        $this->assertSame(250, SquadShelter::payKitchenBonus($this->pdo, (int) $mem['id'], 10000, 0));
        $this->assertSame($before + 250, $this->coins((int) $mem['id']));

        // Mapownia poziom 10 = +12,5% pieczatek; ulamki sumuja sie miedzy wyprawami.
        $this->pdo->prepare("INSERT INTO squad_rooms (squad_id, room, level) VALUES (?, 'mapping', 10)")->execute([$id]);
        $this->assertSame(0, SquadShelter::mappingExtraStamps($this->pdo, $id, 4));
        $this->assertSame(0, SquadShelter::mappingExtraStamps($this->pdo, $id, 3));
        $this->assertSame(1, SquadShelter::mappingExtraStamps($this->pdo, $id, 4), '0,5 + 0,375 + 0,5 = 1,375');
    }
}
