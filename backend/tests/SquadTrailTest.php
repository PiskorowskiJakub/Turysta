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
use Turysta\Game\Clock;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadTrail;
use Turysta\Squad\SquadTrailThemes;

/** Szlaki oddzialow (Etap 2): wpisowe, Marsz, postep, rozliczenie, pieczatki i nagrody (prawdziwy MySQL). */
final class SquadTrailTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private SquadService $squads;
    private SquadTrail $trails;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM squad_trails LIMIT 1');
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
        $this->trails = new SquadTrail($this->pdo, $this->squads);
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
        ], '10.7.' . random_int(1, 250) . '.' . random_int(1, 250));
        $id = (int) $created['id'];
        $this->pdo->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP(), created_at = UTC_TIMESTAMP() - INTERVAL 10 DAY WHERE id = ?')->execute([$id]);
        $this->pdo->prepare('UPDATE wallets SET coins_cents = 1000 WHERE user_id = ?')->execute([$id]);
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

    private function active(int $userId, string $from, string $to): void
    {
        for ($d = new DateTimeImmutable($from); $d <= new DateTimeImmutable($to); $d = $d->modify('+1 day')) {
            $this->pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$userId, $d->format('Y-m-d')]);
        }
    }

    public function testStampThresholds(): void
    {
        $this->assertSame(0, SquadTrail::stampsFor('medium', 99, 1), 'Za mało uprawnionych uczestników.');
        $this->assertSame(0, SquadTrail::stampsFor('medium', 39.9, 5));
        $this->assertSame(1, SquadTrail::stampsFor('medium', 40, 2));
        $this->assertSame(3, SquadTrail::stampsFor('medium', 80, 2));
        $this->assertSame(4, SquadTrail::stampsFor('medium', 92, 2));
        $this->assertSame(3, SquadTrail::stampsFor('easy', 100, 2), 'Łatwa trasa ma limit 3 pieczątek.');
        $this->assertSame(5, SquadTrail::stampsFor('hard', 97, 2));
    }

    public function testThemesRespectSeasonAndDoNotRepeat(): void
    {
        $winter = new DateTimeImmutable('2026-01-05');
        for ($squad = 1; $squad <= 40; $squad++) {
            $code = SquadTrailThemes::pick($squad, 'narciarska', $winter, null);
            $this->assertNotNull(SquadTrailThemes::get($code));
            $this->assertNotSame('g_lato', $code);
            $this->assertNotSame($code, SquadTrailThemes::pick($squad, 'narciarska', $winter, $code));
        }
        $summer = new DateTimeImmutable('2026-07-06');
        for ($squad = 1; $squad <= 40; $squad++) {
            $code = SquadTrailThemes::pick($squad, 'narciarska', $summer, null);
            $this->assertStringStartsNotWith('narciarska_', $code);
            $this->assertNotSame('g_zima', $code);
        }
    }

    public function testFullWeekFlow(): void
    {
        $lead = $this->user('SzlLid');
        $a = $this->user('SzlA');
        $b = $this->user('SzlB');
        $squadId = $this->squads->create($lead, ['name' => "Szlak {$this->suffix}", 'specialization' => 'gorska', 'join_mode' => 'open'], '10.6.' . random_int(1, 250) . '.' . random_int(1, 250))['id'];
        $this->squads->join($a, $squadId);
        $this->squads->join($b, $squadId);

        // Poniedzialek 2026-01-05: trudnosc ustawia przywodca, zwykly czlonek nie moze.
        $mon = $this->at('2026-01-05');
        $view = $this->trails->view($lead, $squadId, $mon);
        $this->assertSame('medium', $view['difficulty']);
        $this->assertTrue($view['can_plan']);
        $this->assertFalse($this->trails->view($a, $squadId, $mon)['can_plan']);
        try {
            $this->trails->setDifficulty($a, $squadId, ['difficulty' => 'hard'], $mon);
            $this->fail('Zwykły członek nie planuje trasy.');
        } catch (ApiException $e) {
            $this->assertSame('forbidden', $e->errorCode);
        }
        $this->trails->setDifficulty($lead, $squadId, ['difficulty' => 'hard'], $mon);
        $this->assertSame('hard', $this->trails->view($lead, $squadId, $mon)['difficulty']);
        try {
            $this->trails->setDifficulty($lead, $squadId, ['difficulty' => 'easy'], $this->at('2026-01-06'));
            $this->fail('Po poniedziałku trudności nie zmieniamy.');
        } catch (ApiException $e) {
            $this->assertSame('difficulty_locked', $e->errorCode);
        }

        // Wpisowe znika z obiegu, drugie dolaczenie jest blokowane.
        $this->trails->join($lead, $squadId, $mon);
        $this->trails->join($a, $squadId, $mon);
        $this->assertSame(800, $this->coins((int) $lead['id']));
        try {
            $this->trails->join($a, $squadId, $mon);
            $this->fail('Dwa razy do tej samej wyprawy się nie dołącza.');
        } catch (ApiException $e) {
            $this->assertSame('already_joined', $e->errorCode);
        }
        $this->assertSame(800, $this->coins((int) $a['id']));

        // Czwartek: za pozno na dolaczenie, a brak dolaczenia = brak Marszu.
        try {
            $this->trails->join($b, $squadId, $this->at('2026-01-08'));
            $this->fail('Po środzie nie można dołączyć.');
        } catch (ApiException $e) {
            $this->assertSame('trail_closed', $e->errorCode);
        }
        $this->assertSame(1000, $this->coins((int) $b['id']));
        try {
            $this->trails->march($b, $squadId, $this->at('2026-01-08'));
            $this->fail('Marsz tylko dla uczestników.');
        } catch (ApiException $e) {
            $this->assertSame('not_participant', $e->errorCode);
        }

        // Przywodca maszeruje codziennie (Marsz raz dziennie), oboje sa aktywni przez caly tydzien.
        $this->trails->march($lead, $squadId, $mon);
        try {
            $this->trails->march($lead, $squadId, $this->at('2026-01-05', '20:00:00'));
            $this->fail('Marsz raz dziennie.');
        } catch (ApiException $e) {
            $this->assertSame('march_done', $e->errorCode);
        }
        for ($d = 6; $d <= 11; $d++) {
            $this->trails->march($lead, $squadId, $this->at(sprintf('2026-01-%02d', $d)));
        }
        $this->active((int) $lead['id'], '2026-01-05', '2026-01-11');
        $this->active((int) $a['id'], '2026-01-05', '2026-01-11');

        // Przed rozliczeniem postep jest policzony na zywo: (100% + 50%) / 2 = 75%.
        $live = $this->trails->view($lead, $squadId, $this->at('2026-01-11', '20:00:00'));
        $this->assertSame(75.0, $live['percent']);
        $this->assertSame(2, $live['stamps_now']);
        // Kilometry to suma uczestnikow, a cel rosnie z ich liczba (trudna trasa = 80 km na osobe).
        $this->assertSame(160, $live['goal_km']);
        $this->assertSame(120.0, $live['km']);
        $this->assertSame(80.0, $live['my_km']);

        // Rozliczenie (nowy tydzien): 2 pieczatki (trudna trasa, prog 70%), nagroda po 1 dk dla obu uczestnikow, tylko raz.
        $this->assertGreaterThanOrEqual(1, SquadTrail::finalizeDue($this->pdo, Clock::now($this->pdo)));
        $row = $this->pdo->query("SELECT * FROM squad_trails WHERE squad_id = $squadId")->fetch();
        $this->assertSame('done', $row['status']);
        $this->assertSame(2, (int) $row['stamps']);
        $this->assertSame(75.0, (float) $row['percent']);
        $this->assertSame(2, (int) $row['eligible']);
        $sq = $this->pdo->query("SELECT stamps, trails_done FROM squads WHERE id = $squadId")->fetch();
        $this->assertSame(2, (int) $sq['stamps']);
        $this->assertSame(1, (int) $sq['trails_done']);
        $this->assertSame(900, $this->coins((int) $lead['id']));
        $this->assertSame(900, $this->coins((int) $a['id']));
        $this->assertSame(
            1,
            (int) $this->pdo->query("SELECT COUNT(*) FROM squad_events WHERE squad_id = $squadId AND type = 'trail_done'")->fetchColumn()
        );
        SquadTrail::finalizeDue($this->pdo, Clock::now($this->pdo));
        $this->assertSame(900, $this->coins((int) $lead['id']));
        $this->assertSame(2, (int) $this->pdo->query("SELECT stamps FROM squads WHERE id = $squadId")->fetchColumn());

        $history = $this->trails->history($lead, $squadId);
        $this->assertCount(1, $history);
        $this->assertSame(2, $history[0]['stamps']);
        $this->assertSame(100, $history[0]['my_reward_cents']);
        $this->assertTrue($history[0]['my_eligible']);
    }

    public function testNoStampsWithoutEnoughActiveParticipants(): void
    {
        $lead = $this->user('SzlSolo');
        $squadId = $this->squads->create($lead, ['name' => "Solo {$this->suffix}", 'specialization' => 'ogolna', 'join_mode' => 'open'], '10.6.' . random_int(1, 250) . '.' . random_int(1, 250))['id'];
        $mon = $this->at('2025-12-01');
        $this->trails->join($lead, $squadId, $mon);
        for ($d = 1; $d <= 7; $d++) {
            $this->trails->march($lead, $squadId, $this->at(sprintf('2025-12-%02d', $d)));
        }
        $this->active((int) $lead['id'], '2025-12-01', '2025-12-07');
        SquadTrail::finalizeDue($this->pdo, Clock::now($this->pdo));
        $row = $this->pdo->query("SELECT status, stamps, percent FROM squad_trails WHERE squad_id = $squadId")->fetch();
        $this->assertSame('done', $row['status']);
        $this->assertSame(0, (int) $row['stamps']);
        $this->assertSame(100.0, (float) $row['percent']);
        $this->assertSame(800, $this->coins((int) $lead['id']), 'Wpisowe przepada, a nagrody nie ma.');
    }
}
