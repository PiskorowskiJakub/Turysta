<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Database;
use Turysta\Game\AnnouncementService;
use Turysta\Game\UpdateService;
use Turysta\Http\ApiException;

/** Aktualizacje gry i ich powiazanie z ogloszeniami (prawdziwy MySQL). */
final class UpdateFlowTest extends TestCase
{
    private PDO $pdo;
    private UpdateService $updates;
    private AnnouncementService $announcements;
    /** @var list<int> */
    private array $updateIds = [];
    /** @var list<int> */
    private array $announcementIds = [];

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM updates LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $this->updates = new UpdateService($this->pdo);
        $this->announcements = new AnnouncementService($this->pdo);
    }

    protected function tearDown(): void
    {
        foreach ($this->announcementIds as $id) {
            $this->pdo->exec("DELETE FROM announcements WHERE id = $id");
        }
        foreach ($this->updateIds as $id) {
            $this->pdo->exec("DELETE FROM updates WHERE id = $id");
        }
    }

    public function testDraftsAreHiddenFromPlayersAndDatesAreValidated(): void
    {
        $tag = bin2hex(random_bytes(3));
        $pub = $this->updates->create(null, ['title' => "Nowosc $tag", 'body' => "Pierwszy akapit.\n\n- punkt", 'released_on' => '2026-11-01', 'published' => true]);
        $draft = $this->updates->create(null, ['title' => "Szkic $tag", 'body' => 'Wersja robocza.', 'published' => false]);
        $this->updateIds = [$pub['id'], $draft['id']];

        $titles = array_column($this->updates->published(100), 'title');
        $this->assertContains("Nowosc $tag", $titles);
        $this->assertNotContains("Szkic $tag", $titles);
        $this->assertContains("Szkic $tag", array_column($this->updates->all(), 'title'));
        // Brak daty oznacza dzisiejsza.
        $this->assertSame(gmdate('Y-m-d'), $draft['released_on']);

        foreach ([['title' => '', 'body' => 'x'], ['title' => 'x', 'body' => ''], ['title' => 'x', 'body' => 'x', 'released_on' => '2026-13-45']] as $bad) {
            try {
                $this->updates->create(null, $bad);
                $this->fail('Niepoprawne dane powinny byc odrzucone.');
            } catch (ApiException $e) {
                $this->assertSame('validation_failed', $e->errorCode);
            }
        }

        $edited = $this->updates->update($pub['id'], ['title' => "Zmiana $tag", 'body' => 'Nowa tresc.', 'released_on' => '2026-11-02', 'published' => true]);
        $this->assertSame("Zmiana $tag", $edited['title']);
        $this->assertSame('2026-11-02', $edited['released_on']);
    }

    public function testAnnouncementLinksToUpdateAndLosesLinkWhenUpdateDeleted(): void
    {
        $author = (int) $this->pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
        if ($author === 0) {
            $this->markTestSkipped('Brak uzytkownika w bazie.');
        }
        $upd = $this->updates->create(null, ['title' => 'Duza zmiana', 'body' => 'Szczegoly zmiany.', 'published' => true]);
        $this->updateIds[] = $upd['id'];

        $ann = $this->announcements->create($author, ['title' => 'Nowosci', 'body' => 'Krotko o zmianach.', 'published' => true, 'update_id' => $upd['id']]);
        $this->announcementIds[] = $ann['id'];
        $this->assertSame($upd['id'], $ann['update_id']);

        try {
            $this->announcements->update($ann['id'], ['title' => 'Nowosci', 'body' => 'x', 'published' => true, 'update_id' => 999999999]);
            $this->fail('Nieistniejaca aktualizacja powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('update_id', $e->fields);
        }

        $none = $this->announcements->update($ann['id'], ['title' => 'Nowosci', 'body' => 'x', 'published' => true, 'update_id' => '']);
        $this->assertNull($none['update_id']);
        $linked = $this->announcements->update($ann['id'], ['title' => 'Nowosci', 'body' => 'x', 'published' => true, 'update_id' => $upd['id']]);
        $this->assertSame($upd['id'], $linked['update_id']);

        $this->updates->delete($upd['id']);
        $after = array_values(array_filter($this->announcements->all(), static fn(array $a): bool => $a['id'] === $ann['id']));
        $this->assertNull($after[0]['update_id']);
    }
}
