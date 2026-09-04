<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$unique = date('YmdHis');

$title = 'Repository Test Post ' . $unique;
$slug = 'repository-test-' . $unique;
$content = 'Repository create test content ' . $unique;

$post = $repository->create(
    userId: 1,
    title: $title,
    slug: $slug,
    content: $content
);

if ($post->id <= 0) {
    throw new RuntimeException(
        'Expected the created post to have a valid ID.'
    );
}

if ($post->userId !== 1) {
    throw new RuntimeException(
        'Expected the created post to belong to user 1.'
    );
}

if ($post->title !== $title) {
    throw new RuntimeException(
        'Created post title does not match.'
    );
}

if ($post->slug !== $slug) {
    throw new RuntimeException(
        'Created post slug does not match.'
    );
}

if ($post->content !== $content) {
    throw new RuntimeException(
        'Created post content does not match.'
    );
}

echo 'Created post ID: ' . $post->id . PHP_EOL;
echo 'Created post title: ' . $post->title . PHP_EOL;
echo 'Created post slug: ' . $post->slug . PHP_EOL;
echo 'Repository create test passed.' . PHP_EOL;
