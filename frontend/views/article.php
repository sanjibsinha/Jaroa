<?php

$post = $article['data'] ?? null;

if (!$post) {
    http_response_code(404);
    echo 'Article not found.';
    return;
}

$pageTitle = $post['title'];
$pageDescription = $post['excerpt'] ?? '';

$pageStylesheets = [
    '/assets/css/jaroa-article.css',
];

$showSiteFooter = false;
?>

<section class="article-page">

    <header class="article-header">

        <p class="article-kicker">
            Jaroa Article
        </p>

        <h1>
            <?= htmlspecialchars($post['title']) ?>
        </h1>

        <div class="article-meta">

            <?php if (!empty($post['date'])): ?>

                <span>
                    <?= htmlspecialchars($post['date']) ?>
                </span>

            <?php endif; ?>

            <?php if (!empty($post['author']['name'])): ?>

                <span>
                    <?= htmlspecialchars($post['author']['name']) ?>
                </span>

            <?php endif; ?>

        </div>

    </header>


    <?php if (!empty($post['featured_image'])): ?>

        <figure class="article-featured-image">

            <img
                src="<?= htmlspecialchars(
                    $post['featured_image']['url']
                ) ?>"
                alt="<?= htmlspecialchars(
                    $post['featured_image']['alt'] ?? ''
                ) ?>"
            >

        </figure>

    <?php endif; ?>


    <div class="article-body">

        <?= $post['content'] ?>

    </div>


    <footer class="article-footer">

        <a
            class="article-back"
            href="/"
        >
            ← Back to Jaroa
        </a>

    </footer>

</section>
