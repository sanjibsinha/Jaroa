<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Services\AuthService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class AuthenticationMiddleware
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
                        'code' => 'unauthorized',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        $authenticated = $this->auth->authenticateToken(
            $token
        );

        if ($authenticated === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'Invalid or expired authentication token.',
                    ],
                ],
                401
            );
        }

        return $authenticated;
    }
}
