<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Social\ActivityFeed;
use Turysta\Social\FriendService;
use Turysta\Social\GiftService;

/** Znajomi: zaproszenia, akceptacja, widocznosc statusu online, dukatow i punktow (prawdziwy MySQL). */
final class FriendFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private FriendService $friends;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM friendships LIMIT 1');
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
        $this->friends = new FriendService($this->pdo, new RateLimiter($this->pdo));
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> wiersz users */
    private function user(string $prefix): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.7.' . random_int(1, 250) . '.' . random_int(1, 250));
        return $this->pdo->query("SELECT * FROM users WHERE id = {$created['id']}")->fetch();
    }

    private function ip(): string
    {
        return '10.1.' . random_int(1, 250) . '.' . random_int(1, 250);
    }

    /** @param list<array<string,mixed>> $rows */
    private function names(array $rows): array
    {
        return array_column($rows, 'name');
    }

    public function testRequestAcceptAndVisibility(): void
    {
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');

        $sent = $this->friends->request($ala, ['name' => strtolower($bob['character_name'])], $this->ip());
        $this->assertSame('pending', $sent['status']);
        $this->assertSame('outgoing', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame('incoming', $this->friends->state((int) $bob['id'], (int) $ala['id']));

        // Zaproszony widzi je, ale jeszcze nie widzi dukatow ani punktow zapraszajacego.
        $bobView = $this->friends->overview((int) $bob['id']);
        $this->assertSame([$ala['character_name']], $this->names($bobView['incoming']));
        $this->assertSame([], $bobView['friends']);
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$bob['id']} AND type = 'friend_request'")->fetchColumn());

        // Zaprosic samemu siebie, zaprosic dwa razy lub nieistniejacego gracza sie nie da.
        foreach ([[$ala, ['name' => $ala['character_name']]], [$ala, ['name' => 'Nikt' . $this->suffix]]] as [$u, $in]) {
            try {
                $this->friends->request($u, $in, $this->ip());
                $this->fail('Zaproszenie powinno byc odrzucone.');
            } catch (ApiException $e) {
                $this->assertArrayHasKey('name', $e->fields);
            }
        }
        try {
            $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
            $this->fail('Drugie zaproszenie powinno byc odrzucone.');
        } catch (ApiException $e) {
            $this->assertSame('request_pending', $e->errorCode);
        }

        // Zapraszajacy nie moze sam siebie zaakceptowac.
        try {
            $this->friends->accept($ala, (int) $bob['id']);
            $this->fail('Akceptacja wlasnego zaproszenia powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertSame('not_found', $e->errorCode);
        }

        $this->friends->accept($bob, (int) $ala['id']);
        $this->assertSame('friends', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$ala['id']} AND type = 'friend_accepted'")->fetchColumn());

        $this->pdo->exec("UPDATE wallets SET coins_cents = 12345, points = 77 WHERE user_id = {$bob['id']}");
        $list = $this->friends->overview((int) $ala['id'])['friends'];
        $this->assertSame([$bob['character_name']], $this->names($list));
        $this->assertSame(12345, $list[0]['coins_cents']);
        $this->assertSame(77, $list[0]['points']);
        $this->assertFalse($list[0]['online']);

        // Aktywna sesja w ostatnich minutach oznacza "online".
        $sid = 'test' . bin2hex(random_bytes(8));
        $this->pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute([$sid, $bob['id']]);
        $this->assertTrue($this->friends->overview((int) $ala['id'])['friends'][0]['online']);
        $this->pdo->exec("UPDATE sessions SET last_activity = UTC_TIMESTAMP() - INTERVAL 10 MINUTE WHERE id = '$sid'");
        $this->assertFalse($this->friends->overview((int) $ala['id'])['friends'][0]['online']);
        $this->pdo->exec("DELETE FROM sessions WHERE id = '$sid'");

        $this->friends->remove((int) $bob['id'], (int) $ala['id']);
        $this->assertSame('none', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame([], $this->friends->overview((int) $ala['id'])['friends']);
    }

    public function testMutualRequestsBecomeFriendsAndDeclineDeletes(): void
    {
        $ala = $this->user('Ola');
        $bob = $this->user('Rob');
        $cez = $this->user('Cez');

        $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
        $this->assertSame('accepted', $this->friends->request($bob, ['user_id' => (int) $ala['id']], $this->ip())['status']);
        $this->assertSame('friends', $this->friends->state((int) $ala['id'], (int) $bob['id']));

        $this->friends->request($cez, ['name' => $ala['character_name']], $this->ip());
        $this->friends->remove((int) $ala['id'], (int) $cez['id']);
        $this->assertSame('none', $this->friends->state((int) $cez['id'], (int) $ala['id']));
    }

    public function testFriendshipsDisappearWithAccount(): void
    {
        $ala = $this->user('Usu');
        $bob = $this->user('Zos');
        $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
        $this->friends->accept($bob, (int) $ala['id']);

        (new \Turysta\Auth\UserDeleter($this->pdo))->delete((int) $ala['id']);
        $this->assertSame([], $this->friends->overview((int) $bob['id'])['friends']);
    }

    public function testFriendBadges(): void
    {
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');
        $ids = [(int) $ala['id'], (int) $bob['id']];
        $codes = fn(int $id): array => array_column(\Turysta\Game\BadgeService::earned($this->pdo, $id), 'tier', 'group');

        $this->assertArrayNotHasKey('special_friends', $codes($ids[0]));
        $this->friends->request($ala, ['user_id' => $ids[1]], $this->ip());
        $this->friends->accept($bob, $ids[0]);
        // Pierwszy znajomy: obie strony dostaja brazowa odznake.
        $this->assertSame('bronze', $codes($ids[0])['special_friends']);
        $this->assertSame('bronze', $codes($ids[1])['special_friends']);

        // Odznaki za wyprzedzanie znajomych juz nie ma.
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM badge_definitions WHERE code LIKE 'special\\_rival.%'")->fetchColumn());
    }

    public function testReferralBadgeAwardsCoinsOncePerTier(): void
    {
        $ala = $this->user('Ala');
        $codeStmt = $this->pdo->query("SELECT code FROM referral_codes WHERE user_id = {$ala['id']}");
        $code = (string) $codeStmt->fetchColumn();
        $this->assertNotSame('', $code);

        $bob = $this->auth->register([
            'character_name' => 'Pol' . $this->suffix,
            'email' => "pol{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
            'referral_code' => $code,
        ], '10.8.' . random_int(1, 250) . '.' . random_int(1, 250));
        $now = \Turysta\Game\Clock::now($this->pdo);
        $earned = fn(): array => array_column(\Turysta\Game\BadgeService::earned($this->pdo, (int) $ala['id']), 'tier', 'group');

        // Polecony bez potwierdzonego e-maila i bez dni aktywnosci sie nie liczy.
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $ala['id'], $now);
        $this->assertArrayNotHasKey('special_referrals', $earned());

        $this->pdo->exec("UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id = {$bob['id']}");
        $this->pdo->exec("INSERT IGNORE INTO user_stats (user_id) VALUES ({$bob['id']})");
        $this->pdo->exec("UPDATE user_stats SET active_days = 6 WHERE user_id = {$bob['id']}");
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $ala['id'], $now);
        $this->assertArrayNotHasKey('special_referrals', $earned());

        $before = $this->coins($ala);
        $this->pdo->exec("UPDATE user_stats SET active_days = 7 WHERE user_id = {$bob['id']}");
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $ala['id'], $now);
        $this->assertSame('bronze', $earned()['special_referrals']);
        $reference = (new \Turysta\Game\DailyRewardService($this->pdo))->referenceCost((int) $ala['id']);
        // Odznaka (braz) oraz premia za pierwszego aktywnego poleconego.
        $reward = \Turysta\Game\Economy::referralBadgeCoins('bronze', $reference) + \Turysta\Game\Economy::referralPersonCoins($reference);
        $this->assertGreaterThan(0, \Turysta\Game\Economy::referralPersonCoins($reference));
        $this->assertGreaterThan(0, $reward);
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$ala['id']} AND type = 'referral_person'")->fetchColumn());
        $this->assertSame($before + $reward, $this->coins($ala));
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$ala['id']} AND type = 'referral_badge'")->fetchColumn());

        // Ponowna synchronizacja nie wyplaca drugi raz.
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $ala['id'], $now);
        $this->assertSame($before + $reward, $this->coins($ala));
    }

    /** Robi z dwoch graczy znajomych od dawna (prezenty wymagaja 24 godzin znajomosci). */
    private function befriend(array $a, array $b, bool $old = true): void
    {
        $this->friends->request($a, ['user_id' => (int) $b['id']], $this->ip());
        $this->friends->accept($b, (int) $a['id']);
        if ($old) {
            $this->pdo->exec("UPDATE friendships SET responded_at = UTC_TIMESTAMP() - INTERVAL 3 DAY WHERE user_a = LEAST({$a['id']}, {$b['id']}) AND user_b = GREATEST({$a['id']}, {$b['id']})");
        }
    }

    private function coins(array $u): int
    {
        return (int) $this->pdo->query("SELECT coins_cents FROM wallets WHERE user_id = {$u['id']}")->fetchColumn();
    }

    public function testGiftsMoveCoinsAndRespectLimits(): void
    {
        $gifts = new GiftService($this->pdo, $this->friends);
        $ala = $this->user('Ala');
        $others = [$this->user('Bob'), $this->user('Cyn'), $this->user('Dar'), $this->user('Ewa')];
        foreach ($others as $o) {
            $this->befriend($ala, $o);
        }
        $this->pdo->exec("UPDATE wallets SET coins_cents = 100000 WHERE user_id = {$ala['id']}");

        $cents = $gifts->giftCents((int) $ala['id']);
        $bonus = GiftService::bonusCents($cents);
        $this->assertGreaterThan(0, $cents);
        $this->assertGreaterThan(0, $bonus);
        $this->assertLessThan($cents, $bonus);

        $bobBefore = $this->coins($others[0]);
        $sent = $gifts->send($ala, (int) $others[0]['id']);
        $this->assertSame($cents, $sent['cents']);
        $this->assertSame(100000 - $cents + $bonus, $this->coins($ala));
        $this->assertSame($bobBefore + $cents, $this->coins($others[0]));
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$others[0]['id']} AND type = 'friend_gift'")->fetchColumn());

        // Ta sama osoba dwa razy dziennie: nie. Czwarty prezent dziennie: tez nie.
        $this->expectCode(fn() => $gifts->send($ala, (int) $others[0]['id']), 'gift_already_sent');
        $gifts->send($ala, (int) $others[1]['id']);
        $gifts->send($ala, (int) $others[2]['id']);
        $this->expectCode(fn() => $gifts->send($ala, (int) $others[3]['id']), 'gift_limit');

        $state = $gifts->state((int) $ala['id']);
        $this->assertSame(0, $state['sent_left']);
        $this->assertSame(['sent_count' => 3, 'sent_cents' => 3 * $cents, 'received_count' => 0, 'received_cents' => 0], $state['totals']);
        $this->assertSame(['sent_count' => 0, 'sent_cents' => 0, 'received_count' => 1, 'received_cents' => $cents], $gifts->state((int) $others[0]['id'])['totals']);
        $this->assertSame('sent', ((array) $state['per_friend'])[(int) $others[0]['id']]['status']);
        $this->assertSame('limit', ((array) $state['per_friend'])[(int) $others[3]['id']]['status']);
    }

    public function testGiftRulesForNewFriendsStrangersAndFunds(): void
    {
        $gifts = new GiftService($this->pdo, $this->friends);
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');
        $obcy = $this->user('Obcy');
        $this->pdo->exec("UPDATE wallets SET coins_cents = 100000 WHERE user_id = {$ala['id']}");

        $this->expectCode(fn() => $gifts->send($ala, (int) $obcy['id']), 'not_friends');
        $this->expectCode(fn() => $gifts->send($ala, (int) $ala['id']), 'not_friends');

        // Swiezy znajomy: za wczesnie.
        $this->befriend($ala, $bob, false);
        $this->expectCode(fn() => $gifts->send($ala, (int) $bob['id']), 'gift_too_early');
        $this->assertSame('early', ((array) $gifts->state((int) $ala['id'])['per_friend'])[(int) $bob['id']]['status']);

        // Brak srodkow: nic sie nie zmienia i nie zuzywa limitu.
        $this->pdo->exec("UPDATE friendships SET responded_at = UTC_TIMESTAMP() - INTERVAL 3 DAY");
        $this->pdo->exec("UPDATE wallets SET coins_cents = 0 WHERE user_id = {$ala['id']}");
        $this->expectCode(fn() => $gifts->send($ala, (int) $bob['id']), 'insufficient_funds');
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM friend_gifts WHERE sender_id = {$ala['id']}")->fetchColumn());
    }

    private function expectCode(callable $fn, string $code): void
    {
        try {
            $fn();
            $this->fail("Oczekiwano bledu $code.");
        } catch (ApiException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public function testFriendsRankingByPeriod(): void
    {
        $ranking = new \Turysta\Game\RankingService($this->pdo);
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');
        $obcy = $this->user('Obcy');
        $this->befriend($ala, $bob);

        \Turysta\Game\Wallet::apply($this->pdo, (int) $ala['id'], 'points', 30, 'job_points');
        \Turysta\Game\Wallet::apply($this->pdo, (int) $bob['id'], 'points', 50, 'job_points');
        \Turysta\Game\Wallet::apply($this->pdo, (int) $ala['id'], 'points', 900, 'admin_adjust');
        \Turysta\Game\Wallet::apply($this->pdo, (int) $obcy['id'], 'points', 5000, 'job_points');
        // Stary wpis (przed poczatkiem miesiaca) nie liczy sie do tygodnia ani miesiaca.
        \Turysta\Game\Wallet::apply($this->pdo, (int) $ala['id'], 'points', 400, 'job_points');
        $this->pdo->exec("UPDATE ledger_entries SET created_at = UTC_TIMESTAMP() - INTERVAL 90 DAY WHERE user_id = {$ala['id']} AND amount = 400");

        foreach (['week', 'month'] as $period) {
            $r = $ranking->friends((int) $ala['id'], $period);
            $this->assertSame([(int) $bob['id'], (int) $ala['id']], array_column($r['rows'], 'id'), $period);
            $this->assertSame([50, 30], array_column($r['rows'], 'points'), $period);
            $this->assertSame([1, 2], array_column($r['rows'], 'rank'));
            $this->assertNotNull($r['since']);
        }

        // Lacznie: calkowite punkty (z korektami i starymi wpisami), obcy w ogole sie nie pojawia.
        $all = $ranking->friends((int) $ala['id'], 'all');
        $this->assertNull($all['since']);
        $this->assertSame([(int) $ala['id'], (int) $bob['id']], array_column($all['rows'], 'id'));
        $this->assertNotContains((int) $obcy['id'], array_column($all['rows'], 'id'));

        // Nieznany okres to tydzien.
        $this->assertSame('week', $ranking->friends((int) $ala['id'], 'xyz')['period']);
        $start = \Turysta\Game\RankingService::periodStart('week', \Turysta\Game\Clock::parse('2026-11-04 12:00:00'));
        $this->assertSame('2026-11-01 23:00:00', $start); // poniedzialek 2 listopada 00:00 w Warszawie (UTC+1)
    }

    public function testActivityFeedAndPrivacy(): void
    {
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');
        $obcy = $this->user('Obcy');
        $this->befriend($ala, $bob);

        // Zlota odznaka Boba i wejscie do swiata 3 trafiaja do aktywnosci.
        $this->pdo->exec("INSERT IGNORE INTO user_stats (user_id) VALUES ({$bob['id']})");
        $this->pdo->exec("UPDATE user_stats SET longest_streak = 30 WHERE user_id = {$bob['id']}");
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $bob['id'], \Turysta\Game\Clock::now($this->pdo));
        \Turysta\Game\BadgeService::sync($this->pdo, (int) $bob['id'], \Turysta\Game\Clock::now($this->pdo));
        ActivityFeed::record($this->pdo, (int) $bob['id'], 'world', 'w3', ['world' => 3]);
        ActivityFeed::record($this->pdo, (int) $obcy['id'], 'world', 'w2', ['world' => 2]);

        $feed = ActivityFeed::forUser($this->pdo, (int) $ala['id']);
        $this->assertTrue($feed['enabled']);
        $types = array_map(fn($e) => $e['type'] . ':' . ($e['payload']['group'] ?? $e['payload']['world']), $feed['events']);
        sort($types);
        // Brazowa i srebrna odznaki serii nie sa pokazywane, zlota tak; zdarzenie jest tylko raz mimo dwoch synchronizacji.
        $this->assertSame(['badge:special_streak', 'world:3'], $types);
        $this->assertSame((int) $bob['id'], $feed['events'][0]['user']['id']);
        // Obcy gracz nie jest widoczny.
        $this->assertCount(2, $feed['events']);

        // Bob wylacza aktywnosc: znika z feedu Ali.
        $this->pdo->exec("UPDATE users SET share_activity = 0 WHERE id = {$bob['id']}");
        $this->assertSame([], ActivityFeed::forUser($this->pdo, (int) $ala['id'])['events']);
        $this->pdo->exec("UPDATE users SET share_activity = 1 WHERE id = {$bob['id']}");

        // Ala wylacza: sama nic nie widzi.
        $this->pdo->exec("UPDATE users SET share_activity = 0 WHERE id = {$ala['id']}");
        $off = ActivityFeed::forUser($this->pdo, (int) $ala['id']);
        $this->assertFalse($off['enabled']);
        $this->assertSame([], $off['events']);
    }

    public function testGiftMultiplierGrowsWithActiveReferrals(): void
    {
        $m = fn(int $n): int => \Turysta\Game\Economy::giftMultiplierPermille($n);
        $this->assertSame(1000, $m(0));
        $this->assertSame(1250, $m(1));
        $this->assertSame(3500, $m(10));
        $this->assertSame(3600, $m(11));
        $this->assertSame(5500, $m(30));
        $this->assertSame(5500, $m(80));

        // Kwota prezentu rosnie razem z liczba aktywnych poleconych, a bonus nadawcy zostaje liczony od kwoty bazowej.
        $gifts = new GiftService($this->pdo, $this->friends);
        $ala = $this->user('Ala');
        $base = $gifts->giftCents((int) $ala['id']);
        $this->assertSame($base, $gifts->baseCents((int) $ala['id']));
        for ($i = 0; $i < 2; $i++) {
            $ref = $this->user('Pol' . $i);
            $this->pdo->exec("INSERT INTO referrals (referrer_id, referred_id) VALUES ({$ala['id']}, {$ref['id']})");
            $this->pdo->exec("UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id = {$ref['id']}");
            $this->pdo->exec("INSERT IGNORE INTO user_stats (user_id) VALUES ({$ref['id']})");
            $this->pdo->exec("UPDATE user_stats SET active_days = 7 WHERE user_id = {$ref['id']}");
        }
        $state = $gifts->state((int) $ala['id']);
        $this->assertSame(2, $state['active_referrals']);
        $this->assertSame(1500, $state['multiplier_permille']);
        $this->assertSame($base, $state['base_cents']);
        $this->assertSame(intdiv(intdiv($base * 1500, 1000) + 5, 10) * 10, $state['cents']);
        $this->assertGreaterThan($base, $state['cents']);
        $this->assertSame(GiftService::bonusCents($base), $state['bonus_cents']);
    }
}
