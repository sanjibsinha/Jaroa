<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

interface MiddlewareInterface
{
    public function handle(
        MiddlewareContext $context,
        callable $next
    ): mixed;
}
