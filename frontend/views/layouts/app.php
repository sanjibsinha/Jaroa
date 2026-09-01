<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        rel="stylesheet"
        href="/assets/css/jaroa-layout.css"
    >

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

<?php
$requestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);
?>

<?php if (
    ($showSiteHeader ?? true) &&
    '/' !== $requestPath
): ?>

<header class="jaroa-layout-header">

    <a
        class="jaroa-home-link"
        href="/"
        aria-label="Return to Jaroa home"
    >
        <span class="jaroa-home-mark">J</span>
        <span>Jaroa</span>
    </a>

</header>

<?php endif; ?>

<main>

    <?= $content ?>

</main>

<?php if ($showSiteFooter ?? true): ?>

<footer class="site-footer">
    <p>
        <?= htmlspecialchars($appName) ?>
    </p>
</footer>

<?php endif; ?>

</body>
</html>
