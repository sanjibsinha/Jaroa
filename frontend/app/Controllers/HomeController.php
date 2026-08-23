<?php

namespace App\Controllers;

use App\Services\AppService;
use App\Services\PostService;
use App\View;

class HomeController
{
    public function __construct(
        private readonly PostService $posts,
        private readonly AppService $apps
    ) {
    }

    /**
     * Display the application homepage.
     */
    public function index(): void
    {
        $posts = $this->posts->latest(6);
        $apps = $this->apps->all();

        View::render(
            'home',
            [
                'posts' => $posts,
                'apps'  => $apps,
            ]
        );
    }
}