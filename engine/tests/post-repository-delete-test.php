<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$repository = new Jaroa\Repositories\PostRepository(
    $app->database()->connection()
);

$unique = date('YmdHis');

$post = $repository->create(
    userId: 1,
    title: 'Repository Delete Test ' . $unique,
    slug: 'repository-delete-test-' . $unique,
    content: 'Temporary post for repository delete testing.'
);

if ($post->id <= 0) {
    throw new RuntimeException(
        'Expected created post to have a valid ID.'
    );
}

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$deleted = $repository->delete($post->id);

if ($deleted !== true) {
    throw new RuntimeException(
        'Expected existing post to be deleted successfully.'
    );
}

echo 'Repository deleted temporary post.' . PHP_EOL;

$deletedAgain = $repository->delete($post->id);

if ($deletedAgain !== false) {
    throw new RuntimeException(
        'Expected deleting the same post again to return false.'
    );
}

echo 'Repository correctly returned false for missing post.' . PHP_EOL;
echo 'Repository delete test passed.' . PHP_EOL;
