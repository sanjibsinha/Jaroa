<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$router = $app->router();

$response = $router->dispatch(
    'GET',
    '/api/v1/status'
);

echo 'Response class: ' . $response::class . PHP_EOL;
echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Data: ' . json_encode(
    $response->data(),
    JSON_UNESCAPED_UNICODE
) . PHP_EOL;
