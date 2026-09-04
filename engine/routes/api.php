<?php

declare(strict_types=1);

use Jaroa\Application;
use Jaroa\Controllers\StatusController;
use Jaroa\Providers\ControllerProvider;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;
use Jaroa\Support\Router;

return function (
    Router $router,
    Application $application
): void {
    $statusController = new StatusController();

    $router->get(
        '/api/v1/status',
        [$statusController, 'index']
    );

    $controllerProvider = new ControllerProvider(
        $application->database()->connection()
    );

    $postsController = $controllerProvider->posts();

    $authController = $controllerProvider->auth();

    $authenticationMiddleware =
        $controllerProvider->authenticationMiddleware();

    $authorizationMiddleware =
        $controllerProvider->authorizationMiddleware();

    /*
     * Public authentication endpoints.
     */

    $router->post(
        '/api/v1/auth/register',
        static function () use ($authController): mixed {
            return $authController->register(
                new Request()
            );
        }
    );

    $router->post(
        '/api/v1/auth/login',
        static function () use ($authController): mixed {
            return $authController->login(
                new Request()
            );
        }
    );

    $router->get(
        '/api/v1/auth/me',
        static function () use ($authController): mixed {
            return $authController->me(
                new Request()
            );
        }
    );

    $router->post(
        '/api/v1/auth/logout',
        static function () use ($authController): mixed {
            return $authController->logout(
                new Request()
            );
        }
    );

    /*
     * User management endpoints.
     */

    $usersController = $controllerProvider->users();

    $router->get(
        '/api/v1/users/{id}',
        static function (array $parameters) use (
            $usersController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    (int) $parameters['id']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $usersController->show(
                (int) $parameters['id'],
                $authenticated
            );
        }
    );

    $router->put(
        '/api/v1/users/{id}',
        static function (array $parameters) use (
            $usersController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    (int) $parameters['id']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $usersController->update(
                (int) $parameters['id'],
                $request
            );
        }
    );

    $router->delete(
        '/api/v1/users/{id}',
        static function (array $parameters) use (
            $usersController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $userId = (int) $parameters['id'];

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    $userId
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            if (
                $authenticated->user->role === 'admin' &&
                $authenticated->user->id === $userId
            ) {
                return new JsonResponse(
                    [
                        'error' => [
                            'code' => 'forbidden',
                            'message' => 'Administrators cannot delete themselves.',
                        ],
                    ],
                    403
                );
            }

            return $usersController->delete($userId);
        }
    );

    $router->post(
        '/api/v1/users/{id}/password',
        static function (array $parameters) use (
            $usersController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorizeSelf(
                    $authenticated,
                    (int) $parameters['id']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $usersController->changePassword(
                (int) $parameters['id'],
                $request
            );
        }
    );

    /*
     * Public post endpoints.
     */

    $router->get(
        '/api/v1/posts',
        static function () use ($postsController): mixed {
            return $postsController->index(
                [],
                new Request()
            );
        }
    );

    $router->get(
        '/api/v1/posts/{id}',
        [$postsController, 'show']
    );

    /*
     * Editor/admin post creation.
     */

    $router->post(
        '/api/v1/posts',
        static function () use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->store(
                $request
            );
        }
    );

    /*
     * Editor/admin post update.
     */

    $router->put(
        '/api/v1/posts/{id}',
        static function (array $parameters) use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->update(
                (int) $parameters['id'],
                $request
            );
        }
    );

    /*
     * Editor/admin post deletion.
     */

    $router->delete(
        '/api/v1/posts/{id}',
        static function (array $parameters) use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->delete(
                (int) $parameters['id']
            );
        }
    );
};
