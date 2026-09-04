<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$runner = new Jaroa\Database\MigrationRunner(
    $app->database()->connection(),
    dirname(__DIR__) . '/database/migrations'
);

$executed = $runner->migrate();

if ($executed === []) {
    echo "No pending migrations." . PHP_EOL;
    exit(0);
}

foreach ($executed as $migration) {
    echo "Migrated: {$migration}" . PHP_EOL;
}
