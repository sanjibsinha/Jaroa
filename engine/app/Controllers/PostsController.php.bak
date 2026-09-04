<?php

declare(strict_types=1);

namespace Jaroa\Controllers;

use InvalidArgumentException;
use Jaroa\Services\PostService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class PostsController
{
    public function __construct(
        private readonly PostService $service
    ) {
    }

    public function index(
        array $parameters = [],
        ?Request $request = null
    ): JsonResponse {
        if ($request === null) {
            $posts = $this->service->all();

            return new JsonResponse([
                'data' => array_map(
                    fn ($post): array => $this->resource($post),
                    $posts
                ),
            ]);
        }

        try {
            $page = $this->positiveIntegerQuery(
                $request->query('page', 1),
                'page'
            );

            $limit = $this->positiveIntegerQuery(
                $request->query('limit', 20),
                'limit'
            );

            $userIdValue = $request->query('user_id');

            $userId = $userIdValue === null || $userIdValue === ''
                ? null
                : $this->positiveIntegerQuery($userIdValue, 'user_id');

            $search = $request->query('search');

            if ($search !== null && !is_string($search)) {
                throw new InvalidArgumentException(
                    'Search must be a string.'
                );
            }

            $sort = $request->query('sort', 'created_at');

            if (!is_string($sort)) {
                throw new InvalidArgumentException(
                    'Sort must be a string.'
                );
            }

            $order = $request->query('order', 'desc');

            if (!is_string($order)) {
                throw new InvalidArgumentException(
                    'Order must be a string.'
                );
            }

            $result = $this->service->paginate(
                page: $page,
                limit: $limit,
                userId: $userId,
                search: $search,
                sort: $sort,
                order: $order
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        $total = $result['total'];

        return new JsonResponse([
            'data' => array_map(
                fn ($post): array => $this->resource($post),
                $result['posts']
            ),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total === 0
                    ? 0
                    : (int) ceil($total / $limit),
            ],
        ]);
    }

    public function show(array $parameters): JsonResponse
    {
        $id = (int) $parameters['id'];

        $post = $this->service->find($id);

        if ($post === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Post not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => $this->resource($post),
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->delete($id);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        if (!$deleted) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Post not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => [
                'deleted' => true,
                'id' => $id,
            ],
        ]);
    }

    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        $data = $request->json();

        try {
            $post = $this->service->update(
                id: $id,
                title: (string) ($data['title'] ?? ''),
                slug: (string) ($data['slug'] ?? ''),
                content: (string) ($data['content'] ?? '')
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        if ($post === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Post not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => $this->resource($post),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->json();

        try {
            $post = $this->service->create(
                userId: (int) ($data['user_id'] ?? 0),
                title: (string) ($data['title'] ?? ''),
                slug: (string) ($data['slug'] ?? ''),
                content: (string) ($data['content'] ?? '')
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        return new JsonResponse(
            [
                'data' => $this->resource($post),
            ],
            201
        );
    }

    private function positiveIntegerQuery(
        mixed $value,
        string $name
    ): int {
        if (
            !is_string($value) &&
            !is_int($value)
        ) {
            throw new InvalidArgumentException(
                ucfirst($name) . ' must be an integer.'
            );
        }

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validated === false) {
            throw new InvalidArgumentException(
                ucfirst($name) . ' must be a positive integer.'
            );
        }

        return $validated;
    }

    private function resource($post): array
    {
        $resource = [
            'id' => $post->id,
            'user_id' => $post->userId,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'created_at' => $post->createdAt,
            'updated_at' => $post->updatedAt,
        ];

        if ($post->author !== null) {
            $resource['author'] = $post->author;
        }

        return $resource;
    }
}
