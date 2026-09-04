<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Services\AuthService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthService $auth
    ) {
    }

    public function authenticate(
        Request $request
    ): AuthenticatedUser|JsonResponse {
        $token = $request->bearerToken();

        if ($token === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        $authenticated = $this->auth->authenticate($token);

        if ($authenticated === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        return $authenticated;
    }

    public function handle(
        Request $request,
        array $parameters,
        callable $next
    ): mixed {
        $authenticated = $this->authenticate($request);

        if ($authenticated instanceof JsonResponse) {
            return $authenticated;
        }

        return $next(
            new MiddlewareContext(
                $request,
                $parameters,
                $authenticated
            )
        );
    }
}
