<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Turysta\Http\Router;

final class RouterTest extends TestCase
{
    public function testKnownRoute(): void
    {
        $r = new Router();
        $r->get('/health', fn() => [200, ['status' => 'ok']]);
        $this->assertSame([200, ['status' => 'ok']], $r->dispatch('GET', '/health'));
    }

    public function testUnknownRouteIs404(): void
    {
        $this->assertSame(404, (new Router())->dispatch('GET', '/x')[0]);
    }

    public function testWrongMethodIs405(): void
    {
        $r = new Router();
        $r->get('/health', fn() => [200, []]);
        $this->assertSame(405, $r->dispatch('POST', '/health')[0]);
    }
}
