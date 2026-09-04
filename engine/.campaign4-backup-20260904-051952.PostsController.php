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

    public function index(array $parameters): JsonResponse
    {
        $posts = $this->service->all();

        $data = array_map(
            static function ($post): array {
                return [
                    'id' => $post->id,
                    'user_id' => $post->userId,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'created_at' => $post->createdAt,
                    'updated_at' => $post->updatedAt,
                ];
            },
            $posts
        );

        return new JsonResponse([
            'data' => $data,
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
            'data' => [
                'id' => $post->id,
                'user_id' => $post->userId,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'created_at' => $post->createdAt,
                'updated_at' => $post->updatedAt,
            ],
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

        return new JsonResponse(
            [
                'data' => [
                    'deleted' => true,
                    'id' => $id,
                ],
            ]
        );
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
            'data' => [
                'id' => $post->id,
                'user_id' => $post->userId,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'created_at' => $post->createdAt,
                'updated_at' => $post->updatedAt,
            ],
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
                'data' => [
                    'id' => $post->id,
                    'user_id' => $post->userId,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'created_at' => $post->createdAt,
                    'updated_at' => $post->updatedAt,
                ],
            ],
            201
        );
    }
}
