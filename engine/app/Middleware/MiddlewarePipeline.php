<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Support\Request;

final class MiddlewarePipeline
{
    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private readonly array $middleware = []
    ) {
        foreach ($middleware as $item) {
            if (!$item instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException(
                    'All pipeline middleware must implement MiddlewareInterface.'
                );
            }
        }
    }

    public function handle(
        Request $request,
        array $parameters,
        callable $destination
    ): mixed {
        $index = 0;
        $middleware = $this->middleware;

        $next = function (MiddlewareContext $context) use (
            &$next,
            &$index,
            $middleware,
            $destination
        ): mixed {
            if ($index >= count($middleware)) {
                return $destination($context);
            }

            $current = $middleware[$index];
            $index++;

            return $current->handle(
                $context,
                $next
            );
        };

        return $next(
            new MiddlewareContext(
                $request,
                $parameters
            )
        );
    }
}
