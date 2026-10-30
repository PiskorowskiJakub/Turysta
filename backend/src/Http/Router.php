<?php
declare(strict_types=1);

namespace Turysta\Http;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function dispatch(string $method, string $path): array
    {
        $path = rtrim($path, '/') ?: '/';
        if (isset($this->routes[$method][$path])) {
            return ($this->routes[$method][$path])();
        }
        foreach ($this->routes as $routes) {
            if (isset($routes[$path])) {
                return [405, ['error' => 'method_not_allowed']];
            }
        }
        return [404, ['error' => 'not_found']];
    }
}
