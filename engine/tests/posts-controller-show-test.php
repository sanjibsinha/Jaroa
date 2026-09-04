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

$response = $controller->show([
    'id' => '1',
]);

if ($response->status() !== 200) {
    throw new RuntimeException(
        'Expected status 200 for an existing post.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful response.'
    );
}

if ($data['data']['id'] !== 1) {
    throw new RuntimeException(
        'Expected post ID 1.'
    );
}

echo 'Controller returned existing post successfully.' . PHP_EOL;

$missingResponse = $controller->show([
    'id' => '999999',
]);

if ($missingResponse->status() !== 404) {
    throw new RuntimeException(
        'Expected status 404 for a missing post.'
    );
}

$missingData = $missingResponse->data();

if (
    $missingData['error']['code'] !== 'not_found'
) {
    throw new RuntimeException(
        'Expected not_found error code.'
    );
}

if (
    $missingData['error']['message'] !== 'Post not found.'
) {
    throw new RuntimeException(
        'Expected Post not found. message.'
    );
}

echo 'Controller returned 404 for missing post.' . PHP_EOL;
