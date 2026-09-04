<?php

declare(strict_types=1);

namespace Jaroa\Support;

use Jaroa\Middleware\MiddlewareContext;
use Jaroa\Middleware\MiddlewareInterface;
use Jaroa\Middleware\MiddlewarePipeline;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly MiddlewarePipeline $pipeline = new MiddlewarePipeline()
    ) {
    }

    public function get(
        string $path,
        callable $handler,
        array $middleware = []
    ): void {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(
        string $path,
        callable $handler,
        array $middleware = []
    ): void {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(
        string $path,
        callable $handler,
        array $middleware = []
    ): void {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(
        string $path,
        callable $handler,
        array $middleware = []
    ): void {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(
        string $method,
        string $path,
        callable $handler,
        array $middleware = []
    ): void {
        $method = strtoupper($method);

        if ($path === '') {
            throw new RuntimeException('Route path cannot be empty.');
        }

        foreach ($middleware as $item) {
            if (!$item instanceof MiddlewareInterface) {
                throw new RuntimeException(
                    'Invalid middleware supplied to route.'
                );
            }
        }

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
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

        foreach ($this->routes[$method] ?? [] as $route => $definition) {
            $parameters = $this->match($route, $path);

            if ($parameters === null) {
                continue;
            }

            $request = new Request();

            $pipeline = new MiddlewarePipeline(
                $definition['middleware']
            );

            return $pipeline->handle(
                $request,
                $parameters,
                static function (MiddlewareContext $context) use (
                    $definition
                ): mixed {
                    return ($definition['handler'])(
                        $context->parameters,
                        $context
                    );
                }
            );
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
