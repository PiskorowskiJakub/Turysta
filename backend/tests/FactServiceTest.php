<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Admin\FactService;
use Turysta\Database;
use Turysta\Http\ApiException;

/** Ciekawostki dnia: limit dlugosci tresci pod pasek (prawdziwy MySQL). */
final class FactServiceTest extends TestCase
{
    private PDO $pdo;
    private FactService $facts;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM daily_facts LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $this->facts = new FactService($this->pdo);
    }

    public function testTextLengthLimit(): void
    {
        $ok = $this->facts->create(['text' => str_repeat('a', FactService::TEXT_MAX), 'active' => false]);
        $this->assertFalse($ok['too_long']);

        foreach ([str_repeat('a', FactService::TEXT_MAX + 1), '   '] as $bad) {
            try {
                $this->facts->create(['text' => $bad]);
                $this->fail('Zla tresc powinna zostac odrzucona.');
            } catch (ApiException $e) {
                $this->assertArrayHasKey('text', $e->fields);
            }
        }
        try {
            $this->facts->update($ok['id'], ['text' => str_repeat('b', FactService::TEXT_MAX + 1), 'active' => true]);
            $this->fail('Edycja tez ma miec limit.');
        } catch (ApiException $e) {
            $this->assertSame('validation_failed', $e->errorCode);
        }
        $this->facts->delete($ok['id']);
    }

    public function testOldLongFactCanStillBeToggled(): void
    {
        $this->pdo->prepare('INSERT INTO daily_facts (text, active) VALUES (?, 1)')->execute([str_repeat('x', FactService::TEXT_MAX + 50)]);
        $id = (int) $this->pdo->lastInsertId();

        $row = $this->facts->update($id, ['active' => false]);
        $this->assertFalse($row['active']);
        $this->assertTrue($row['too_long']);
        $this->assertSame(FactService::TEXT_MAX + 50, mb_strlen($row['text']));
        $this->facts->delete($id);
    }
}
