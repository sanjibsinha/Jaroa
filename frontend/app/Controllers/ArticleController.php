<?php

namespace App\Controllers;

use App\Exceptions\NotFoundException;
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
            throw new NotFoundException(
                'Article slug is missing.'
            );
        }

        $article = $this->posts->findBySlug($slug);

        View::render(
            'article',
            [
                'article' => $article,
            ]
        );
    }
}