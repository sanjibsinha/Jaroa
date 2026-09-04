<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$pdo = $app->database()->connection();

$repository = new Jaroa\Database\MigrationRepository($pdo);

$repository->createRepository();

echo "Migration repository created successfully." . PHP_EOL;

$table = $pdo->query(
    "SHOW TABLES LIKE 'jaroa_migrations'"
)->fetchColumn();

echo $table !== false
    ? "jaroa_migrations exists." . PHP_EOL
    : "jaroa_migrations does NOT exist." . PHP_EOL;
