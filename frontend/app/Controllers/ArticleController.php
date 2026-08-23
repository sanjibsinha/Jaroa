<?php

namespace App\Controllers;

use App\Services\PostService;
use App\View;

class ArticleController
{
    public function __construct(
        private readonly PostService $posts
    ) {
    }

    /**
     * Display a single article by slug.
     */
    public function show(array $parameters): void
    {
        $slug = $parameters['slug'] ?? '';

        if ('' === $slug) {
            http_response_code(404);
            echo 'Article not found.';
            return;
        }

        try {
            $article = $this->posts->findBySlug($slug);
        } catch (\RuntimeException $exception) {
            http_response_code(404);
            echo 'Article not found.';
            return;
        }

        View::render(
            'article',
            [
                'article' => $article,
            ]
        );
    }
}