<?php

$pageStylesheets = [
    '/assets/css/jaroa-home.css',
];

$showSiteHeader = false;
$showSiteFooter = false;

$activeTemplate = $activeTemplate ?? null;
$posts = $posts['data'] ?? [];
$apps = $apps['data'] ?? [];

$pageTitle = 'Jaroa';
$pageDescription =
    'A headless PHP application powered by WordPress.';
?>

<section class="jaroa-home">

    <div class="jaroa-hero">

        <div class="jaroa-hero-copy">

            <p class="jaroa-kicker">
                HEADLESS PHP APPLICATION
            </p>

            <h1>
                Build something
                <span>of your own.</span>
            </h1>

            <p class="jaroa-intro">
                Jaroa separates your content, application logic
                and visual presentation, giving you a clean
                foundation for building modern websites.
            </p>

            <div class="jaroa-actions">

                <a
                    class="jaroa-button jaroa-button-primary"
                    href="/templates"
                >
                    Explore Templates
                </a>

                <?php if (null !== $activeTemplate): ?>

                    <?php if (!empty($activeTemplateUrl)): ?>

                        <a
                            class="jaroa-button"
                            href="<?= htmlspecialchars(
                                $activeTemplateUrl
                            ) ?>"
                        >
                            Show My Site
                        </a>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

        <div class="jaroa-hero-mark">
            <span>J</span>
        </div>

    </div>


    <section class="jaroa-workspace">

        <div class="jaroa-section-heading">

            <p class="jaroa-kicker">
                YOUR WORKSPACE
            </p>

            <h2>
                Your site,
                your direction.
            </h2>

        </div>

        <div class="jaroa-status-card">

            <div>

                <p class="jaroa-status-label">
                    ACTIVE TEMPLATE
                </p>

                <?php if (null !== $activeTemplate): ?>

                    <h3>
                        <?= htmlspecialchars(
                            ucfirst($activeTemplate)
                        ) ?>
                    </h3>

                    <p>
                        Your Jaroa application is currently
                        using this template.
                    </p>

                <?php else: ?>

                    <h3>
                        No template selected
                    </h3>

                    <p>
                        Choose a starter template to begin.
                    </p>

                <?php endif; ?>

            </div>

            <div class="jaroa-status-action">

                <?php if (null !== $activeTemplate): ?>

                    <a
                        class="jaroa-button"
                        href="/templates"
                    >
                        Change Template
                    </a>

                <?php else: ?>

                    <a
                        class="jaroa-button jaroa-button-primary"
                        href="/templates"
                    >
                        Choose Template
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <section class="jaroa-architecture">

        <div class="jaroa-section-heading">

            <p class="jaroa-kicker">
                UNDER THE HOOD
            </p>

            <h2>
                Three pieces.
                One application.
            </h2>

        </div>

        <div class="jaroa-pillars">

            <article>

                <span class="jaroa-number">
                    01
                </span>

                <h3>
                    WordPress
                </h3>

                <p>
                    Your content lives in a familiar
                    headless WordPress backend.
                </p>

            </article>

            <article>

                <span class="jaroa-number">
                    02
                </span>

                <h3>
                    Jaroa
                </h3>

                <p>
                    A lightweight PHP application handles
                    routing, services, controllers and views.
                </p>

            </article>

            <article>

                <span class="jaroa-number">
                    03
                </span>

                <h3>
                    Templates
                </h3>

                <p>
                    Starter templates provide the visual
                    identity without changing the application core.
                </p>

            </article>

        </div>

    </section>


    <?php if ([] !== $posts): ?>

        <section class="jaroa-articles">

            <div class="jaroa-section-heading">

                <p class="jaroa-kicker">
                    FROM YOUR CONTENT
                </p>

                <h2>
                    Latest articles.
                </h2>

            </div>

            <div class="jaroa-article-grid">

                <?php foreach ($posts as $post): ?>

                    <article>

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

                        <p>
                            <?= htmlspecialchars(
                                $post['excerpt']
                            ) ?>
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

</section>
