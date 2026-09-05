<?php

$pageTitle = 'Jaroa · Templates';

$templates = $templates ?? [];
$installedTemplates = $installedTemplates ?? [];
$activeTemplate = $activeTemplate ?? null;

$installedSlugs = array_map(
    static fn (array $template): string =>
        (string) ($template['slug'] ?? ''),
    $installedTemplates
);
?>

<div class="admin-app">

    <aside class="admin-sidebar">

        <div class="admin-sidebar-brand">
            <span class="admin-logo">J</span>

            <div>
                <strong>Jaroa</strong>
                <span>Admin Console</span>
            </div>
        </div>

        <nav class="admin-nav">

            <a
                class="admin-nav-link"
                href="/admin"
            >
                <span>01</span>
                Dashboard
            </a>

            <a
                class="admin-nav-link admin-nav-link-active"
                href="/admin/templates"
            >
                <span>02</span>
                Templates
            </a>

            <a
                class="admin-nav-link"
                href="/"
                target="_blank"
                rel="noopener"
            >
                <span>03</span>
                View Site
            </a>

        </nav>

        <div class="admin-sidebar-footer">

            <a
                class="admin-quiet-link"
                href="/admin"
            >
                ← Dashboard
            </a>

            <form
                method="post"
                action="/admin/logout"
            >
                <button
                    class="admin-logout"
                    type="submit"
                >
                    Sign out
                </button>
            </form>

        </div>

    </aside>

    <main class="admin-main">

        <header class="admin-topbar">

            <div>
                <p class="admin-kicker">
                    JAROA / PRESENTATION
                </p>

                <h1>
                    Templates
                </h1>
            </div>

            <span class="admin-status admin-status-online">
                <span></span>
                Control enabled
            </span>

        </header>

        <section class="admin-template-intro">

            <p class="admin-overline">
                STARTER COLLECTION
            </p>

            <h2>
                Choose the atmosphere
                <em>of the application.</em>
            </h2>

            <p>
                Installation creates an editable theme.
                Activation decides which presentation
                layer your visitors see.
            </p>

        </section>

        <section class="admin-template-grid">

            <?php foreach (
                $templates as $index => $template
            ): ?>

                <?php
                $slug = (string) (
                    $template['slug'] ?? ''
                );

                $name = (string) (
                    $template['name']
                    ?? ucfirst($slug)
                );

                $description = (string) (
                    $template['description']
                    ?? ''
                );

                $isInstalled = in_array(
                    $slug,
                    $installedSlugs,
                    true
                );

                $isActive =
                    $slug === $activeTemplate;
                ?>

                <article
                    class="admin-template-card
                    <?= $isActive
                        ? 'admin-template-card-active'
                        : '' ?>"
                >

                    <div class="admin-template-art">

                        <span>
                            <?= str_pad(
                                (string) ($index + 1),
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                strtoupper(
                                    substr($name, 0, 1)
                                )
                            ) ?>
                        </strong>

                    </div>

                    <div class="admin-template-body">

                        <div class="admin-template-status">

                            <?php if ($isActive): ?>

                                <span
                                    class="admin-template-badge
                                    admin-template-badge-active"
                                >
                                    Active
                                </span>

                            <?php elseif ($isInstalled): ?>

                                <span
                                    class="admin-template-badge"
                                >
                                    Installed
                                </span>

                            <?php else: ?>

                                <span
                                    class="admin-template-badge"
                                >
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

                        <div class="admin-template-actions">

                            <?php if (!$isInstalled): ?>

                                <form
                                    method="post"
                                    action="/admin/templates/install/<?= htmlspecialchars(
                                        $slug
                                    ) ?>"
                                >
                                    <button
                                        class="admin-button"
                                        type="submit"
                                    >
                                        Install
                                        <span>+</span>
                                    </button>
                                </form>

                            <?php endif; ?>

                            <?php if (
                                $isInstalled &&
                                !$isActive
                            ): ?>

                                <form
                                    method="post"
                                    action="/admin/templates/activate/<?= htmlspecialchars(
                                        $slug
                                    ) ?>"
                                >
                                    <button
                                        class="admin-button admin-button-primary"
                                        type="submit"
                                    >
                                        Activate
                                        <span>↗</span>
                                    </button>
                                </form>

                            <?php endif; ?>

                            <?php if ($isActive): ?>

                                <a
                                    class="admin-button"
                                    href="/"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    View Site
                                    <span>↗</span>
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </section>

    </main>

</div>
