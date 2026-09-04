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

    /**
     * @return array{posts: Post[], total: int}
     */
    public function paginate(
        int $page = 1,
        int $limit = 20,
        ?int $userId = null,
        ?string $search = null,
        string $sort = 'created_at',
        string $order = 'desc'
    ): array {
        if ($page < 1) {
            throw new InvalidArgumentException(
                'Page must be greater than zero.'
            );
        }

        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException(
                'Limit must be between 1 and 100.'
            );
        }

        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException(
                'User ID filter must be greater than zero.'
            );
        }

        $allowedSorts = [
            'id',
            'title',
            'created_at',
            'updated_at',
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            throw new InvalidArgumentException(
                'Invalid sort field.'
            );
        }

        $allowedOrders = ['asc', 'desc'];

        if (!in_array(strtolower($order), $allowedOrders, true)) {
            throw new InvalidArgumentException(
                'Invalid sort order.'
            );
        }

        if ($search !== null && strlen(trim($search)) > 100) {
            throw new InvalidArgumentException(
                'Search query must not exceed 100 characters.'
            );
        }

        return $this->repository->paginate(
            page: $page,
            limit: $limit,
            userId: $userId,
            search: $search,
            sort: $sort,
            order: strtolower($order)
        );
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
