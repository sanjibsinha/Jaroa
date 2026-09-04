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
    throw new RuntimeException(
        'Expected post 1 to exist before service update.'
    );
}

$unique = date('YmdHis');

$updated = $service->update(
    id: 1,
    title: 'Service Updated Post ' . $unique,
    slug: 'service-updated-post-' . $unique,
    content: 'Updated through the service layer ' . $unique . '.'
);

if ($updated === null) {
    throw new RuntimeException(
        'Expected service update to return a post.'
    );
}

if ($updated->id !== 1) {
    throw new RuntimeException(
        'Expected updated post ID to remain 1.'
    );
}

echo 'Service updated post ID: ' . $updated->id . PHP_EOL;

$missing = $service->update(
    id: 999999,
    title: 'Missing',
    slug: 'missing-service-' . $unique,
    content: 'Missing.'
);

if ($missing !== null) {
    throw new RuntimeException(
        'Expected missing service update to return null.'
    );
}

echo 'Service correctly returned null for missing post.' . PHP_EOL;

foreach ([
    'title' => ['', 'valid-slug', 'valid-content'],
    'slug' => ['Valid title', '', 'valid-content'],
    'content' => ['Valid title', 'valid-slug', ''],
] as $field => $values) {
    try {
        $service->update(
            id: 1,
            title: $values[0],
            slug: $values[1],
            content: $values[2]
        );

        throw new RuntimeException(
            "Expected empty {$field} to be rejected."
        );
    } catch (InvalidArgumentException $exception) {
        echo "Empty {$field} correctly rejected." . PHP_EOL;
    }
}

try {
    $service->update(
        id: 0,
        title: 'Valid title',
        slug: 'valid-id-test-' . $unique,
        content: 'Valid content'
    );

    throw new RuntimeException(
        'Expected invalid post ID to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Invalid post ID correctly rejected.' . PHP_EOL;
}

echo 'Service update test passed.' . PHP_EOL;
