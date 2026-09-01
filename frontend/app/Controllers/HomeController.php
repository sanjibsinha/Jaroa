<?php

namespace App\Controllers;

use App\Services\AppService;
use App\Services\PostService;
use App\Templates\TemplateManager;
use App\View;

class HomeController
{
    private PostService $postService;

    private AppService $appService;

    private TemplateManager $templateManager;

    public function __construct(
        PostService $postService,
        AppService $appService,
        TemplateManager $templateManager
    ) {
        $this->postService = $postService;
        $this->appService = $appService;
        $this->templateManager = $templateManager;
    }

    /**
     * Display the Jaroa landing page.
     */
    public function index(): void
    {
        $posts = $this->postService->latest(
            3
        );

        View::render(
            'home',
            [
                'posts' => $posts,
                'apps' => $this->appService->all(),
                'activeTemplate' =>
                    $this->templateManager->active(),
            ]
        );
    }
}
