<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$post = $repository->find(1);

if ($post === null) {
    throw new RuntimeException('Expected post 1 to exist.');
}

echo 'Found post: ' . $post->title . PHP_EOL;

$missing = $repository->find(999999);

if ($missing !== null) {
    throw new RuntimeException('Expected missing post to return null.');
}

echo 'Missing post correctly returned null.' . PHP_EOL;
