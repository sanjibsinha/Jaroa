<?php

declare(strict_types=1);

namespace Jaroa\Repositories;

use Jaroa\Models\Post;
use PDO;
use RuntimeException;

final class PostRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return Post[]
     */
    public function all(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
SELECT
    id,
    user_id,
    title,
    slug,
    content,
    created_at,
    updated_at
FROM posts
ORDER BY id DESC
SQL
        );

        $rows = $statement->fetchAll();

        return array_map(
            fn (array $row): Post => $this->mapToPost($row),
            $rows
        );
    }

    public function find(int $id): ?Post
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    user_id,
    title,
    slug,
    content,
    created_at,
    updated_at
FROM posts
WHERE id = :id
LIMIT 1
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->mapToPost($row);
    }

    public function create(
        int $userId,
        string $title,
        string $slug,
        string $content
    ): Post {
        $statement = $this->pdo->prepare(
            <<<'SQL'
INSERT INTO posts (
    user_id,
    title,
    slug,
    content
) VALUES (
    :user_id,
    :title,
    :slug,
    :content
)
SQL
        );

        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        $post = $this->find($id);

        if ($post === null) {
            throw new RuntimeException(
                'Created post could not be retrieved.'
            );
        }

        return $post;
    }

    public function update(
        int $id,
        string $title,
        string $slug,
        string $content
    ): ?Post {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE posts
SET
    title = :title,
    slug = :slug,
    content = :content
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
DELETE FROM posts
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    private function mapToPost(array $row): Post
    {
        return new Post(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            title: $row['title'],
            slug: $row['slug'],
            content: $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at']
        );
    }
}
