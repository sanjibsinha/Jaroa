<?php

$pageTitle = '404 - Page Not Found';
$pageDescription = 'The page you requested could not be found.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= htmlspecialchars($pageDescription) ?>"
    >
</head>

<body>

<header>
    <p>
        <a href="/">
            <?= htmlspecialchars($appName) ?>
        </a>
    </p>
</header>

<main>

    <h1>404</h1>

    <h2>Page Not Found</h2>

    <p>
        <?= htmlspecialchars($pageDescription) ?>
    </p>

    <p>
        <a href="/">Back to <?= htmlspecialchars($appName) ?></a>
    </p>

</main>

<footer>
    <p>
        <?= htmlspecialchars($appName) ?>
    </p>
</footer>

</body>
</html>
