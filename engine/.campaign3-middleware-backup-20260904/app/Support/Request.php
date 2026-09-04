<?php

declare(strict_types=1);

namespace Jaroa\Support;

use JsonException;

final class Request
{
    private ?array $json = null;

    public function __construct(
        private readonly ?string $body = null
    ) {
    }

    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $body = $this->body;

        if ($body === null) {
            $body = file_get_contents('php://input');
        }

        if ($body === false || trim($body) === '') {
            $this->json = [];

            return $this->json;
        }

        try {
            $data = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new BadRequestException(
                'Invalid JSON request body.'
            );
        }

        if (!is_array($data)) {
            throw new BadRequestException(
                'JSON request body must be an object.'
            );
        }

        $this->json = $data;

        return $this->json;
    }

    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->json()[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(
            str_replace('-', '_', $name)
        );

        $value = $_SERVER[$serverKey] ?? null;

        return is_string($value) ? $value : null;
    }

    public function bearerToken(): ?string
    {
        $authorization = $this->header(
            'Authorization'
        );

        if ($authorization === null) {
            return null;
        }

        if (
            preg_match(
                '/^Bearer\s+(.+)$/i',
                trim($authorization),
                $matches
            ) !== 1
        ) {
            return null;
        }

        return trim($matches[1]);
    }
}
