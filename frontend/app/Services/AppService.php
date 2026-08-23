<?php

namespace App\Services;

use App\Api\ApiClient;

class AppService
{
    public function __construct(
        private readonly ApiClient $api
    ) {
    }

    /**
     * Get all published applications.
     */
    public function all(): array
    {
        return $this->api->get('/apps');
    }
}