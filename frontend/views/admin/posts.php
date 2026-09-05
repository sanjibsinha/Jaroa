<?php

$pageTitle = 'Jaroa · All Posts';

$user = $user ?? [];
$posts = $posts ?? [];
$total = $total ?? count($posts);

$currentUserName =
    $user['name']
    ?? $user['email']
    ?? 'User';
$currentUserRole =
    $user['role']
    ?? 'editor';
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
                href="/admin/posts"
            >
                <span>02</span>
                All Posts
            </a>

            <a
                class="admin-nav-link"
                href="/admin/posts/add"
            >
                <span>03</span>
                Add Post
            </a>

            <a
                class="admin-nav-link"
                href="/admin/templates"
            >
                <span>04</span>
                Templates
            </a>

            <a
                class="admin-nav-link"
                href="/"
                target="_blank"
                rel="noopener"
            >
                <span>05</span>
                View Site
            </a>

        </nav>

        <div class="admin-sidebar-footer">

            <div class="admin-user-mini">

                <span class="admin-user-dot"></span>

                <div>
                    <strong>
                        <?= htmlspecialchars(
                            (string) $currentUserName
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            ucfirst(
                                (string) $currentUserRole
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
                    JAROA / CONTENT
                </p>

                <h1>
                    All Posts
                </h1>
            </div>

            <div class="admin-topbar-actions">

                <span class="admin-record-count">
                    <?= (int) $total ?>
                    total
                </span>

                <a
                    class="admin-button admin-button-primary"
                    href="/admin/posts/add"
                >
                    Add Post
                    <span>+</span>
                </a>

            </div>

        </header>

        <section class="admin-page-intro">

            <div>
                <p class="admin-overline">
                    CONTENT LIBRARY
                </p>

                <h2>
                    Everything your application publishes.
                </h2>

                <p>
                    Posts below are coming directly from
                    the native Jaroa Engine.
                </p>
            </div>

        </section>

        <section class="admin-panel admin-posts-panel">

            <div class="admin-panel-heading">

                <div>
                    <p class="admin-overline">
                        POSTS
                    </p>

                    <h2>
                        All posts
                    </h2>
                </div>

                <span>
                    <?= (int) $total ?>
                    records
                </span>

            </div>

            <?php if ($posts === []): ?>

                <div class="admin-empty admin-empty-large">

                    <div class="admin-empty-mark">
                        +
                    </div>

                    <h3>
                        No posts yet.
                    </h3>

                    <p>
                        Your first piece of content can start here.
                    </p>

                    <a
                        class="admin-button admin-button-primary"
                        href="/admin/posts/add"
                    >
                        Create First Post
                        <span>↗</span>
                    </a>

                </div>

            <?php else: ?>

                <div class="admin-post-table-wrap">

                    <table class="admin-post-table">

                        <thead>
                            <tr>
                                <th>Post</th>
                                <th>Status</th>
                                <th>Slug</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($posts as $post): ?>

                                <?php
                                $status =
                                    $post['status']
                                    ?? 'published';

                                $title =
                                    $post['title']
                                    ?? 'Untitled';

                                $slug =
                                    $post['slug']
                                    ?? '';

                                $date =
                                    $post['date']
                                    ?? $post['created_at']
                                    ?? '';
                                ?>

                                <tr>

                                    <td>
                                        <div class="admin-post-title-cell">
                                            <strong>
                                                <?= htmlspecialchars(
                                                    (string) $title
                                                ) ?>
                                            </strong>

                                            <span>
                                                ID
                                                <?= (int) (
                                                    $post['id']
                                                    ?? 0
                                                ) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="admin-post-status admin-post-status-<?= htmlspecialchars(
                                                strtolower(
                                                    (string) $status
                                                )
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    (string) $status
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <code>
                                            <?= htmlspecialchars(
                                                (string) $slug
                                            ) ?>
                                        </code>
                                    </td>

                                    <td>
                                        <span class="admin-post-date">
                                            <?= htmlspecialchars(
                                                (string) $date
                                            ) ?>
                                        </span>
                                    </td>

                                    <td class="admin-post-action-cell">



                                        <div class="admin-table-actions">



                                            <a


                                                class="admin-action-button"


                                                href="/admin/posts/<?= (int) ($post['id'] ?? 0) ?>/edit"


                                            >


                                                Edit


                                            </a>



                                            <form


                                                method="post"


                                                action="/admin/posts/<?= (int) ($post['id'] ?? 0) ?>/delete"


                                                onsubmit="return confirm('Delete this post permanently?');"


                                            >


                                                <button


                                                    class="admin-action-button admin-action-button-delete"


                                                    type="submit"


                                                >


                                                    Delete


                                                </button>


                                            </form>



                                        </div>



                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>
