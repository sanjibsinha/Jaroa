<?php

declare(strict_types=1);

namespace Jaroa\Repositories;

use Jaroa\Models\User;
use PDO;
use RuntimeException;

final class UserRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    name,
    email,
    password_hash,
    role,
    created_at,
    updated_at
FROM users
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

        return $this->map($row);
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    name,
    email,
    password_hash,
    role,
    created_at,
    updated_at
FROM users
WHERE email = :email
LIMIT 1
SQL
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->map($row);
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role = 'user'
    ): User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
INSERT INTO users (
    name,
    email,
    password_hash,
    role
)
VALUES (
    :name,
    :email,
    :password_hash,
    :role
)
SQL
        );

        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        $user = $this->findById($id);

        if ($user === null) {
            throw new RuntimeException(
                'Created user could not be retrieved.'
            );
        }

        return $user;
    }

    public function setRole(
        int $id,
        string $role
    ): ?User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET role = :role
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'role' => $role,
        ]);

        return $this->findById($id);
    }

    public function emailExistsForAnotherUser(
        string $email,
        int $exceptId
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT COUNT(*)
FROM users
WHERE email = :email
AND id <> :id
SQL
        );

        $statement->execute([
            'email' => $email,
            'id' => $exceptId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function updateProfile(
        int $id,
        string $name,
        string $email
    ): ?User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET
    name = :name,
    email = :email
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);

        return $this->findById($id);
    }

    public function updatePasswordHash(
        int $id,
        string $passwordHash
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET password_hash = :password_hash
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
DELETE FROM users
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    private function map(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
            (string) ($row['role'] ?? 'user'),
        );
    }
}
