<?php

declare(strict_types=1);

namespace Jaroa\Support;

use Throwable;

final class ExceptionHandler
{
    public function handle(Throwable $exception): JsonResponse
    {
        if ($exception instanceof NotFoundException) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Route not found.',
                    ],
                ],
                404
            );
        }

        if ($exception instanceof BadRequestException) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'bad_request',
                        'message' => $exception->getMessage(),
                    ],
                ],
                400
            );
        }

        return new JsonResponse(
            [
                'error' => [
                    'code' => 'internal_server_error',
                    'message' => 'An unexpected error occurred.',
                ],
            ],
            500
        );
    }
}
