<?php

declare(strict_types=1);

namespace Jaroa\Repositories;

use Jaroa\Models\Post;
use PDO;
use RuntimeException;

final class PostRepository
{
    private const SORT_COLUMNS = [
        'id' => 'p.id',
        'title' => 'p.title',
        'created_at' => 'p.created_at',
        'updated_at' => 'p.updated_at',
    ];

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

    /**
     * @return array{posts: Post[], total: int}
     */
    public function paginate(
        int $page,
        int $limit,
        ?int $userId = null,
        ?string $search = null,
        string $sort = 'created_at',
        string $order = 'desc'
    ): array {
        $offset = ($page - 1) * $limit;

        $where = [];
        $bindings = [];

        if ($userId !== null) {
            $where[] = 'p.user_id = :filter_user_id';
            $bindings['filter_user_id'] = $userId;
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = '(p.title LIKE :search OR p.slug LIKE :search OR p.content LIKE :search)';
            $bindings['search'] = '%' . trim($search) . '%';
        }

        $whereSql = $where === []
            ? ''
            : 'WHERE ' . implode(' AND ', $where);

        $sortColumn = self::SORT_COLUMNS[$sort] ?? self::SORT_COLUMNS['created_at'];
        $direction = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        $countStatement = $this->pdo->prepare(
            <<<SQL
SELECT COUNT(*)
FROM posts p
$whereSql
SQL
        );

        $countStatement->execute($bindings);

        $total = (int) $countStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            <<<SQL
SELECT
    p.id,
    p.user_id,
    p.title,
    p.slug,
    p.content,
    p.created_at,
    p.updated_at,
    u.id AS author_id,
    u.name AS author_name,
    u.email AS author_email,
    u.role AS author_role
FROM posts p
LEFT JOIN users u ON u.id = p.user_id
$whereSql
ORDER BY {$sortColumn} {$direction}, p.id DESC
LIMIT :limit OFFSET :offset
SQL
        );

        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);

        $statement->execute();

        $rows = $statement->fetchAll();

        return [
            'posts' => array_map(
                fn (array $row): Post => $this->mapToPost($row, true),
                $rows
            ),
            'total' => $total,
        ];
    }

    public function find(int $id): ?Post
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    p.id,
    p.user_id,
    p.title,
    p.slug,
    p.content,
    p.created_at,
    p.updated_at,
    u.id AS author_id,
    u.name AS author_name,
    u.email AS author_email,
    u.role AS author_role
FROM posts p
LEFT JOIN users u ON u.id = p.user_id
WHERE p.id = :id
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

        return $this->mapToPost($row, true);
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

    private function mapToPost(
        array $row,
        bool $includeAuthor = false
    ): Post {
        $author = null;

        if (
            $includeAuthor &&
            isset($row['author_id']) &&
            $row['author_id'] !== null
        ) {
            $author = [
                'id' => (int) $row['author_id'],
                'name' => $row['author_name'],
                'email' => $row['author_email'],
                'role' => $row['author_role'],
            ];
        }

        return new Post(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            title: $row['title'],
            slug: $row['slug'],
            content: $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            author: $author,
        );
    }
}
