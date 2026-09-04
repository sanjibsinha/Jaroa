<?php

declare(strict_types=1);

namespace Jaroa\Auth;

final class TokenManager
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
