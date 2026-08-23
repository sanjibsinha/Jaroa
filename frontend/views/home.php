<?php

$posts = $posts['data'] ?? [];
$apps  = $apps['data'] ?? [];

$pageTitle = 'Headless PHP Application';
$pageDescription = 'A PHP frontend powered by a headless WordPress backend.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= htmlspecialchars($pageDescription) ?>"
    >
</head>

<body>

<header>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>

    <p>
        <?= htmlspecialchars($pageDescription) ?>
    </p>
</header>

<main>

    <section>
        <h2>Applications</h2>

        <?php if ([] === $apps): ?>

            <p>No applications available yet.</p>

        <?php else: ?>

            <?php foreach ($apps as $app): ?>

                <article>
                    <h3>
                        <?= htmlspecialchars($app['title']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($app['description']) ?>
                    </p>

                    <p>
                        <strong>Version:</strong>
                        <?= htmlspecialchars($app['version']) ?>
                    </p>

                    <p>
                        <strong>Operating System:</strong>
                        <?= htmlspecialchars($app['os']) ?>
                    </p>

                    <?php if (!empty($app['file'])): ?>

                        <p>
                            <a
                                href="<?= htmlspecialchars($app['file']) ?>"
                                download
                            >
                                Download
                            </a>
                        </p>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>


    <section>
        <h2>Latest Articles</h2>

        <?php if ([] === $posts): ?>

            <p>No articles available yet.</p>

        <?php else: ?>

            <?php foreach ($posts as $post): ?>

                <article>
                    <h3>
                        <?= htmlspecialchars($post['title']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($post['excerpt']) ?>
                    </p>

                    <small>
                        <?= htmlspecialchars($post['date']) ?>
                    </small>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>
        Headless PHP Application
    </p>
</footer>

</body>
</html>