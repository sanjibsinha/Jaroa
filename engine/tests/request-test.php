<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$request = new Jaroa\Support\Request(
    '{"title":"A New Post","slug":"a-new-post","content":"Hello from Jaroa.","user_id":1}'
);

$data = $request->json();

if ($data['title'] !== 'A New Post') {
    throw new RuntimeException(
        'Expected title to be decoded correctly.'
    );
}

if ($request->input('slug') !== 'a-new-post') {
    throw new RuntimeException(
        'Expected input() to return the slug.'
    );
}

if ($request->input('missing') !== null) {
    throw new RuntimeException(
        'Expected missing input to return null.'
    );
}

echo 'JSON body decoded successfully.' . PHP_EOL;
echo 'Request input access works correctly.' . PHP_EOL;

try {
    $invalidRequest = new Jaroa\Support\Request(
        '{"title":'
    );

    $invalidRequest->json();

    throw new RuntimeException(
        'Expected invalid JSON to be rejected.'
    );
} catch (Jaroa\Support\BadRequestException $exception) {
    echo 'Invalid JSON correctly rejected.' . PHP_EOL;
}

echo 'Request test passed.' . PHP_EOL;
