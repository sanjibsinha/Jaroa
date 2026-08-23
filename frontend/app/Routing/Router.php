<?php

namespace App\Routing;

use RuntimeException;

class Router
{
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * Register a route.
     */
    private function add(
        string $method,
        string $path,
        callable $handler
    ): void {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(
        string $method,
        string $uri
    ): mixed {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $parameters = $this->match(
                $route['path'],
                $path
            );

            if (false !== $parameters) {
                return call_user_func(
                    $route['handler'],
                    $parameters
                );
            }
        }

        throw new RuntimeException(
            "Route not found: {$method} {$path}",
            404
        );
    }

    /**
     * Match a registered route against a request path.
     *
     * Returns extracted parameters on success,
     * or false when the route does not match.
     */
    private function match(
        string $routePath,
        string $requestPath
    ): array|false {
        $routeSegments = $this->segments($routePath);
        $requestSegments = $this->segments($requestPath);

        if (count($routeSegments) !== count($requestSegments)) {
            return false;
        }

        $parameters = [];

        foreach ($routeSegments as $index => $segment) {
            $requestSegment = $requestSegments[$index];

            if (
                strlen($segment) > 1 &&
                $segment[0] === '{' &&
                $segment[strlen($segment) - 1] === '}'
            ) {
                $name = substr(
                    $segment,
                    1,
                    -1
                );

                $parameters[$name] = $requestSegment;

                continue;
            }

            if ($segment !== $requestSegment) {
                return false;
            }
        }

        return $parameters;
    }

    /**
     * Break a URL path into segments.
     */
    private function segments(string $path): array
    {
        $path = trim($path, '/');

        if ('' === $path) {
            return [];
        }

        return explode('/', $path);
    }
}