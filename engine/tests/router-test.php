<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Jaroa\Support\Router;

$router = new Router();

$router->get('/api/v1/status', function (array $parameters): string {
    return 'status-ok';
});

$router->get('/api/v1/posts/{id}', function (array $parameters): string {
    return 'post-' . $parameters['id'];
});

echo $router->dispatch('GET', '/api/v1/status') . PHP_EOL;
echo $router->dispatch('GET', '/api/v1/posts/42') . PHP_EOL;

$router->post('/api/v1/posts', function (array $parameters): string {
    return 'post-created';
});

$router->get('/api/v1/posts', function (array $parameters): string {
    return 'posts-list';
});

echo $router->dispatch(
    'GET',
    '/api/v1/posts?page=2'
) . PHP_EOL;

echo $router->dispatch(
    'POST',
    '/api/v1/posts'
) . PHP_EOL;
