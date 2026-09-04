<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\Application();

$pdo = $app->database()->connection();

$version = $pdo->query('SELECT VERSION()')->fetchColumn();

echo "PDO class: " . get_class($pdo) . PHP_EOL;
echo "MariaDB version: {$version}" . PHP_EOL;
echo "Database connection successful." . PHP_EOL;
