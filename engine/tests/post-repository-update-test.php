<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$post = $repository->find(1);

if ($post === null) {
    throw new RuntimeException(
        'Expected post 1 to exist before update.'
    );
}

$unique = date('YmdHis');

$updated = $repository->update(
    id: 1,
    title: 'Updated First Jaroa Post ' . $unique,
    slug: 'updated-first-jaroa-post-' . $unique,
    content: 'This post was updated by the repository test ' . $unique . '.'
);

if ($updated === null) {
    throw new RuntimeException(
        'Expected updated post to be returned.'
    );
}

if ($updated->id !== 1) {
    throw new RuntimeException(
        'Expected updated post ID to remain 1.'
    );
}

if ($updated->title !== 'Updated First Jaroa Post ' . $unique) {
    throw new RuntimeException(
        'Updated title does not match.'
    );
}

if ($updated->slug !== 'updated-first-jaroa-post-' . $unique) {
    throw new RuntimeException(
        'Updated slug does not match.'
    );
}

if (
    $updated->content !==
    'This post was updated by the repository test ' . $unique . '.'
) {
    throw new RuntimeException(
        'Updated content does not match.'
    );
}

$missing = $repository->update(
    id: 999999,
    title: 'Missing',
    slug: 'missing-' . $unique,
    content: 'Missing post.'
);

if ($missing !== null) {
    throw new RuntimeException(
        'Expected update of missing post to return null.'
    );
}

echo 'Repository updated post ID: ' . $updated->id . PHP_EOL;
echo 'Repository correctly returned null for missing post.' . PHP_EOL;
echo 'Repository update test passed.' . PHP_EOL;
