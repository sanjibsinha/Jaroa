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

$title = 'Service Test Post ' . $unique;
$slug = 'service-test-' . $unique;
$content = 'Service create test content ' . $unique;

$post = $service->create(
    userId: 1,
    title: $title,
    slug: $slug,
    content: $content
);

if ($post->id <= 0) {
    throw new RuntimeException(
        'Expected created post to have a valid ID.'
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

echo 'Service created post ID: ' . $post->id . PHP_EOL;

try {
    $service->create(
        userId: 1,
        title: '',
        slug: $slug . '-empty-title',
        content: $content
    );

    throw new RuntimeException(
        'Expected empty title to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Empty title correctly rejected.' . PHP_EOL;
}

try {
    $service->create(
        userId: 1,
        title: $title,
        slug: '',
        content: $content
    );

    throw new RuntimeException(
        'Expected empty slug to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Empty slug correctly rejected.' . PHP_EOL;
}

try {
    $service->create(
        userId: 1,
        title: $title,
        slug: $slug . '-empty-content',
        content: ''
    );

    throw new RuntimeException(
        'Expected empty content to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Empty content correctly rejected.' . PHP_EOL;
}

try {
    $service->create(
        userId: 0,
        title: $title,
        slug: $slug . '-invalid-user',
        content: $content
    );

    throw new RuntimeException(
        'Expected invalid user ID to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Invalid user ID correctly rejected.' . PHP_EOL;
}

echo 'Service create test passed.' . PHP_EOL;
