<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Jaroa\Support\JsonResponse;

$response = new JsonResponse(
    [
        'status' => 'ok',
        'application' => 'Jaroa Engine',
        'version' => '0.1.0',
    ]
);

echo 'Status: ' . $response->status() . PHP_EOL;

echo 'Data: ' . json_encode(
    $response->data(),
    JSON_UNESCAPED_UNICODE
) . PHP_EOL;


$created = new JsonResponse(
    [
        'message' => 'Resource created',
    ],
    201
);

echo 'Created status: ' . $created->status() . PHP_EOL;
