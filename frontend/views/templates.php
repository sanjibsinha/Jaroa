<?php

$templates = $templates ?? [];
$installedTemplates = $installedTemplates ?? [];
$activeTemplate = $activeTemplate ?? null;

$installedSlugs = array_map(
    static fn (array $template): string => $template['slug'],
    $installedTemplates
);

$pageTitle = $appName . ' · Templates';
$pageDescription = 'Choose a starter template for your Jaroa application.';

$showSiteHeader = true;
$showSiteFooter = true;
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

    <style>
        .site-header {
            display: flex;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem 1.5rem 0;
        }

        .site-header a {
            color: #171717;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .site-header a:hover {
            text-decoration: underline;
        }

        .site-footer {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        body {
            margin: 0;
            font-family:
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: #f5f5f5;
            color: #171717;
        }

        .templates-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 1.5rem 5rem;
        }

        .templates-header {
            margin-bottom: 3rem;
        }

        .templates-header h1 {
            margin: 0;
            font-size: clamp(2.5rem, 7vw, 5rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .templates-header p {
            max-width: 650px;
            margin-top: 1.5rem;
            color: #666;
            line-height: 1.7;
        }

        .template-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }

        .template-card {
            padding: 2rem;
            border: 1px solid #ddd;
            border-radius: 24px;
            background: #fff;
        }

        .template-card h2 {
            margin: 0;
            font-size: 1.75rem;
        }

        .template-card p {
            color: #666;
            line-height: 1.7;
        }

        .template-status {
            display: inline-block;
            margin: 1rem 0;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: #eee;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .template-action {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.7rem 1rem;
            border: 1px solid #171717;
            border-radius: 999px;
            color: #171717;
            text-decoration: none;
            font-weight: 700;
        }

        .template-action:hover {
            background: #171717;
            color: #fff;
        }

        .template-action {
            font: inherit;
            cursor: pointer;
        }

        form {
            margin: 0;
        }

        .template-active {
            background: #171717;
            color: #fff;
        }

        @media (max-width: 700px) {
            .template-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<main class="templates-page">

    <header class="templates-header">

        <h1>
            Jaroa Templates
        </h1>

        <p>
            Choose a starter template for your Jaroa application.
            Templates can be installed and activated independently
            from the application core.
        </p>

    </header>

    <section class="template-grid">

        <?php foreach ($templates as $template): ?>

            <?php
            $slug = $template['slug'];
            $name = $template['name'];
            $description = $template['description'] ?? '';

            $isInstalled =
                in_array(
                    $slug,
                    $installedSlugs,
                    true
                );

            $isActive =
                $slug === $activeTemplate;
            ?>

            <article class="template-card">

                <h2>
                    <?= htmlspecialchars($name) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($description) ?>
                </p>

                <?php if ($isActive): ?>

                    <span class="template-status template-active">
                        Active
                    </span>

                    <br>

                    <?php if (!empty($template['showcase'])): ?>

                        <a
                            class="template-action"
                            href="<?= htmlspecialchars(
                                $template['showcase']
                            ) ?>"
                        >
                            Show Me
                        </a>

                    <?php endif; ?>

                <?php elseif ($isInstalled): ?>

                    <span class="template-status">
                        Installed
                    </span>

                    <br>

                    <form
                        method="post"
                        action="/templates/activate/<?= rawurlencode($slug) ?>"
                    >
                        <button
                            class="template-action"
                            type="submit"
                        >
                            Activate
                        </button>
                    </form>

                <?php else: ?>

                    <span class="template-status">
                        Available
                    </span>

                    <br>

                    <a
                        class="template-action"
                        href="#"
                    >
                        Install
                    </a>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    </section>

</main>

</body>
</html>
