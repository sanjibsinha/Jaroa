<?php

declare(strict_types=1);

namespace Jaroa\Controllers;

use Jaroa\Support\JsonResponse;

final class StatusController
{
    public function index(array $parameters): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'application' => 'Jaroa Engine',
            'version' => '0.1.0',
        ]);
    }
}
