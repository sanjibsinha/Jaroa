<?php

declare(strict_types=1);

use Jaroa\Database\Migration;

return new class implements Migration {
    public function up(\PDO $pdo): void
    {
        $statement = $pdo->query(
            <<<'SQL'
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role'
SQL
        );

        $exists = (int) $statement->fetchColumn() > 0;

        if (!$exists) {
            $pdo->exec(
                <<<'SQL'
ALTER TABLE users
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'
AFTER email
SQL
            );
        }

        $pdo->exec(
            <<<'SQL'
UPDATE users
SET role = 'admin'
WHERE id = 1
  AND name = 'Jaroa Admin'
SQL
        );
    }

    public function down(\PDO $pdo): void
    {
        $statement = $pdo->query(
            <<<'SQL'
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role'
SQL
        );

        $exists = (int) $statement->fetchColumn() > 0;

        if ($exists) {
            $pdo->exec(
                'ALTER TABLE users DROP COLUMN role'
            );
        }
    }
};
