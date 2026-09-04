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

$post = $repository->create(
    userId: 1,
    title: 'Controller Delete Test ' . $unique,
    slug: 'controller-delete-test-' . $unique,
    content: 'Temporary post for controller delete testing.'
);

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$response = $controller->delete($post->id);

if ($response->status() !== 200) {
    throw new RuntimeException(
        'Expected status 200 for successful delete.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful delete response.'
    );
}

if ($data['data']['deleted'] !== true) {
    throw new RuntimeException(
        'Expected deleted to be true.'
    );
}

if ($data['data']['id'] !== $post->id) {
    throw new RuntimeException(
        'Expected deleted post ID in response.'
    );
}

echo 'Controller deleted temporary post successfully.' . PHP_EOL;

$missingResponse = $controller->delete($post->id);

if ($missingResponse->status() !== 404) {
    throw new RuntimeException(
        'Expected 404 when deleting missing post.'
    );
}

echo 'Controller correctly returned 404 for missing post.' . PHP_EOL;

$invalidResponse = $controller->delete(0);

if ($invalidResponse->status() !== 422) {
    throw new RuntimeException(
        'Expected 422 for invalid post ID.'
    );
}

echo 'Controller correctly returned 422 for invalid post ID.' . PHP_EOL;

echo 'Controller delete test passed.' . PHP_EOL;
