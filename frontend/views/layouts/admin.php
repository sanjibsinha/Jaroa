<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars(
        $pageTitle ?? 'Jaroa Admin'
    ) ?></title>

    <link
        rel="stylesheet"
        href="/assets/css/jaroa-admin.css"
    >
</head>

<body class="jaroa-admin-body">

<div class="admin-shell">

    <?= $content ?>

</div>

</body>
</html>
