<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Support\JsonResponse;

final class AuthorizationMiddleware
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
}
