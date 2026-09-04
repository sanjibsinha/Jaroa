<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Support\Request;

final class MiddlewareContext
{
    public function __construct(
        public readonly Request $request,
        public readonly array $parameters = [],
        public readonly ?AuthenticatedUser $authenticated = null,
    ) {
    }

    public function withAuthenticatedUser(
        AuthenticatedUser $authenticated
    ): self {
        return new self(
            $this->request,
            $this->parameters,
            $authenticated
        );
    }
}
