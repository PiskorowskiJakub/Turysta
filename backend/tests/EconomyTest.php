<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Turysta\Game\Economy;

final class EconomyTest extends TestCase
{
    /** @return array<string,mixed> */
    private function skill(): array
    {
        return ['base_cost_cents' => 619, 'cost_growth' => '1.1800', 'base_seconds' => 1908, 'time_growth' => '1.1200'];
    }

    public function testFirstLevelUsesBaseValues(): void
    {
        $this->assertSame(619, Economy::cost($this->skill(), 1));
        $this->assertSame(1908, Economy::seconds($this->skill(), 1));
    }

    public function testExponentialGrowthUpToLevel50(): void
    {
        $this->assertSame((int) floor(619 * 1.18), Economy::cost($this->skill(), 2));
        $this->assertSame((int) round(1908 * 1.12), Economy::seconds($this->skill(), 2));
        // ok. 20 600 monet i 5,7 doby na poziomie 50
        $this->assertEqualsWithDelta(2060200, Economy::cost($this->skill(), 50), 5000);
        $this->assertEqualsWithDelta(5.7 * 86400, Economy::seconds($this->skill(), 50), 0.05 * 86400);
    }

    public function testCurveIsFlattenedAfterLevel50(): void
    {
        $c50 = Economy::cost($this->skill(), 50);
        $this->assertEqualsWithDelta($c50 * 8, Economy::cost($this->skill(), 100), 2);
        $t50 = Economy::seconds($this->skill(), 50);
        $this->assertEqualsWithDelta($t50 * 4, Economy::seconds($this->skill(), 100), 2);
        $this->assertGreaterThan($c50, Economy::cost($this->skill(), 51));
    }

    public function testPoints(): void
    {
        $this->assertSame(30, Economy::trainingPoints(3));
        $this->assertSame(24, Economy::jobPoints(24700));
        $this->assertSame(0, Economy::jobPoints(999));
    }
}
