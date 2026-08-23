<?php

namespace App\Services;

use App\Api\ApiClient;

class PostService
{
    public function __construct(
        private readonly ApiClient $api
    ) {
    }

    /**
     * Get the latest published posts.
     */
    public function latest(int $perPage = 6): array
    {
        return $this->api->get('/posts', [
            'page'     => 1,
            'per_page' => $perPage,
            'orderby'  => 'date',
            'order'    => 'desc',
        ]);
    }

    /**
     * Get a paginated collection of posts.
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10
    ): array {
        return $this->api->get('/posts', [
            'page'     => max(1, $page),
            'per_page' => min(50, max(1, $perPage)),
            'orderby'  => 'date',
            'order'    => 'desc',
        ]);
    }

    /**
     * Search published posts.
     */
    public function search(
        string $search,
        int $page = 1,
        int $perPage = 10
    ): array {
        return $this->api->get('/posts', [
            'page'     => max(1, $page),
            'per_page' => min(50, max(1, $perPage)),
            'search'   => $search,
            'orderby'  => 'date',
            'order'    => 'desc',
        ]);
    }

    /**
     * Get a single published post by ID.
     */
    public function find(int $id): array
    {
        return $this->api->get("/posts/{$id}");
    }

    /**
     * Get a single published post by slug.
     */
    public function findBySlug(string $slug): array
    {
        return $this->api->get(
            '/posts/slug/' . rawurlencode($slug)
        );
    }
}