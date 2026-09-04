<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$service = new Jaroa\Services\PostService(
    $repository
);

$controller = new Jaroa\Controllers\PostsController(
    $service
);

$response = $controller->index([]);

echo 'Response class: ' . $response::class . PHP_EOL;
echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Data: ' . json_encode(
    $response->data(),
    JSON_UNESCAPED_UNICODE
) . PHP_EOL;
