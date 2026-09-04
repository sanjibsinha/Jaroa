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
        return $this->normalizeCollection(
            $this->api->get('/posts', [
                'page'  => 1,
                'limit' => min(50, max(1, $perPage)),
                'sort'  => 'created_at',
                'order' => 'desc',
            ])
        );
    }

    /**
     * Get a paginated collection of posts.
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10
    ): array {
        return $this->normalizeCollection(
            $this->api->get('/posts', [
                'page'  => max(1, $page),
                'limit' => min(50, max(1, $perPage)),
                'sort'  => 'created_at',
                'order' => 'desc',
            ])
        );
    }

    /**
     * Search published posts.
     */
    public function search(
        string $search,
        int $page = 1,
        int $perPage = 10
    ): array {
        return $this->normalizeCollection(
            $this->api->get('/posts', [
                'page'   => max(1, $page),
                'limit'  => min(50, max(1, $perPage)),
                'search' => $search,
                'sort'   => 'created_at',
                'order'  => 'desc',
            ])
        );
    }

    /**
     * Get a single published post by ID.
     */
    public function find(int $id): array
    {
        return $this->normalizePostResponse(
            $this->api->get("/posts/{$id}")
        );
    }

    /**
     * Get a single published post by slug.
     */
    public function findBySlug(string $slug): array
    {
        return $this->normalizePostResponse(
            $this->api->get(
                '/posts/slug/' . rawurlencode($slug)
            )
        );
    }

    private function normalizeCollection(array $response): array
    {
        $response['data'] = array_map(
            fn (array $post): array => $this->normalizePost($post),
            $response['data'] ?? []
        );

        return $response;
    }

    private function normalizePostResponse(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            $response['data'] = $this->normalizePost(
                $response['data']
            );
        }

        return $response;
    }

    private function normalizePost(array $post): array
    {
        $post['date'] = $post['created_at'] ?? '';

        $plainText = trim(
            preg_replace(
                '/\s+/',
                ' ',
                html_entity_decode(
                    strip_tags((string) ($post['content'] ?? ''))
                )
            ) ?? ''
        );

        $post['excerpt'] = strlen($plainText) > 180
            ? substr($plainText, 0, 177) . '...'
            : $plainText;

        return $post;
    }
}
