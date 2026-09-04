<?php

$templates = $templates ?? [];
$installedTemplates = $installedTemplates ?? [];
$activeTemplate = $activeTemplate ?? null;

$installedSlugs = array_map(
    static fn (array $template): string => $template['slug'],
    $installedTemplates
);

$pageTitle = $appName . ' · Themes';
$pageDescription =
    'Choose, install and activate the visual identity of your Jaroa application.';

$showSiteHeader = false;
$showSiteFooter = false;

$pageStylesheets = [
    '/assets/css/jaroa-templates.css',
];
?>

<div class="jaroa-themes-page">

    <header class="themes-header">

        <div class="themes-topline">

            <a class="themes-wordmark" href="/">
                <span class="themes-wordmark-mark">J</span>
                <span>Jaroa</span>
            </a>

            <div class="themes-topline-meta">
                <span>Theme Manager</span>
                <span>01</span>
            </div>

        </div>


        <div class="themes-intro">

            <div class="themes-intro-label">
                <span>01</span>
                Visual identity
            </div>

            <h1>
                Choose how
                <em>Jaroa looks.</em>
            </h1>

            <p>
                Install a starter theme, make it your own,
                and activate it when you are ready.
                Your application core stays exactly where it is.
            </p>

        </div>

    </header>


    <main>

        <section class="themes-current">

            <div class="themes-section-label">
                <span>02</span>
                Current workspace
            </div>

            <div class="themes-current-panel">

                <div class="themes-current-copy">

                    <p class="themes-overline">
                        ACTIVE THEME
                    </p>

                    <?php if (null !== $activeTemplate): ?>

                        <h2>
                            <?= htmlspecialchars(
                                ucfirst($activeTemplate)
                            ) ?>
                        </h2>

                        <p>
                            This theme is currently powering the
                            presentation layer of your Jaroa application.
                        </p>

                    <?php else: ?>

                        <h2>
                            No active theme.
                        </h2>

                        <p>
                            Install a starter theme and activate it
                            to give your application a visual identity.
                        </p>

                    <?php endif; ?>

                </div>


                <div class="themes-current-action">

                    <?php if (null !== $activeTemplate): ?>

                        <?php
                        $activeManifest = null;

                        foreach ($templates as $template) {
                            if (
                                isset($template['slug']) &&
                                $template['slug'] === $activeTemplate
                            ) {
                                $activeManifest = $template;
                                break;
                            }
                        }
                        ?>

                        <?php if (
                            is_array($activeManifest) &&
                            !empty($activeManifest['showcase'])
                        ): ?>

                            <a
                                class="themes-primary-button"
                                href="<?= htmlspecialchars(
                                    $activeManifest['showcase']
                                ) ?>"
                            >
                                Show My Site <span>↗</span>
                            </a>

                        <?php endif; ?>

                    <?php endif; ?>

                    <a
                        class="themes-secondary-button"
                        href="/"
                    >
                        Back to Jaroa <span>↗</span>
                    </a>

                </div>

            </div>

        </section>


        <section class="themes-collection">

            <div class="themes-collection-heading">

                <div>

                    <div class="themes-section-label">
                        <span>03</span>
                        Starter collection
                    </div>

                    <h2>
                        Four starting points.
                    </h2>

                </div>

                <p>
                    Installation creates your editable theme.
                    Activation decides which one your visitors see.
                </p>

            </div>


            <div class="themes-grid">

                <?php foreach ($templates as $index => $template): ?>

                    <?php
                    $slug = $template['slug'] ?? '';
                    $name = $template['name'] ?? ucfirst($slug);
                    $description = $template['description'] ?? '';
                    $showcase = $template['showcase'] ?? null;

                    $isInstalled =
                        in_array(
                            $slug,
                            $installedSlugs,
                            true
                        );

                    $isActive =
                        $slug === $activeTemplate;

                    $number = str_pad(
                        (string) ($index + 1),
                        2,
                        '0',
                        STR_PAD_LEFT
                    );
                    ?>

                    <article
                        class="theme-card theme-card-<?= htmlspecialchars(
                            $slug
                        ) ?>"
                    >

                        <div class="theme-card-art" aria-hidden="true">

                            <span class="theme-card-number">
                                <?= htmlspecialchars($number) ?>
                            </span>

                            <span class="theme-card-letter">
                                <?= htmlspecialchars(
                                    strtoupper(
                                        substr($name, 0, 1)
                                    )
                                ) ?>
                            </span>

                            <span class="theme-card-orbit"></span>

                        </div>


                        <div class="theme-card-body">

                            <div class="theme-card-status-row">

                                <?php if ($isActive): ?>

                                    <span class="theme-status theme-status-active">
                                        Active
                                    </span>

                                <?php elseif ($isInstalled): ?>

                                    <span class="theme-status">
                                        Installed
                                    </span>

                                <?php else: ?>

                                    <span class="theme-status">
                                        Available
                                    </span>

                                <?php endif; ?>

                            </div>


                            <h3>
                                <?= htmlspecialchars($name) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($description) ?>
                            </p>


                            <div class="theme-card-actions">

                                <?php if ($isActive): ?>

                                    <?php if (!empty($showcase)): ?>

                                        <a
                                            class="theme-action theme-action-primary"
                                            href="<?= htmlspecialchars(
                                                $showcase
                                            ) ?>"
                                        >
                                            Show Me <span>↗</span>
                                        </a>

                                    <?php endif; ?>


                                <?php elseif ($isInstalled): ?>

                                    <?php if (!empty($showcase)): ?>

                                        <a
                                            class="theme-action theme-action-secondary"
                                            href="<?= htmlspecialchars(
                                                $showcase
                                            ) ?>"
                                        >
                                            Preview <span>↗</span>
                                        </a>

                                    <?php endif; ?>


                                    <form
                                        method="post"
                                        action="/templates/activate/<?= rawurlencode(
                                            $slug
                                        ) ?>"
                                    >
                                        <button
                                            class="theme-action theme-action-primary"
                                            type="submit"
                                        >
                                            Activate
                                        </button>
                                    </form>


                                <?php else: ?>

                                    <form
                                        method="post"
                                        action="/templates/install/<?= rawurlencode(
                                            $slug
                                        ) ?>"
                                    >
                                        <button
                                            class="theme-action theme-action-primary"
                                            type="submit"
                                        >
                                            Install
                                        </button>
                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


        <section class="themes-flow">

            <div class="themes-section-label">
                <span>04</span>
                Theme lifecycle
            </div>

            <div class="themes-flow-grid">

                <div class="themes-flow-step">
                    <span>01</span>
                    <strong>Available</strong>
                    <p>
                        A pristine starter lives in the
                        Jaroa template library.
                    </p>
                </div>

                <div class="themes-flow-line"></div>

                <div class="themes-flow-step">
                    <span>02</span>
                    <strong>Installed</strong>
                    <p>
                        A private editable copy is created
                        inside your site workspace.
                    </p>
                </div>

                <div class="themes-flow-line"></div>

                <div class="themes-flow-step">
                    <span>03</span>
                    <strong>Active</strong>
                    <p>
                        Your chosen installed theme becomes
                        the site's presentation layer.
                    </p>
                </div>

            </div>

        </section>


        <section class="themes-closing">

            <div class="themes-closing-mark">
                J
            </div>

            <p class="themes-overline">
                JAROA / THEMES
            </p>

            <h2>
                The core stays.
                <em>The character changes.</em>
            </h2>

            <a href="/">
                Return to Jaroa <span>↗</span>
            </a>

        </section>

    </main>


    <footer class="themes-footer">

        <div class="themes-footer-brand">
            <strong>Jaroa</strong>
            <span>Theme Manager</span>
        </div>

        <a href="/">
            Home <span>↗</span>
        </a>

        <div class="themes-footer-bottom">
            <span>© 2026 Jaroa</span>
            <span>Headless PHP · WordPress · Themes</span>
        </div>

    </footer>

</div>
