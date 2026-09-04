<?php

$pageStylesheets = [
    '/assets/css/jaroa-home.css',
];

$showSiteHeader = false;
$showSiteFooter = false;

$activeTemplate = $activeTemplate ?? null;
$activeTemplateUrl = $activeTemplateUrl ?? null;
$posts = $posts['data'] ?? [];
$apps = $apps['data'] ?? [];

$pageTitle = 'Jaroa';
$pageDescription =
    'A headless PHP application platform with WordPress at its core.';
?>

<div class="jaroa-home">

    <header class="jaroa-home-header">

        <a class="jaroa-wordmark" href="/">
            <span class="jaroa-wordmark-mark">J</span>
            <span>Jaroa</span>
        </a>

        <div class="jaroa-home-header-right">
            <span>Headless PHP</span>
            <span class="jaroa-home-version">01</span>
        </div>

    </header>


    <main>

        <section class="jaroa-hero">

            <div class="jaroa-hero-copy">

                <p class="jaroa-kicker">
                    HEADLESS APPLICATION PLATFORM
                </p>

                <h1>
                    Build the
                    <em>experience.</em>
                </h1>

                <p class="jaroa-hero-intro">
                    Jaroa separates content, application logic and
                    visual identity into distinct layers, giving you
                    a clean foundation for building something entirely
                    your own.
                </p>

                <div class="jaroa-actions">

                    <a
                        class="jaroa-button jaroa-button-primary"
                        href="/templates"
                    >
                        Explore Starter Themes
                        <span>↗</span>
                    </a>

                    <?php if (null !== $activeTemplateUrl): ?>

                        <a
                            class="jaroa-button"
                            href="<?= htmlspecialchars(
                                $activeTemplateUrl
                            ) ?>"
                        >
                            View Active Theme
                            <span>↗</span>
                        </a>

                    <?php endif; ?>

                </div>

            </div>


            <div class="jaroa-hero-art" aria-hidden="true">

                <div class="jaroa-orbit jaroa-orbit-one"></div>
                <div class="jaroa-orbit jaroa-orbit-two"></div>
                <div class="jaroa-orbit jaroa-orbit-three"></div>

                <div class="jaroa-hero-core">
                    J
                </div>

                <div class="jaroa-orbit-dot jaroa-orbit-dot-one"></div>
                <div class="jaroa-orbit-dot jaroa-orbit-dot-two"></div>
                <div class="jaroa-orbit-dot jaroa-orbit-dot-three"></div>

                <div class="jaroa-hero-coordinate">
                    01 / 01
                </div>

            </div>

        </section>


        <section class="jaroa-current">

            <div class="jaroa-section-label">
                <span>01</span>
                Current workspace
            </div>

            <div class="jaroa-current-grid">

                <div class="jaroa-current-copy">

                    <p class="jaroa-overline">
                        ACTIVE THEME
                    </p>

                    <?php if (null !== $activeTemplate): ?>

                        <h2>
                            <?= htmlspecialchars(
                                ucfirst($activeTemplate)
                            ) ?>
                        </h2>

                        <p>
                            This is the visual layer currently powering
                            your Jaroa application.
                        </p>

                    <?php else: ?>

                        <h2>
                            Nothing active yet.
                        </h2>

                        <p>
                            Choose a starter theme and give your
                            application a visual identity.
                        </p>

                    <?php endif; ?>

                </div>


                <div class="jaroa-current-actions">

                    <?php if (null !== $activeTemplateUrl): ?>

                        <a
                            class="jaroa-outline-button"
                            href="<?= htmlspecialchars(
                                $activeTemplateUrl
                            ) ?>"
                        >
                            Show My Site <span>↗</span>
                        </a>

                    <?php endif; ?>

                    <a
                        class="jaroa-outline-button"
                        href="/templates"
                    >
                        Change Theme <span>↗</span>
                    </a>

                </div>

            </div>

        </section>


        <section class="jaroa-themes">

            <div class="jaroa-section-heading">

                <div class="jaroa-section-label">
                    <span>02</span>
                    Starter collection
                </div>

                <h2>
                    Four ways to
                    begin.
                </h2>

                <p>
                    The application stays the same.
                    The atmosphere changes.
                </p>

            </div>


            <div class="jaroa-theme-grid">

                <a class="jaroa-theme-card jaroa-theme-profile" href="/profile">

                    <span class="jaroa-theme-number">01</span>

                    <div class="jaroa-theme-symbol">P</div>

                    <div class="jaroa-theme-content">
                        <p>PERSONAL / EDITORIAL</p>
                        <h3>Profile</h3>
                        <span>Explore <b>↗</b></span>
                    </div>

                </a>


                <a class="jaroa-theme-card jaroa-theme-blog" href="/blog">

                    <span class="jaroa-theme-number">02</span>

                    <div class="jaroa-theme-symbol">B</div>

                    <div class="jaroa-theme-content">
                        <p>LITERARY / EDITORIAL</p>
                        <h3>Blog</h3>
                        <span>Explore <b>↗</b></span>
                    </div>

                </a>


                <a class="jaroa-theme-card jaroa-theme-news" href="/news">

                    <span class="jaroa-theme-number">03</span>

                    <div class="jaroa-theme-symbol">N</div>

                    <div class="jaroa-theme-content">
                        <p>NEWSROOM / MAGAZINE</p>
                        <h3>News</h3>
                        <span>Explore <b>↗</b></span>
                    </div>

                </a>


                <a class="jaroa-theme-card jaroa-theme-fashion" href="/fashion">

                    <span class="jaroa-theme-number">04</span>

                    <div class="jaroa-theme-symbol">F</div>

                    <div class="jaroa-theme-content">
                        <p>FASHION / MAGAZINE</p>
                        <h3>Fashion</h3>
                        <span>Explore <b>↗</b></span>
                    </div>

                </a>

            </div>


            <div class="jaroa-themes-footer">

                <p>
                    Want to manage the collection?
                </p>

                <a href="/templates">
                    Open Theme Manager <span>↗</span>
                </a>

            </div>

        </section>


        <section class="jaroa-architecture">

            <div class="jaroa-section-heading">

                <div class="jaroa-section-label">
                    <span>03</span>
                    Under the hood
                </div>

                <h2>
                    Three layers.
                    One application.
                </h2>

                <p>
                    Each layer has one job.
                    That keeps the system easier to understand,
                    extend and maintain.
                </p>

            </div>


            <div class="jaroa-layer-stack">

                <article class="jaroa-layer">

                    <div class="jaroa-layer-index">
                        01
                    </div>

                    <div>
                        <p class="jaroa-overline">
                            CONTENT
                        </p>

                        <h3>
                            WordPress
                        </h3>

                        <p>
                            Posts, pages and editorial content live
                            in a familiar headless WordPress backend.
                        </p>
                    </div>

                </article>


                <article class="jaroa-layer jaroa-layer-focus">

                    <div class="jaroa-layer-index">
                        02
                    </div>

                    <div>
                        <p class="jaroa-overline">
                            APPLICATION
                        </p>

                        <h3>
                            Jaroa
                        </h3>

                        <p>
                            PHP handles routing, controllers, services,
                            views and the application itself.
                        </p>
                    </div>

                </article>


                <article class="jaroa-layer">

                    <div class="jaroa-layer-index">
                        03
                    </div>

                    <div>
                        <p class="jaroa-overline">
                            PRESENTATION
                        </p>

                        <h3>
                            Themes
                        </h3>

                        <p>
                            Installed themes provide the visual identity
                            without replacing the application core.
                        </p>
                    </div>

                </article>

            </div>

        </section>


        <?php if ([] !== $posts): ?>

            <section class="jaroa-content">

                <div class="jaroa-section-heading">

                    <div class="jaroa-section-label">
                        <span>04</span>
                        From your content
                    </div>

                    <h2>
                        Latest articles.
                    </h2>

                    <p>
                        Content comes from WordPress.
                        Jaroa decides how it reaches the reader.
                    </p>

                </div>


                <div class="jaroa-article-list">

                    <?php foreach ($posts as $index => $post): ?>

                        <article class="jaroa-article-row">

                            <div class="jaroa-article-index">
                                <?= str_pad(
                                    (string) ($index + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </div>

                            <div class="jaroa-article-main">

                                <p class="jaroa-article-date">
                                    <?= htmlspecialchars(
                                        $post['date']
                                    ) ?>
                                </p>

                                <h3>
                                    <a
                                        href="/articles/<?= rawurlencode(
                                            $post['slug']
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $post['title']
                                        ) ?>
                                    </a>
                                </h3>

                            </div>

                            <p class="jaroa-article-excerpt">
                                <?= htmlspecialchars(
                                    $post['excerpt']
                                ) ?>
                            </p>

                            <a
                                class="jaroa-article-arrow"
                                href="/articles/<?= rawurlencode(
                                    $post['slug']
                                ) ?>"
                                aria-label="Read article"
                            >
                                ↗
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>


        <section class="jaroa-closing">

            <div class="jaroa-closing-mark">
                J
            </div>

            <p class="jaroa-kicker">
                JAROA
            </p>

            <h2>
                Your content.
                Your application.
                <em>Your direction.</em>
            </h2>

            <a href="/templates">
                Explore the starter collection <span>↗</span>
            </a>

        </section>

    </main>


    <footer class="jaroa-footer">

        <div class="jaroa-footer-brand">
            <strong>Jaroa</strong>
            <span>Headless PHP application platform</span>
        </div>

        <div class="jaroa-footer-links">
            <a href="/templates">Themes</a>
            <a href="/articles">Articles</a>
            <a href="/">Home</a>
        </div>

        <div class="jaroa-footer-bottom">
            <span>© 2026 Jaroa</span>
            <span>Built with PHP · WordPress · Jaroa</span>
        </div>

    </footer>

</div>
