<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Jaroa\Controllers\StatusController;
use Jaroa\Support\JsonResponse;

$controller = new StatusController();

$response = $controller->index([]);

echo 'Response class: ' . $response::class . PHP_EOL;
echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Data: ' . json_encode(
    $response->data(),
    JSON_UNESCAPED_UNICODE
) . PHP_EOL;
