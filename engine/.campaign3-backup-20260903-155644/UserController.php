<?php

declare(strict_types=1);

namespace Jaroa\Controllers;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Services\AuthService;
use Jaroa\Services\UserService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;
use InvalidArgumentException;

final class UserController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuthService $auth
    ) {
    }

    public function show(
        int $id,
        AuthenticatedUser $authenticated
    ): JsonResponse {
        $user = $this->users->find($id);

        if ($user === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'User not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }

    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        try {
            $user = $this->users->updateProfile(
                $id,
                (string) $request->input('name', ''),
                (string) $request->input('email', '')
            );
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'User not found.') {
                return new JsonResponse(
                    [
                        'error' => [
                            'code' => 'not_found',
                            'message' => 'User not found.',
                        ],
                    ],
                    404
                );
            }

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

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $deleted = $this->users->delete($id);
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

        if (!$deleted) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'User not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => [
                'deleted' => true,
                'id' => $id,
            ],
        ]);
    }

    public function changePassword(
        int $id,
        Request $request
    ): JsonResponse {
        $currentPassword = (string) $request->input(
            'current_password',
            ''
        );

        $newPassword = (string) $request->input(
            'new_password',
            ''
        );

        try {
            $user = $this->auth->changePassword(
                $id,
                $currentPassword,
                $newPassword
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

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }
}
