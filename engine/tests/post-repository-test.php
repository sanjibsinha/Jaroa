<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$posts = $repository->all();

echo 'Post count: ' . count($posts) . PHP_EOL;

foreach ($posts as $post) {
    echo $post->id . ': ' . $post->title . PHP_EOL;
}
