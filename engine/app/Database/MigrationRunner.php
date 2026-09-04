<?php

declare(strict_types=1);

namespace Jaroa\Database;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationPath
    ) {
    }

    public function migrate(): array
    {
        $repository = new MigrationRepository($this->pdo);

        $repository->createRepository();

        $files = glob($this->migrationPath . '/*.php');

        if ($files === false) {
            throw new RuntimeException(
                "Unable to read migration directory: {$this->migrationPath}"
            );
        }

        sort($files);

        $executed = [];

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if ($repository->hasRun($migrationName)) {
                continue;
            }

            $migration = require $file;

            if (!$migration instanceof Migration) {
                throw new RuntimeException(
                    "Migration {$migrationName} must return a Migration instance."
                );
            }

            $migration->up($this->pdo);

            $repository->record($migrationName);

            $executed[] = $migrationName;
        }

        return $executed;
    }
}
