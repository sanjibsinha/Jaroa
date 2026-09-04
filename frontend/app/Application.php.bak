<?php

namespace App;

use App\Api\ApiClient;
use App\Controllers\ArticleController;
use App\Controllers\HomeController;
use App\Exceptions\NotFoundException;
use App\Routing\Router;
use App\Services\AppService;
use App\Services\PostService;
use App\Controllers\TemplateController;
use App\Controllers\TemplateShowcaseController;
use App\Templates\TemplateInstaller;
use App\Templates\TemplateManager;

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

        View::share([
            'appName' => $config['name'],
        ]);

        $this->postService = new PostService(
            $this->api
        );

        $this->appService = new AppService(
            $this->api
        );

        $templateManager = new TemplateManager(
            __DIR__ . '/../templates',
            __DIR__ . '/../site/themes',
            __DIR__ . '/../storage/active-template'
        );

        $templateInstaller = new TemplateInstaller(
            __DIR__ . '/../templates',
            __DIR__ . '/../site/themes'
        );

        View::setTemplateManager(
            $templateManager
        );

        $this->router = new Router();

        /*
         * Template installer.
         */
        $templateController = new TemplateController(
            $templateManager,
            $templateInstaller
        );

        $this->router->get(
            '/templates',
            [$templateController, 'index']
        );

        $this->router->post(
            '/templates/install/{slug}',
            [$templateController, 'install']
        );

        $this->router->post(
            '/templates/activate/{slug}',
            [$templateController, 'activate']
        );



        /*
         * Home controller.
         */
        $homeController = new HomeController(
            $this->postService,
            $this->appService,
            $templateManager
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


        /*
         * Template showcase routes.
         */
        $templateShowcaseController =
            new TemplateShowcaseController(
                $templateManager
            );

        foreach (
            $templateManager->available()
            as $template
        ) {
            $showcase = $template['showcase'] ?? null;
            $slug = $template['slug'] ?? null;

            if (
                !is_string($showcase) ||
                '' === $showcase ||
                !is_string($slug) ||
                '' === $slug
            ) {
                continue;
            }

            $this->router->get(
                $showcase,
                static function () use (
                    $templateShowcaseController,
                    $slug
                ): void {
                    $templateShowcaseController->show(
                        [
                            'slug' => $slug,
                        ]
                    );
                }
            );
        }


    }

    /**
     * Handle the current HTTP request.
     */
    public function run(): mixed
    {
        try {
            return $this->router->dispatch(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/'
            );
        } catch (NotFoundException $exception) {
            http_response_code(404);

            View::render(
                '404',
                [],
                'app'
            );

            return null;
        }
    }
}
