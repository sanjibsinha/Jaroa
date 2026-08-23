<?php

$post = $article['data'] ?? null;

if (!$post) {
    http_response_code(404);
    echo 'Article not found.';
    return;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($post['title']) ?></title>
</head>

<body>

<header>
    <p>
        <a href="/">Headless PHP Application</a>
    </p>
</header>

<main>

    <article>

        <h1>
            <?= htmlspecialchars($post['title']) ?>
        </h1>

        <p>
            <small>
                <?= htmlspecialchars($post['date']) ?>
            </small>
        </p>

        <?php if (!empty($post['featured_image'])): ?>

            <figure>
                <img
                    src="<?= htmlspecialchars($post['featured_image']['url']) ?>"
                    alt="<?= htmlspecialchars($post['featured_image']['alt'] ?? '') ?>"
                >
            </figure>

        <?php endif; ?>

        <div>
            <?= $post['content'] ?>
        </div>

    </article>

</main>

</body>
</html>