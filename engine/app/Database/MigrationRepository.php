<?php

declare(strict_types=1);

namespace Jaroa\Database;

use PDO;

final class MigrationRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function createRepository(): void
    {
        $this->pdo->exec(
            <<<'SQL'
CREATE TABLE IF NOT EXISTS jaroa_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );
    }

    public function hasRun(string $migration): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM jaroa_migrations WHERE migration = :migration'
        );

        $statement->execute([
            'migration' => $migration,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function record(string $migration): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO jaroa_migrations (migration) VALUES (:migration)'
        );

        $statement->execute([
            'migration' => $migration,
        ]);
    }

    public function remove(string $migration): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM jaroa_migrations WHERE migration = :migration'
        );

        $statement->execute([
            'migration' => $migration,
        ]);
    }
}
