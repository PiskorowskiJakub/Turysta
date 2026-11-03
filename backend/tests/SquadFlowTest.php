<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Squad\NameFilter;
use Turysta\Squad\SquadAdminService;
use Turysta\Squad\SquadChronicle;
use Turysta\Squad\SquadFire;
use Turysta\Squad\SquadMaintenance;
use Turysta\Squad\SquadRules;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadView;

/** Oddzialy (Etap 1): zakladanie, czlonkostwo, role, ognisko, kronika i moderacja nazw (prawdziwy MySQL). */
final class SquadFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private SquadService $squads;
    private SquadView $view;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM squads LIMIT 1');
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
        $this->view = new SquadView($this->pdo, $this->squads);
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** Gracz z potwierdzonym e-mailem i kontem starszym niz minimalny staz. @return array<string,mixed> wiersz users */
    private function user(string $prefix, bool $eligible = true): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.9.' . random_int(1, 250) . '.' . random_int(1, 250));
        if ($eligible) {
            $this->pdo->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP(), created_at = UTC_TIMESTAMP() - INTERVAL 10 DAY WHERE id = ?')->execute([$created['id']]);
        }
        return $this->fresh((int) $created['id']);
    }

    /** @return array<string,mixed> */
    private function fresh(int $id): array
    {
        return $this->pdo->query("SELECT * FROM users WHERE id = $id")->fetch();
    }

    private function ip(): string
    {
        return '10.2.' . random_int(1, 250) . '.' . random_int(1, 250);
    }

    /** @param array<string,mixed> $leader */
    private function makeSquad(array $leader, string $mode = 'open', string $label = 'Test'): int
    {
        return $this->squads->create($leader, [
            'name' => "{$label} {$this->suffix}",
            'specialization' => 'gorska',
            'join_mode' => $mode,
        ], $this->ip())['id'];
    }

    private function role(int $userId): ?string
    {
        $r = $this->squads->memberRow($userId);
        return $r ? (string) $r['role'] : null;
    }

    public function testNameRules(): void
    {
        $this->assertNull(NameFilter::formatError('im. Jana Kochanowskiego'));
        $this->assertNotNull(NameFilter::formatError('ab'));
        $this->assertNotNull(NameFilter::formatError('Zły <znak>'));
        $this->assertNotNull(NameFilter::formatError('Oddział PTTK Gdańsk'));
        $this->assertSame(NameFilter::uniqueKey('Młodych  Odkrywców!'), NameFilter::uniqueKey('mlodych odkrywcow'));
        $this->assertSame('xkurwax', NameFilter::screenKey('X-kUrW4-x'));
        $this->assertSame('Oddział im. X – turystyka górska', SquadRules::displayName('im. X', 'gorska'));
        $this->assertSame('Oddział Y', SquadRules::displayName('Y', 'ogolna'));
    }

    public function testCreateRequiresEligibleAccount(): void
    {
        $young = $this->user('Mlody', false);
        try {
            $this->squads->create($young, ['name' => "Mlodzi {$this->suffix}", 'specialization' => 'piesza'], $this->ip());
            $this->fail('Niezweryfikowane konto nie powinno zalozyc oddzialu.');
        } catch (ApiException $e) {
            $this->assertSame('email_unverified', $e->errorCode);
        }

        $this->pdo->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$young['id']]);
        try {
            $this->squads->create($this->fresh((int) $young['id']), ['name' => "Mlodzi {$this->suffix}", 'specialization' => 'piesza'], $this->ip());
            $this->fail('Konto z 1. dnia gry nie powinno zalozyc oddzialu.');
        } catch (ApiException $e) {
            $this->assertSame('account_too_young', $e->errorCode);
        }

        // 3. dzien gry (dzien zalozenia = 1) wystarcza.
        $this->pdo->prepare('UPDATE users SET created_at = UTC_TIMESTAMP() - INTERVAL 2 DAY WHERE id = ?')->execute([$young['id']]);
        $id = $this->squads->create($this->fresh((int) $young['id']), ['name' => "Mlodzi {$this->suffix}", 'specialization' => 'piesza'], $this->ip())['id'];
        $this->assertGreaterThan(0, $id);
        $this->assertSame('leader', $this->role((int) $young['id']));
    }

    public function testNameUniquenessAndBannedWords(): void
    {
        $a = $this->user('Ala');
        $b = $this->user('Bob');
        $this->makeSquad($a, 'open', 'Orly');

        try {
            $this->squads->create($b, ['name' => 'ORLY ' . $this->suffix, 'specialization' => 'piesza'], $this->ip());
            $this->fail('Nazwa rozni sie tylko wielkoscia liter.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('name', $e->fields);
        }

        $word = 'zakaz' . substr($this->suffix, 0, 3);
        $admin = new SquadAdminService($this->pdo);
        $adminUser = $this->user('Adm');
        $admin->addBannedWord($word, (int) $adminUser['id']);
        try {
            $this->squads->create($b, ['name' => "Mega Z4kaz" . substr($this->suffix, 0, 3) . ' Team', 'specialization' => 'piesza'], $this->ip());
            $this->fail('Slowo zakazane (z podmiana znakow) powinno byc wykryte.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('name', $e->fields);
        }
        $this->assertTrue(NameFilter::isBanned($this->pdo, "xx{$word}yy"));
    }

    public function testJoinModes(): void
    {
        $lead = $this->user('Lid');
        $p1 = $this->user('Pierwszy');
        $p2 = $this->user('Drugi');
        $p3 = $this->user('Trzeci');

        $open = $this->makeSquad($lead, 'open', 'Otwarty');
        $this->assertSame('joined', $this->squads->join($p1, $open));
        $this->assertSame('member', $this->role((int) $p1['id']));
        try {
            $this->squads->join($this->fresh((int) $p1['id']), $open);
            $this->fail('Gracz jest juz w oddziale.');
        } catch (ApiException $e) {
            $this->assertSame('already_member', $e->errorCode);
        }

        // Tryb "na zgloszenie": zgloszenie, przyjecie przez uprawniona osobe.
        $this->squads->update($lead, $open, ['join_mode' => 'request']);
        $this->assertSame('requested', $this->squads->join($p2, $open));
        $this->assertSame('request', $this->view->profile($p2, $open)['me']['pending']);
        try {
            $this->squads->approveRequest($p1, $open, (int) $p2['id']);
            $this->fail('Zwykly czlonek nie przyjmuje zgloszen.');
        } catch (ApiException $e) {
            $this->assertSame('forbidden', $e->errorCode);
        }
        $this->squads->approveRequest($lead, $open, (int) $p2['id']);
        $this->assertSame('member', $this->role((int) $p2['id']));

        // Tryb "na zaproszenie": bez zaproszenia nie wejdzie, z zaproszeniem (bez znajomosci) tak.
        $this->squads->update($lead, $open, ['join_mode' => 'invite']);
        try {
            $this->squads->join($p3, $open);
            $this->fail('Dolaczenie bez zaproszenia.');
        } catch (ApiException $e) {
            $this->assertSame('invite_only', $e->errorCode);
        }
        $this->squads->invite($lead, $open, ['name' => $p3['character_name']]);
        $this->assertCount(1, $this->view->mine($p3)['invites']);
        $this->squads->acceptInvite($p3, $open);
        $this->assertSame('member', $this->role((int) $p3['id']));
        $this->assertSame(4, $this->squads->countMembers($open));
    }

    public function testRolesPermissionsAndTransfer(): void
    {
        $lead = $this->user('Lider');
        $dep = $this->user('Zastepca');
        $g1 = $this->user('Przew');
        $g2 = $this->user('Przew2');
        $g3 = $this->user('Przew3');
        $id = $this->makeSquad($lead);
        foreach ([$dep, $g1, $g2, $g3] as $u) {
            $this->squads->join($u, $id);
        }

        $this->squads->setRole($lead, $id, (int) $dep['id'], ['role' => 'deputy']);
        $this->squads->setRole($dep, $id, (int) $g1['id'], ['role' => 'guide']);
        $this->squads->setRole($dep, $id, (int) $g2['id'], ['role' => 'guide']);
        try {
            $this->squads->setRole($dep, $id, (int) $g3['id'], ['role' => 'guide']);
            $this->fail('Rola Przewodnika ma limit 2 osob.');
        } catch (ApiException $e) {
            $this->assertSame('role_full', $e->errorCode);
        }
        try {
            $this->squads->setRole($g1, $id, (int) $g3['id'], ['role' => 'recruiter']);
            $this->fail('Przewodnik nie zarzadza rolami.');
        } catch (ApiException $e) {
            $this->assertSame('forbidden', $e->errorCode);
        }

        // Zastepca nie rozwiazuje oddzialu, nie przekazuje roli i nie wyrzuca Przywodcy.
        foreach ([
            fn() => $this->squads->disband($dep, $id),
            fn() => $this->squads->transfer($dep, $id, ['user_id' => $g1['id']]),
            fn() => $this->squads->kick($dep, $id, (int) $lead['id'], ['reason' => 'rules']),
        ] as $action) {
            try {
                $action();
                $this->fail('Zastepca nie moze tej czynnosci.');
            } catch (ApiException $e) {
                $this->assertSame('forbidden', $e->errorCode);
            }
        }

        // Wyrzucenie wymaga powodu z listy, ustawia karencje i zapisuje wpis w kronice.
        try {
            $this->squads->kick($dep, $id, (int) $g3['id'], ['reason' => 'bo tak']);
            $this->fail('Powod spoza listy.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('reason', $e->fields);
        }
        $this->squads->kick($dep, $id, (int) $g3['id'], ['reason' => 'inactive']);
        $this->assertNull($this->role((int) $g3['id']));
        $this->assertSame('squad_cooldown', $this->squads->eligibility($this->fresh((int) $g3['id']))['reason']);

        // Przywodca nie moze wyjsc bez przekazania roli; po przekazaniu zostaje Zastepca (zamiana).
        try {
            $this->squads->leave($lead);
            $this->fail('Przywodca musi przekazac role.');
        } catch (ApiException $e) {
            $this->assertSame('leader_cannot_leave', $e->errorCode);
        }
        $this->squads->transfer($lead, $id, ['user_id' => $dep['id']]);
        $this->assertSame('leader', $this->role((int) $dep['id']));
        $this->assertSame('deputy', $this->role((int) $lead['id']));

        $types = array_column(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), 'type');
        $this->assertContains('kicked', $types);
        $this->assertContains('leader', $types);
        $this->assertContains('joined', $types);
    }

    public function testLeavingStartsCooldownAndLastLeaderDisbands(): void
    {
        $lead = $this->user('Samotny');
        $mem = $this->user('Czlonek');
        $id = $this->makeSquad($lead);
        $this->squads->join($mem, $id);

        $this->squads->leave($mem);
        $this->assertSame('squad_cooldown', $this->squads->eligibility($this->fresh((int) $mem['id']))['reason']);
        // Po uplywie karencji mozna wrocic.
        $this->pdo->prepare('UPDATE users SET squad_left_at = UTC_TIMESTAMP() - INTERVAL 4 DAY WHERE id = ?')->execute([$mem['id']]);
        $this->assertTrue($this->squads->eligibility($this->fresh((int) $mem['id']))['ok']);

        // Przywodca sam w oddziale rozwiazuje go wychodzac; nazwa wraca do puli.
        $this->squads->leave($lead);
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM squads WHERE id = $id AND archived_at IS NOT NULL AND name_key IS NULL")->fetchColumn());
        $this->makeSquad($this->fresh((int) $mem['id']), 'open', 'Test');
    }

    public function testFireStreakAndEmber(): void
    {
        $this->assertSame(0, SquadFire::level(2));
        $this->assertSame(1, SquadFire::level(3));
        $this->assertSame(4, SquadFire::level(30));
        $this->assertSame(2, SquadFire::needed(1));
        $this->assertSame(3, SquadFire::needed(10));
        $this->assertSame(9, SquadFire::needed(30));
        $this->assertSame(100, SquadFire::ember(3, 10, 1));
        $this->assertSame(125, SquadFire::ember(5, 10, 1));
        $this->assertSame(150, SquadFire::ember(7, 10, 1));
        $this->assertSame(157, SquadFire::ember(7, 10, 3));
        $this->assertSame(7, SquadFire::streakAfterMiss(14));
        $this->assertSame(0, SquadFire::streakAfterMiss(2));

        $lead = $this->user('OgnLid');
        $mem = $this->user('OgnCz');
        $id = $this->makeSquad($lead);
        $this->squads->join($mem, $id);
        // Przesuwamy start oddzialu w przeszlosc, zeby rozliczyc zalegle dni.
        $this->pdo->prepare("UPDATE squads SET last_fire_day = '2026-01-01', created_at = '2025-12-01 10:00:00' WHERE id = ?")->execute([$id]);
        $this->pdo->prepare("UPDATE squad_members SET joined_at = '2025-12-01 10:00:00' WHERE squad_id = ?")->execute([$id]);
        foreach (['2026-01-02', '2026-01-03', '2026-01-04'] as $day) {
            foreach ([$lead, $mem] as $u) {
                $this->pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$u['id'], $day]);
            }
        }
        // 5. stycznia tylko jedna osoba z dwoch (potrzeba 2): ognisko tli sie.
        $this->pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$lead['id'], '2026-01-05']);

        $this->assertSame(4, SquadFire::processSquad($this->pdo, $id, '2026-01-05'));
        $s = $this->pdo->query("SELECT * FROM squads WHERE id = $id")->fetch();
        $this->assertSame(0, (int) $s['flame_streak']);
        $this->assertSame(3, (int) $s['best_streak']);
        $this->assertSame(3, (int) $s['lit_days']);
        $this->assertSame(150 + 150 + 157, (int) $s['ember']);
        $this->assertSame('2026-01-05', (string) $s['last_fire_day']);

        // Ponowne rozliczenie tych samych dni niczego nie zmienia.
        $this->assertSame(0, SquadFire::processSquad($this->pdo, $id, '2026-01-05'));
        $types = array_column(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), 'type');
        $this->assertContains('fire_level', $types);
        $this->assertContains('fire_dim', $types);

        $fire = $this->view->profile($lead, $id)['fire'];
        $this->assertSame(0, $fire['level']);
        $this->assertSame(457, $fire['ember']);
        $this->assertCount(4, $fire['history']);
    }

    public function testPrivacyAndPublicProfile(): void
    {
        $lead = $this->user('PrivLid');
        $mem = $this->user('PrivCz');
        $outsider = $this->user('Obcy');
        $id = $this->makeSquad($lead);
        $this->squads->join($mem, $id);
        $this->squads->setRole($lead, $id, (int) $mem['id'], ['role' => 'chronicler']);

        // Obcy widzi nazwe, specjalizacje, liczbe czlonkow i liste (z tytulem i data dolaczenia), ale nie kronike ani ognisko.
        $public = $this->view->profile($outsider, $id);
        $this->assertSame(2, $public['members']);
        $this->assertSame('gorska', $public['specialization']);
        $this->assertFalse($public['me']['is_member']);
        $this->assertArrayNotHasKey('chronicle', $public);
        $this->assertArrayNotHasKey('fire', $public);
        $byId = array_column($public['member_list'], null, 'id');
        $this->assertSame('chronicler', $byId[(int) $mem['id']]['title']);
        $this->assertTrue($byId[(int) $mem['id']]['recruit']);
        $this->assertNotEmpty($byId[(int) $mem['id']]['joined_at']);

        // Wylaczony tytul znika dla obcych; czlonkowie dalej widza role.
        $this->pdo->prepare('UPDATE users SET show_squad_title = 0 WHERE id = ?')->execute([$mem['id']]);
        $byId = array_column($this->view->profile($outsider, $id)['member_list'], null, 'id');
        $this->assertNull($byId[(int) $mem['id']]['title']);
        $inside = array_column($this->view->profile($lead, $id)['member_list'], null, 'id');
        $this->assertSame('chronicler', $inside[(int) $mem['id']]['role']);

        // Kronika: gracz z wylaczonym share_activity jest anonimowy dla pozostalych.
        $this->pdo->prepare('UPDATE users SET share_activity = 0 WHERE id = ?')->execute([$mem['id']]);
        $joined = array_values(array_filter(SquadChronicle::list($this->pdo, $id, (int) $lead['id']), fn($e) => $e['type'] === 'joined'));
        $this->assertNull($joined[0]['user']['name']);
        $own = array_values(array_filter(SquadChronicle::list($this->pdo, $id, (int) $mem['id']), fn($e) => $e['type'] === 'joined'));
        $this->assertSame($mem['character_name'], $own[0]['user']['name']);
    }

    public function testNameChangeLimitsReportsAndForcedName(): void
    {
        $lead = $this->user('ZmLid');
        $reporter = $this->user('Zglas');
        $id = $this->makeSquad($lead);
        $this->squads->join($reporter, $id);

        $this->squads->update($lead, $id, ['name' => "Nowa nazwa {$this->suffix}"]);
        try {
            $this->squads->update($lead, $id, ['name' => "Kolejna nazwa {$this->suffix}"]);
            $this->fail('Nazwe mozna zmienic raz na 30 dni.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('name', $e->fields);
        }
        $this->squads->update($lead, $id, ['specialization' => 'rowerowa']);
        try {
            $this->squads->update($lead, $id, ['specialization' => 'piesza']);
            $this->fail('Specjalizacje mozna zmienic raz na 90 dni.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('specialization', $e->fields);
        }

        $this->squads->reportName($reporter, $id, ['reason' => 'offensive'], $this->ip());
        try {
            $this->squads->reportName($reporter, $id, ['reason' => 'other'], $this->ip());
            $this->fail('Jedno zgloszenie na gracza i oddzial.');
        } catch (ApiException $e) {
            $this->assertSame('already_reported', $e->errorCode);
        }

        $admin = new SquadAdminService($this->pdo);
        $this->assertGreaterThanOrEqual(1, $admin->reports(1, 'new')['total']);
        $admin->forceName($id);
        $this->assertSame("nr $id", $this->squads->squad($id)['name']);
        // Po wymuszeniu oddzial moze od razu wybrac nowa nazwe; blokada administratora to uniemozliwia.
        $admin->lockName($id, true);
        try {
            $this->squads->update($lead, $id, ['name' => "Po zmianie {$this->suffix}"]);
            $this->fail('Nazwa zablokowana.');
        } catch (ApiException $e) {
            $this->assertSame('name_locked', $e->errorCode);
        }
        $admin->lockName($id, false);
        $this->squads->update($lead, $id, ['name' => "Po zmianie {$this->suffix}"]);
        $this->assertSame("Po zmianie {$this->suffix}", $this->squads->squad($id)['name']);
        $this->assertSame(0, $admin->reports(1, 'new', $id)['total']);
    }

    public function testSuccessionAndArchiving(): void
    {
        $lead = $this->user('NieLid');
        $dep = $this->user('Nastep');
        $id = $this->makeSquad($lead);
        $this->squads->join($dep, $id);
        $this->squads->setRole($lead, $id, (int) $dep['id'], ['role' => 'deputy']);

        $today = Clock::gameDay(Clock::now($this->pdo));
        $this->pdo->prepare('INSERT IGNORE INTO user_stats (user_id) VALUES (?)')->execute([$lead['id']]);
        $this->pdo->prepare("UPDATE user_stats SET last_active_day = '2020-01-01' WHERE user_id = ?")->execute([$lead['id']]);
        $this->pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$dep['id'], $today]);

        SquadMaintenance::succession($this->pdo, Clock::now($this->pdo));
        $this->assertSame('leader', $this->role((int) $dep['id']));
        $this->assertSame('deputy', $this->role((int) $lead['id']));

        // Oddzial bez zadnej aktywnosci od ponad 30 dni jest archiwizowany, a nazwa wraca do puli.
        $this->pdo->prepare('DELETE FROM user_active_days WHERE user_id IN (?, ?)')->execute([$lead['id'], $dep['id']]);
        $this->pdo->prepare("UPDATE squads SET created_at = UTC_TIMESTAMP() - INTERVAL 60 DAY WHERE id = ?")->execute([$id]);
        SquadMaintenance::archiveInactive($this->pdo, Clock::now($this->pdo));
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM squads WHERE id = $id AND archived_at IS NOT NULL")->fetchColumn());
        $this->assertNull($this->role((int) $dep['id']));
    }
}
