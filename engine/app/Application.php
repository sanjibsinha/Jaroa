<?php

declare(strict_types=1);

namespace Jaroa;

use Jaroa\Support\Database;
use Jaroa\Support\ExceptionHandler;
use Jaroa\Support\JsonResponse;
use Jaroa\Middleware\MiddlewarePipeline;
use Jaroa\Support\Router;
use Throwable;

final class Application
{
    private Database $database;

    private Router $router;

    private ExceptionHandler $exceptionHandler;

    public function __construct()
    {
        $this->database = new Database(
            require dirname(__DIR__) . '/config/database.php'
        );

        $this->router = new Router(new MiddlewarePipeline());

        $this->exceptionHandler = new ExceptionHandler();

        $this->loadRoutes();
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        try {
            $response = $this->router->dispatch($method, $uri);
        } catch (Throwable $exception) {
            $response = $this->exceptionHandler->handle($exception);
        }

        if ($response instanceof JsonResponse) {
            $response->send();
            return;
        }

        if ($response !== null) {
            echo $response;
        }
    }

    private function loadRoutes(): void
    {
        $registerRoutes = require dirname(__DIR__) . '/routes/api.php';

        $registerRoutes($this->router, $this);
    }
}
