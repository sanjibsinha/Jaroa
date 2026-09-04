<?php

declare(strict_types=1);

namespace Jaroa\Support;

use Jaroa\Support\NotFoundException;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function add(
        string $method,
        string $path,
        callable $handler
    ): void {
        $method = strtoupper($method);

        if ($path === '') {
            throw new RuntimeException('Route path cannot be empty.');
        }

        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(
        string $method,
        string $uri
    ): mixed {
        $method = strtoupper($method);

        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $parameters = $this->match($route, $path);

            if ($parameters !== null) {
                return $handler($parameters);
            }
        }

        throw new NotFoundException(
            "Route not found: {$method} {$path}"
        );
    }

    private function match(
        string $route,
        string $path
    ): ?array {
        $routePattern = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[^/]+)',
            $route
        );

        if ($routePattern === null) {
            return null;
        }

        $routePattern = '#^' . $routePattern . '$#';

        if (
            preg_match(
                $routePattern,
                $path,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
