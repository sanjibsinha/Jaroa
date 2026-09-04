<?php

declare(strict_types=1);

namespace Jaroa\Services;

use InvalidArgumentException;
use Jaroa\Models\Post;
use Jaroa\Repositories\PostRepository;

final class PostService
{
    public function __construct(
        private readonly PostRepository $repository
    ) {
    }

    /**
     * @return Post[]
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    public function create(
        int $userId,
        string $title,
        string $slug,
        string $content
    ): Post {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'User ID must be greater than zero.'
            );
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException(
                'Title is required.'
            );
        }

        if (trim($slug) === '') {
            throw new InvalidArgumentException(
                'Slug is required.'
            );
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                'Content is required.'
            );
        }

        return $this->repository->create(
            userId: $userId,
            title: $title,
            slug: $slug,
            content: $content
        );
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        return $this->repository->delete($id);
    }

    public function update(
        int $id,
        string $title,
        string $slug,
        string $content
    ): ?Post {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException(
                'Title is required.'
            );
        }

        if (trim($slug) === '') {
            throw new InvalidArgumentException(
                'Slug is required.'
            );
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                'Content is required.'
            );
        }

        return $this->repository->update(
            id: $id,
            title: $title,
            slug: $slug,
            content: $content
        );
    }
}
