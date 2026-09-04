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

$post = $service->find(1);

if ($post === null) {
    throw new RuntimeException('Expected post 1 to exist.');
}

echo 'Service found post: ' . $post->title . PHP_EOL;

$missing = $service->find(999999);

if ($missing !== null) {
    throw new RuntimeException(
        'Expected missing post to return null.'
    );
}

echo 'Service correctly returned null for missing post.' . PHP_EOL;
