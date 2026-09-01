<?php

$post = $article['data'] ?? null;

if (!$post) {
    throw new \App\Exceptions\NotFoundException(
        'Article not found.'
    );
}

$pageTitle = $post['title'];
?>

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