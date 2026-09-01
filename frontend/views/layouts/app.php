<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <?php if (!empty($pageDescription)): ?>

        <meta
            name="description"
            content="<?= htmlspecialchars($pageDescription) ?>"
        >

    <?php endif; ?>
<?php if (!empty($pageStylesheets)): ?>

    <?php foreach ($pageStylesheets as $stylesheet): ?>

        <link
            rel="stylesheet"
            href="<?= htmlspecialchars($stylesheet) ?>"
        >

    <?php endforeach; ?>

<?php endif; ?>

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

    <?= $content ?>

</main>

<footer>
    <p>
        <?= htmlspecialchars($appName) ?>
    </p>
</footer>

</body>
</html>
