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
        private readonly array $middleware
    ) {
    }

    public function handle(
        Request $request,
        array $parameters,
        callable $destination
    ): mixed {
        $context = new MiddlewareContext(
            $request,
            $parameters
        );

        $middleware = $this->middleware;

        $runner = function (MiddlewareContext $context) use (
            &$runner,
            &$middleware,
            $destination
        ): mixed {
            if ($middleware === []) {
                return $destination($context);
            }

            $current = array_shift($middleware);

            if (!$current instanceof MiddlewareInterface) {
                throw new \RuntimeException(
                    'Invalid middleware supplied to pipeline.'
                );
            }

            return $current->handle(
                $context->request,
                $context->parameters,
                function (MiddlewareContext $nextContext) use (
                    $runner
                ): mixed {
                    return $runner($nextContext);
                }
            );
        };

        return $runner($context);
    }
}
