<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function authorize(
        AuthenticatedUser $authenticated,
        array $allowedRoles
    ): ?JsonResponse {
        if (
            !in_array(
                $authenticated->user->role,
                $allowedRoles,
                true
            )
        ) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'You are not authorized to perform this action.',
                    ],
                ],
                403
            );
        }

        return null;
    }
    public function handle(
        Request $request,
        array $parameters,
        callable $next
    ): mixed {
        return $next(
            new MiddlewareContext(
                $request,
                $parameters
            )
        );
    }
}
