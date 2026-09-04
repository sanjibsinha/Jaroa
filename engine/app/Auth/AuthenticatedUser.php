<?php

declare(strict_types=1);

namespace Jaroa\Auth;

use Jaroa\Models\User;

final readonly class AuthenticatedUser
{
    public function __construct(
        public User $user,
        public int $tokenId,
    ) {
    }
}
