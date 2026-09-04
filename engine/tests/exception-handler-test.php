<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Jaroa\Support\ExceptionHandler;
use Jaroa\Support\JsonResponse;
$handler = new ExceptionHandler();

$response = $handler->handle(
    new \RuntimeException('Test exception')
);

echo 'Response class: ' . $response::class . PHP_EOL;
echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Data: ' . json_encode(
    $response->data(),
    JSON_UNESCAPED_UNICODE
) . PHP_EOL;
