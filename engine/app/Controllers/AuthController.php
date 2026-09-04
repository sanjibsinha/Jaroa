<?php

declare(strict_types=1);

namespace Jaroa\Controllers;

use InvalidArgumentException;
use Jaroa\Services\AuthService;
use Jaroa\Support\BadRequestException;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth
    ) {
    }

    public function register(
        Request $request
    ): JsonResponse {
        try {
            $user = $this->auth->register(
                (string) $request->input('name', ''),
                (string) $request->input('email', ''),
                (string) $request->input('password', '')
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        return new JsonResponse(
            [
                'data' => $user->publicData(),
            ],
            201
        );
    }

    public function login(
        Request $request
    ): JsonResponse {
        try {
            $result = $this->auth->login(
                (string) $request->input('email', ''),
                (string) $request->input('password', '')
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'authentication_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                401
            );
        }

        return new JsonResponse(
            [
                'data' => [
                    'token' => $result['token'],
                    'expires_at' => $result['expires_at'],
                    'user' => $result['user']->publicData(),
                ],
            ]
        );
    }

    public function me(
        Request $request
    ): JsonResponse {
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

        $authenticated = $this->auth->authenticate(
            $token
        );

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

        return new JsonResponse(
            [
                'data' => $authenticated->user->publicData(),
            ]
        );
    }

    public function logout(
        Request $request
    ): JsonResponse {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new BadRequestException(
                'Bearer authentication token is required.'
            );
        }

        if (!$this->auth->logout($token)) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication token is invalid.',
                    ],
                ],
                401
            );
        }

        return new JsonResponse(
            [
                'data' => [
                    'logged_out' => true,
                ],
            ]
        );
    }
}
