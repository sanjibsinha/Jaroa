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

$unique = date('YmdHis');

$post = $repository->create(
    userId: 1,
    title: 'Service Delete Test ' . $unique,
    slug: 'service-delete-test-' . $unique,
    content: 'Temporary post for service delete testing.'
);

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$deleted = $service->delete($post->id);

if ($deleted !== true) {
    throw new RuntimeException(
        'Expected service to delete existing post.'
    );
}

echo 'Service deleted temporary post.' . PHP_EOL;

$deletedAgain = $service->delete($post->id);

if ($deletedAgain !== false) {
    throw new RuntimeException(
        'Expected service to return false for missing post.'
    );
}

echo 'Service correctly returned false for missing post.' . PHP_EOL;

try {
    $service->delete(0);

    throw new RuntimeException(
        'Expected invalid post ID to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Invalid post ID correctly rejected.' . PHP_EOL;
}

echo 'Service delete test passed.' . PHP_EOL;
