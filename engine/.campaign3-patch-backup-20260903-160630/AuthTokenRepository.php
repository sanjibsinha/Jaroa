<?php

declare(strict_types=1);

namespace Jaroa\Repositories;

use PDO;

final class AuthTokenRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function create(
        int $userId,
        string $tokenHash,
        string $expiresAt
    ): int {
        $statement = $this->pdo->prepare(
            <<<'SQL'
INSERT INTO auth_tokens (
    user_id,
    token_hash,
    expires_at
)
VALUES (
    :user_id,
    :token_hash,
    :expires_at
)
SQL
        );

        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findValidByHash(
        string $tokenHash
    ): ?array {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    user_id,
    token_hash,
    expires_at,
    created_at,
    revoked_at
FROM auth_tokens
WHERE token_hash = :token_hash
  AND revoked_at IS NULL
  AND expires_at > CURRENT_TIMESTAMP
LIMIT 1
SQL
        );

        $statement->execute([
            'token_hash' => $tokenHash,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function revokeByHash(
        string $tokenHash
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE auth_tokens
SET revoked_at = CURRENT_TIMESTAMP
WHERE token_hash = :token_hash
  AND revoked_at IS NULL
SQL
        );

        $statement->execute([
            'token_hash' => $tokenHash,
        ]);

        return $statement->rowCount() > 0;
    }
}
