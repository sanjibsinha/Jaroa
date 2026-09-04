<?php

declare(strict_types=1);

namespace Jaroa\Providers;

use Jaroa\Middleware\AuthenticationMiddleware;
use Jaroa\Middleware\AuthorizationMiddleware;

use Jaroa\Auth\TokenManager;
use Jaroa\Controllers\AuthController;
use Jaroa\Controllers\PostsController;
use Jaroa\Controllers\UserController;
use Jaroa\Repositories\AuthTokenRepository;
use Jaroa\Repositories\PostRepository;
use Jaroa\Repositories\UserRepository;
use Jaroa\Services\AuthService;
use Jaroa\Services\PostService;
use Jaroa\Services\UserService;
use PDO;

final class ControllerProvider
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function posts(): PostsController
    {
        $repository = new PostRepository(
            $this->pdo
        );

        $service = new PostService(
            $repository
        );

        return new PostsController(
            $service
        );
    }
    public function auth(): AuthController
    {
        $userRepository = new UserRepository(
            $this->pdo
        );

        $tokenRepository = new AuthTokenRepository(
            $this->pdo
        );

        $service = new AuthService(
            $userRepository,
            $tokenRepository,
            new TokenManager()
        );

        return new AuthController(
            $service
        );
    }

    public function users(): UserController
    {
        $userRepository = new UserRepository(
            $this->pdo
        );

        $tokenRepository = new AuthTokenRepository(
            $this->pdo
        );

        $authService = new AuthService(
            $userRepository,
            $tokenRepository,
            new TokenManager()
        );

        $userService = new UserService(
            $userRepository
        );

        return new UserController(
            $userService,
            $authService
        );
    }

    public function authenticationMiddleware(): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            new AuthService(
                new UserRepository(
                    $this->pdo
                ),
                new AuthTokenRepository(
                    $this->pdo
                ),
                new TokenManager()
            )
        );
    }

    public function authorizationMiddleware(): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware();
    }

}
