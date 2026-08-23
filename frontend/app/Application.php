<?php

namespace App;

use App\Api\ApiClient;
use App\Controllers\ArticleController;
use App\Controllers\HomeController;
use App\Routing\Router;
use App\Services\AppService;
use App\Services\PostService;

class Application
{
    private ApiClient $api;

    private PostService $postService;

    private AppService $appService;

    private Router $router;

    public function __construct(array $config)
    {
        $this->api = new ApiClient(
            $config['api']['base_url']
        );

        $this->postService = new PostService(
            $this->api
        );

        $this->appService = new AppService(
            $this->api
        );

        $this->router = new Router();

        /*
         * Home controller.
         */
        $homeController = new HomeController(
            $this->postService,
            $this->appService
        );

        /*
         * Home route.
         */
        $this->router->get(
            '/',
            [$homeController, 'index']
        );

        /*
         * Article controller.
         */
        $articleController = new ArticleController(
            $this->postService
        );

        /*
         * Article route.
         */
        $this->router->get(
            '/articles/{slug}',
            [$articleController, 'show']
        );
    }

    /**
     * Handle the current HTTP request.
     */
    public function run(): mixed
    {
        return $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }
}