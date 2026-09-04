<?php

declare(strict_types=1);

namespace Jaroa\Support;

use JsonException;

final class JsonResponse
{
    public function __construct(
        private readonly mixed $data,
        private readonly int $status = 200
    ) {
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        http_response_code($this->status);

        header('Content-Type: application/json; charset=utf-8');

        try {
            echo json_encode(
                $this->data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            http_response_code(500);

            echo json_encode(
                [
                    'error' => [
                        'code' => 'json_encoding_failed',
                        'message' => 'Unable to encode response as JSON.',
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );
        }
    }
}
