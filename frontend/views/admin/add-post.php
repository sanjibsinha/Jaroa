<?php

$pageTitle = 'Jaroa · Add Post';

$user = $user ?? [];
$error = $error ?? null;
$old = $old ?? [];

$title = (string) ($old['title'] ?? '');
$slug = (string) ($old['slug'] ?? '');
$status = (string) (
    $old['status'] ?? 'published'
);
$content = (string) (
    $old['content'] ?? ''
);

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
                class="admin-nav-link"
                href="/admin/posts"
            >
                <span>02</span>
                All Posts
            </a>

            <a
                class="admin-nav-link admin-nav-link-active"
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
                    Add Post
                </h1>
            </div>

            <a
                class="admin-button"
                href="/admin/posts"
            >
                All Posts
                <span>↗</span>
            </a>

        </header>

        <section class="admin-page-intro">

            <div>
                <p class="admin-overline">
                    NEW CONTENT
                </p>

                <h2>
                    Create something worth publishing.
                </h2>

                <p>
                    This form writes directly to the
                    native Jaroa Engine.
                </p>
            </div>

        </section>

        <?php if ($error !== null): ?>

            <div class="admin-alert admin-alert-error">
                <?= htmlspecialchars(
                    (string) $error
                ) ?>
            </div>

        <?php endif; ?>

        <section class="admin-panel admin-post-editor-panel">

            <form
                class="admin-form admin-post-form"
                method="post"
                action="/admin/posts/add"
            >

                <div class="admin-form-grid">

                    <label>

                        <span>
                            Title
                        </span>

                        <input
                            type="text"
                            name="title"
                            id="post-title"
                            value="<?= htmlspecialchars(
                                $title
                            ) ?>"
                            placeholder="Enter post title"
                            maxlength="255"
                            required
                            autofocus
                        >

                    </label>

                    <label>

                        <span>
                            Status
                        </span>

                        <select name="status">

                            <option
                                value="published"
                                <?= $status === 'published'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Published
                            </option>

                            <option
                                value="draft"
                                <?= $status === 'draft'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Draft
                            </option>

                        </select>

                    </label>

                </div>

                <label>

                    <span>
                        Slug
                    </span>

                    <input
                        type="text"
                        name="slug"
                        id="post-slug"
                        value="<?= htmlspecialchars(
                            $slug
                        ) ?>"
                        placeholder="your-post-slug"
                        maxlength="255"
                    >

                    <small class="admin-form-help">
                        Leave blank and Jaroa will generate it
                        from the title.
                    </small>

                </label>

                <label>

                    <span>
                        Content
                    </span>

                    <textarea
                        name="content"
                        rows="18"
                        placeholder="Write the post content here..."
                    ><?= htmlspecialchars(
                        $content
                    ) ?></textarea>

                </label>

                <div class="admin-post-form-footer">

                    <a
                        class="admin-button"
                        href="/admin/posts"
                    >
                        Cancel
                    </a>

                    <button
                        class="admin-button admin-button-primary"
                        type="submit"
                    >
                        Create Post
                        <span>↗</span>
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<script>
(function () {
    const title = document.getElementById('post-title');
    const slug = document.getElementById('post-slug');

    if (!title || !slug) {
        return;
    }

    let slugTouched = slug.value.trim() !== '';

    slug.addEventListener('input', function () {
        slugTouched = slug.value.trim() !== '';
    });

    title.addEventListener('input', function () {
        if (slugTouched) {
            return;
        }

        slug.value = title.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
})();
</script>
