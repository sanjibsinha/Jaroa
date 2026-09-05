<?php

$pageTitle = 'Jaroa · Dashboard';

$user = $user ?? [];
$recentPosts = $recentPosts ?? [];
$templates = $templates ?? [];
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
                class="admin-nav-link admin-nav-link-active"
                href="/admin"
            >
                <span>01</span>
                Dashboard
            </a>

            <a
                class="admin-nav-link"
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

            <div class="admin-user-mini">

                <span class="admin-user-dot"></span>

                <div>
                    <strong>
                        <?= htmlspecialchars(
                            (string) (
                                $user['name']
                                ?? $user['email']
                                ?? 'User'
                            )
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            ucfirst(
                                (string) (
                                    $user['role']
                                    ?? 'editor'
                                )
                            )
                        ) ?>
                    </span>
                </div>

            </div>

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
                    JAROA / CONTROL ROOM
                </p>

                <h1>
                    Dashboard
                </h1>
            </div>

            <div class="admin-topbar-status">

                <span
                    class="<?= $engineOnline
                        ? 'admin-status admin-status-online'
                        : 'admin-status admin-status-offline' ?>"
                >
                    <span></span>

                    Engine
                    <?= $engineOnline
                        ? 'Online'
                        : 'Offline' ?>
                </span>

            </div>

        </header>

        <section class="admin-welcome">

            <div>
                <p class="admin-overline">
                    CURRENT WORKSPACE
                </p>

                <h2>
                    Your application,
                    <em>under control.</em>
                </h2>

                <p>
                    Manage content, presentation and the
                    visual identity of your Jaroa application
                    from one place.
                </p>
            </div>

            <div class="admin-workspace-mark">
                J
            </div>

        </section>

        <section class="admin-stat-grid">

            <article class="admin-stat-card">
                <span class="admin-stat-label">
                    POSTS
                </span>

                <strong>
                    <?= $postCount ?>
                </strong>

                <span class="admin-stat-note">
                    Stored in Jaroa Engine
                </span>
            </article>

            <article class="admin-stat-card">
                <span class="admin-stat-label">
                    TEMPLATES
                </span>

                <strong>
                    <?= count($templates) ?>
                </strong>

                <span class="admin-stat-note">
                    Starter themes available
                </span>
            </article>

            <article class="admin-stat-card">
                <span class="admin-stat-label">
                    ACTIVE
                </span>

                <strong class="admin-stat-word">
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) (
                                $activeTemplate
                                ?? 'None'
                            )
                        )
                    ) ?>
                </strong>

                <span class="admin-stat-note">
                    Current presentation layer
                </span>
            </article>

        </section>

        <section class="admin-content-grid">

            <div class="admin-panel">

                <div class="admin-panel-heading">

                    <div>
                        <p class="admin-overline">
                            CONTENT
                        </p>

                        <h2>
                            Recent posts
                        </h2>
                    </div>

                    <span>
                        <?= $postCount ?>
                        total
                    </span>

                </div>

                <div class="admin-list">

                    <?php if ($recentPosts === []): ?>

                        <div class="admin-empty">
                            No posts yet.
                        </div>

                    <?php else: ?>

                        <?php foreach (
                            $recentPosts as $index => $post
                        ): ?>

                            <a
                                class="admin-list-row"
                                href="/articles/<?= htmlspecialchars(
                                    (string) $post['slug']
                                ) ?>"
                                target="_blank"
                                rel="noopener"
                            >

                                <span class="admin-list-number">
                                    <?= str_pad(
                                        (string) ($index + 1),
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>
                                </span>

                                <div class="admin-list-copy">

                                    <strong>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $post['title']
                                                ?? ''
                                            )
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $post['date']
                                                ?? $post['created_at']
                                                ?? ''
                                            )
                                        ) ?>
                                    </span>

                                </div>

                                <span class="admin-list-arrow">
                                    ↗
                                </span>

                            </a>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

            <div class="admin-panel admin-panel-accent">

                <p class="admin-overline">
                    PRESENTATION
                </p>

                <h2>
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) (
                                $activeTemplate
                                ?? 'No active theme'
                            )
                        )
                    ) ?>
                </h2>

                <p>
                    The public frontend is powered by
                    the current starter theme. Change it,
                    then inspect the result immediately.
                </p>

                <a
                    class="admin-button"
                    href="/admin/templates"
                >
                    Manage Templates
                    <span>↗</span>
                </a>

            </div>

        </section>

        <section class="admin-foundation">

            <div class="admin-foundation-heading">

                <p class="admin-overline">
                    v0.1 FOUNDATION
                </p>

                <h2>
                    One control room.
                    Many surfaces.
                </h2>

            </div>

            <div class="admin-foundation-grid">

                <article>
                    <span>01</span>
                    <strong>Content</strong>
                    <p>
                        Posts and editorial data live in
                        the native Jaroa Engine.
                    </p>
                </article>

                <article>
                    <span>02</span>
                    <strong>Presentation</strong>
                    <p>
                        Starter themes remain independent
                        from the application core.
                    </p>
                </article>

                <article>
                    <span>03</span>
                    <strong>Administration</strong>
                    <p>
                        Authentication and control surfaces
                        belong to the application layer.
                    </p>
                </article>

            </div>

        </section>

    </main>

</div>
