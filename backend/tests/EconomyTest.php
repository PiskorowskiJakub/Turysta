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
        return ['base_cost_cents' => 3, 'cost_growth' => '1.1600', 'base_seconds' => 5, 'time_growth' => '1.1600'];
    }

    public function testFirstLevelUsesBaseValues(): void
    {
        $this->assertSame(3, Economy::cost($this->skill(), 1));
        $this->assertSame(5, Economy::seconds($this->skill(), 1));
    }

    public function testExponentialGrowthUpToLevel50(): void
    {
        $this->assertSame((int) floor(3 * 1.16), Economy::cost($this->skill(), 2));
        $this->assertSame((int) round(5 * 1.16), Economy::seconds($this->skill(), 2));
        // Poczatek: sekundy i grosze, pozniej minuty, na poziomie 50 okolo 2 h i 43 monety.
        $this->assertLessThan(10, Economy::seconds($this->skill(), 2));
        $this->assertLessThan(300, Economy::seconds($this->skill(), 20));
        $this->assertEqualsWithDelta(4320, Economy::cost($this->skill(), 50), 50);
        $this->assertEqualsWithDelta(7201, Economy::seconds($this->skill(), 50), 50);
    }

    public function testCurveIsFlattenedAfterLevel50(): void
    {
        $c50 = Economy::cost($this->skill(), 50);
        $this->assertEqualsWithDelta($c50 * 8, Economy::cost($this->skill(), 100), 2);
        $t50 = Economy::seconds($this->skill(), 50);
        $this->assertEqualsWithDelta($t50 * 4, Economy::seconds($this->skill(), 100), 2);
        $this->assertGreaterThan($c50, Economy::cost($this->skill(), 51));
    }

    public function testTradeBonusGrowsSlowlyAndHasCeiling(): void
    {
        $this->assertSame(0, Economy::tradeBonusPermille(0));
        $this->assertSame(240, Economy::tradeBonusPermille(30));
        $this->assertSame(600, Economy::tradeBonusPermille(120));
        $this->assertSame(24700, Economy::boostedReward(24700, 0));
        $this->assertSame(39520, Economy::boostedReward(24700, 120));
        // Nawet absurdalny poziom nie przekroczy 2,2x (brak hiperinflacji).
        $this->assertLessThan(2200, 1000 + Economy::tradeBonusPermille(100000));
        $this->assertLessThan(90000 * 2.2 + 1, Economy::boostedReward(90000, 100000));
    }

    public function testPoints(): void
    {
        $this->assertSame(30, Economy::trainingPoints(3));
        $this->assertSame(24, Economy::jobPoints(24700));
        $this->assertSame(0, Economy::jobPoints(999));
    }
}
