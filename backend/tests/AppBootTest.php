<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\App;
use Turysta\Database;

/** Smoke test: aplikacja sklada sie w calosc (brakujace importy klas wychodza tu, a nie jako 500 na kazdym zadaniu). */
final class AppBootTest extends TestCase
{
    public function testRouterBuilds(): void
    {
        try {
            Database::pdo()->query('SELECT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy: ' . $e->getMessage());
        }
        $this->assertNotNull(App::router());
    }
}
