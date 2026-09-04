<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Support\Request;

interface MiddlewareInterface
{
    public function handle(
        Request $request,
        array $parameters,
        callable $next
    ): mixed;
}
