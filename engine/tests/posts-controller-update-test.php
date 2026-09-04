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
        'title' => 'Controller Updated Post ' . $unique,
        'slug' => 'controller-updated-post-' . $unique,
        'content' => 'Updated through the controller ' . $unique . '.',
    ])
);

$response = $controller->update(1, $request);

if ($response->status() !== 200) {
    throw new RuntimeException(
        'Expected status 200 for successful update.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful update response.'
    );
}

if ($data['data']['id'] !== 1) {
    throw new RuntimeException(
        'Expected updated post ID 1.'
    );
}

echo 'Controller updated post successfully.' . PHP_EOL;

$missingRequest = new Jaroa\Support\Request(
    json_encode([
        'title' => 'Missing',
        'slug' => 'missing-controller-' . $unique,
        'content' => 'Missing.',
    ])
);

$missingResponse = $controller->update(
    999999,
    $missingRequest
);

if ($missingResponse->status() !== 404) {
    throw new RuntimeException(
        'Expected 404 for missing post.'
    );
}

echo 'Controller correctly returned 404 for missing post.' . PHP_EOL;

$invalidRequest = new Jaroa\Support\Request(
    json_encode([
        'title' => '',
        'slug' => 'invalid-controller-' . $unique,
        'content' => 'Invalid.',
    ])
);

$invalidResponse = $controller->update(
    1,
    $invalidRequest
);

if ($invalidResponse->status() !== 422) {
    throw new RuntimeException(
        'Expected 422 for validation failure.'
    );
}

echo 'Controller correctly returned 422 for invalid input.' . PHP_EOL;

echo 'Controller update test passed.' . PHP_EOL;
