<?php
declare(strict_types=1);

namespace Turysta\Http;

final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $regex = preg_replace_callback('#\{(\w+)\}|([^{]+)#', function (array $m): string {
            if (($m[1] ?? '') !== '') {
                return '(?P<' . $m[1] . '>[^/]+)';
            }
            return preg_quote($m[2], '#');
        }, $path);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . $regex . '$#', 'handler' => $handler];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function dispatch(string $method, string $path, ?Request $request = null): array
    {
        $path = rtrim($path, '/') ?: '/';
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return ($route['handler'])($request, $params);
        }
        return $pathMatched
            ? [405, ['error' => ['code' => 'method_not_allowed', 'message' => 'Metoda niedozwolona.']]]
            : [404, ['error' => ['code' => 'not_found', 'message' => 'Nie znaleziono.']]];
    }
}
