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

$unique = date('YmdHis');

$request = new Jaroa\Support\Request(
    json_encode([
        'user_id' => 1,
        'title' => 'Controller Test Post ' . $unique,
        'slug' => 'controller-test-' . $unique,
        'content' => 'Controller store test content ' . $unique,
    ])
);

$response = $controller->store($request);

if ($response->status() !== 201) {
    throw new RuntimeException(
        'Expected status 201 for successful creation.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful response.'
    );
}

if ($data['data']['id'] <= 0) {
    throw new RuntimeException(
        'Expected created post to have a valid ID.'
    );
}

if (
    $data['data']['title'] !==
    'Controller Test Post ' . $unique
) {
    throw new RuntimeException(
        'Created post title does not match.'
    );
}

echo 'Controller created post ID: '
    . $data['data']['id']
    . PHP_EOL;

echo 'Controller returned HTTP 201.' . PHP_EOL;

$invalidRequest = new Jaroa\Support\Request(
    json_encode([
        'user_id' => 1,
        'title' => '',
        'slug' => 'invalid-controller-test-' . $unique,
        'content' => 'Invalid controller test.',
    ])
);

$invalidResponse = $controller->store($invalidRequest);

if ($invalidResponse->status() !== 422) {
    throw new RuntimeException(
        'Expected status 422 for validation failure.'
    );
}

$invalidData = $invalidResponse->data();

if (
    $invalidData['error']['code'] !==
    'validation_failed'
) {
    throw new RuntimeException(
        'Expected validation_failed error code.'
    );
}

echo 'Controller correctly returned 422 for invalid input.'
    . PHP_EOL;

echo 'Controller store test passed.' . PHP_EOL;
