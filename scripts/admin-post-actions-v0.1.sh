#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
LOG="$HOME/jaroa-admin-post-actions-v0.1-build-${STAMP}.txt"
BACKUP="$HOME/jaroa-admin-post-actions-v0.1-backup-${STAMP}"

exec > >(tee "$LOG") 2>&1

echo "============================================================"
echo " JAROA ADMIN POST ACTIONS v0.1"
echo " Dashboard spacing + Edit + Delete"
echo "============================================================"
echo
echo "ROOT:   $ROOT"
echo "LOG:    $LOG"
echo "BACKUP: $BACKUP"
echo

cd "$ROOT"

mkdir -p "$BACKUP"

echo "===== BACKUP ====="

for file in \
    frontend/app/Admin/AdminAuth.php \
    frontend/app/Api/ApiClient.php \
    frontend/app/Application.php \
    frontend/app/Controllers/AdminController.php \
    frontend/views/admin/dashboard.php \
    frontend/views/admin/add-post.php \
    frontend/views/admin/posts.php \
    frontend/public/assets/css/jaroa-admin.css
do
    if [ -f "$file" ]; then
        mkdir -p "$BACKUP/$(dirname "$file")"
        cp "$file" "$BACKUP/$file"
        echo "Backed up: $file"
    fi
done

echo
echo "===== ENSURING AUTH TOKEN ACCESS ====="

python3 - <<'PY'
from pathlib import Path

path = Path("frontend/app/Admin/AdminAuth.php")
text = path.read_text()

if "public function token()" not in text:
    marker = "\n    public function logout("

    if marker not in text:
        raise SystemExit(
            "ERROR: Could not locate AdminAuth::logout()."
        )

    method = r'''
    public function token(): ?string
    {
        $token = $_SESSION['jaroa_admin_token'] ?? null;

        return is_string($token) && $token !== ''
            ? $token
            : null;
    }

'''

    text = text.replace(
        marker,
        "\n" + method + "    public function logout(",
        1
    )

    path.write_text(text)

    print("✅ AdminAuth::token() added.")
else:
    print("✅ AdminAuth::token() already exists.")
PY

echo
echo "===== ENSURING API PUT/DELETE SUPPORT ====="

python3 - <<'PY'
from pathlib import Path

path = Path("frontend/app/Api/ApiClient.php")
text = path.read_text()

changed = False

if "public function put(" not in text:
    marker = "\n    private function request("

    if marker not in text:
        raise SystemExit(
            "ERROR: Could not locate ApiClient::request()."
        )

    methods = r'''
    public function put(
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        return $this->request(
            'PUT',
            $endpoint,
            $headers,
            $data
        );
    }

'''

    text = text.replace(
        marker,
        "\n" + methods + "    private function request(",
        1
    )

    changed = True
    print("✅ ApiClient::put() added.")
else:
    print("✅ ApiClient::put() already exists.")

if "public function delete(" not in text:
    marker = "\n    private function request("

    if marker not in text:
        raise SystemExit(
            "ERROR: Could not locate ApiClient::request() for DELETE."
        )

    method = r'''
    public function delete(
        string $endpoint,
        array $headers = []
    ): array {
        return $this->request(
            'DELETE',
            $endpoint,
            $headers
        );
    }

'''

    text = text.replace(
        marker,
        "\n" + method + "    private function request(",
        1
    )

    changed = True
    print("✅ ApiClient::delete() added.")
else:
    print("✅ ApiClient::delete() already exists.")

if changed:
    path.write_text(text)
else:
    print("✅ ApiClient already has both methods.")
PY

echo
echo "===== ADDING EDIT/DELETE CONTROLLER ACTIONS ====="

python3 - <<'PY'
from pathlib import Path

path = Path("frontend/app/Controllers/AdminController.php")
text = path.read_text()

marker = "\n    public function templates("

if marker not in text:
    raise SystemExit(
        "ERROR: Could not locate AdminController::templates()."
    )

if "public function editPostForm(" not in text:

    methods = r'''
    public function editPostForm(
        array $parameters
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        try {
            $post = $this->posts->find($id);
        } catch (\Throwable) {
            http_response_code(404);

            View::render(
                'admin/forbidden',
                [
                    'message' =>
                        'The requested post could not be found.',
                ],
                'admin'
            );

            return;
        }

        if (!is_array($post)) {
            throw new RuntimeException(
                'Post could not be loaded.'
            );
        }

        View::render(
            'admin/edit-post',
            [
                'user' => $user,
                'error' => null,
                'post' => $post,
            ],
            'admin'
        );
    }

    public function updatePost(
        array $parameters
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        $title = trim(
            (string) ($_POST['title'] ?? '')
        );

        $slug = trim(
            (string) ($_POST['slug'] ?? '')
        );

        $status = trim(
            (string) ($_POST['status'] ?? 'published')
        );

        $content = (string) (
            $_POST['content'] ?? ''
        );

        if ($title === '') {
            $this->renderEditPostError(
                $user,
                $id,
                'Post title is required.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if ($slug === '') {
            $slug = $this->makeSlug($title);
        }

        if ($slug === '') {
            $this->renderEditPostError(
                $user,
                $id,
                'A valid post slug is required.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if (
            !in_array(
                $status,
                ['draft', 'published'],
                true
            )
        ) {
            $status = 'published';
        }

        $token = $this->auth->token();

        if ($token === null) {
            $this->redirect('/admin/login');
        }

        try {
            $this->api->put(
                '/posts/' . $id,
                [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'status' => $status,
                ],
                [
                    'Authorization' =>
                        'Bearer ' . $token,
                ]
            );
        } catch (\Throwable) {
            $this->renderEditPostError(
                $user,
                $id,
                'The post could not be updated. Check the post data and try again.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        $this->redirect('/admin');
    }

    public function deletePost(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        $token = $this->auth->token();

        if ($token === null) {
            $this->redirect('/admin/login');
        }

        try {
            $this->api->delete(
                '/posts/' . $id,
                [
                    'Authorization' =>
                        'Bearer ' . $token,
                ]
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'The post could not be deleted.',
                0,
                $exception
            );
        }

        $this->redirect('/admin');
    }

    private function renderEditPostError(
        array $user,
        int $id,
        string $error,
        array $old
    ): void {
        View::render(
            'admin/edit-post',
            [
                'user' => $user,
                'error' => $error,
                'post' => [
                    'id' => $id,
                    'title' =>
                        (string) (
                            $old['title'] ?? ''
                        ),
                    'slug' =>
                        (string) (
                            $old['slug'] ?? ''
                        ),
                    'status' =>
                        (string) (
                            $old['status']
                            ?? 'published'
                        ),
                    'content' =>
                        (string) (
                            $old['content'] ?? ''
                        ),
                ],
            ],
            'admin'
        );
    }

'''
    text = text.replace(
        marker,
        "\n" + methods + "    public function templates(",
        1
    )

    path.write_text(text)
    print("✅ Edit/update/delete controller actions added.")
else:
    print(
        "✅ Edit/update/delete controller actions already exist. "
        "No duplicate insertion."
    )
PY

echo
echo "===== ADDING ADMIN ROUTES ====="

python3 - <<'PY'
from pathlib import Path

path = Path("frontend/app/Application.php")
text = path.read_text()

anchor = r'''        $this->router->get(
            '/admin/templates',
            [$adminController, 'templates']
        );
'''

if "[$adminController, 'editPostForm']" not in text:

    if anchor not in text:
        raise SystemExit(
            "ERROR: Could not locate /admin/templates route."
        )

    routes = r'''
        $this->router->get(
            '/admin/posts/{id}/edit',
            [$adminController, 'editPostForm']
        );

        $this->router->post(
            '/admin/posts/{id}/edit',
            [$adminController, 'updatePost']
        );

        $this->router->post(
            '/admin/posts/{id}/delete',
            [$adminController, 'deletePost']
        );

'''

    text = text.replace(
        anchor,
        routes + anchor,
        1
    )

    path.write_text(text)

    print("✅ Edit/delete admin routes added.")
else:
    print("✅ Edit/delete admin routes already exist.")
PY

echo
echo "===== REBUILDING DASHBOARD RECENT POSTS BLOCK ====="

python3 - <<'PY'
from pathlib import Path
import re

path = Path("frontend/views/admin/dashboard.php")
text = path.read_text()

start_marker = '<div class="admin-list">'

start = text.find(start_marker)

if start == -1:
    raise SystemExit(
        "ERROR: Could not locate dashboard admin-list block."
    )

# Find the matching </div> using a simple tag-depth scan.
tag_pattern = re.compile(
    r'<div\b[^>]*>|</div>',
    re.IGNORECASE
)

depth = 0
end = None

for match in tag_pattern.finditer(
    text,
    start
):
    token = match.group(0).lower()

    if token.startswith('<div'):
        depth += 1
    else:
        depth -= 1

        if depth == 0:
            end = match.end()
            break

if end is None:
    raise SystemExit(
        "ERROR: Could not find matching closing </div>."
    )

replacement = r'''<div class="admin-list admin-recent-posts">

                    <?php if ($recentPosts === []): ?>

                        <div class="admin-empty">
                            No posts yet.
                        </div>

                    <?php else: ?>

                        <?php foreach (
                            $recentPosts as $index => $post
                        ): ?>

                            <?php
                            $postId = (int) (
                                $post['id'] ?? 0
                            );

                            $slug = (string) (
                                $post['slug'] ?? ''
                            );

                            $title = (string) (
                                $post['title'] ?? 'Untitled'
                            );

                            $date = (string) (
                                $post['date']
                                ?? $post['created_at']
                                ?? ''
                            );
                            ?>

                            <article
                                class="admin-recent-post"
                            >

                                <a
                                    class="admin-list-row"
                                    href="/articles/<?= htmlspecialchars(
                                        $slug
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >

                                    <span
                                        class="admin-list-number"
                                    >
                                        <?= str_pad(
                                            (string) (
                                                $index + 1
                                            ),
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        ) ?>
                                    </span>

                                    <div
                                        class="admin-list-copy"
                                    >

                                        <strong>
                                            <?= htmlspecialchars(
                                                $title
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $date
                                            ) ?>
                                        </span>

                                    </div>

                                    <span
                                        class="admin-list-arrow"
                                        title="View post"
                                    >
                                        ↗
                                    </span>

                                </a>

                                <div
                                    class="admin-post-actions"
                                >

                                    <a
                                        class="admin-action-button"
                                        href="/admin/posts/<?= $postId ?>/edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="/admin/posts/<?= $postId ?>/delete"
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

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>'''

text = (
    text[:start]
    + replacement
    + text[end:]
)

path.write_text(text)

print("✅ Dashboard recent-post block rebuilt.")
PY

echo
echo "===== CREATING EDIT POST VIEW ====="

cat > frontend/views/admin/edit-post.php <<'PHP'
<?php

$pageTitle = 'Jaroa · Edit Post';

$user = $user ?? [];
$error = $error ?? null;
$post = $post ?? [];

$postId = (int) (
    $post['id'] ?? 0
);

$title = (string) (
    $post['title'] ?? ''
);

$slug = (string) (
    $post['slug'] ?? ''
);

$status = (string) (
    $post['status'] ?? 'published'
);

$content = (string) (
    $post['content'] ?? ''
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
                    Edit Post
                </h1>
            </div>

            <div class="admin-topbar-actions">

                <a
                    class="admin-button"
                    href="/admin"
                >
                    Dashboard
                    <span>↗</span>
                </a>

                <a
                    class="admin-button"
                    href="/admin/posts"
                >
                    All Posts
                    <span>↗</span>
                </a>

            </div>

        </header>

        <section class="admin-page-intro">

            <div>

                <p class="admin-overline">
                    EDITORIAL CONTENT
                </p>

                <h2>
                    Refine the story.
                </h2>

                <p>
                    Update this post directly in the
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
                action="/admin/posts/<?= $postId ?>/edit"
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
                        maxlength="255"
                    >

                </label>

                <label>

                    <span>
                        Content
                    </span>

                    <textarea
                        name="content"
                        rows="18"
                    ><?= htmlspecialchars(
                        $content
                    ) ?></textarea>

                </label>

                <div class="admin-post-form-footer">

                    <a
                        class="admin-button"
                        href="/admin"
                    >
                        Cancel
                    </a>

                    <form
                        method="post"
                        action="/admin/posts/<?= $postId ?>/delete"
                        onsubmit="return confirm('Delete this post permanently?');"
                    >
                        <button
                            class="admin-action-button admin-action-button-delete"
                            type="submit"
                        >
                            Delete Post
                        </button>
                    </form>

                    <button
                        class="admin-button admin-button-primary"
                        type="submit"
                    >
                        Save Changes
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

    const initialSlug = slug.value.trim();

    title.addEventListener('input', function () {
        if (slug.value.trim() !== initialSlug) {
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
PHP

echo "✅ Edit Post view created."

echo
echo "===== EXTENDING ADMIN CSS ====="

if ! grep -q 'Admin Post Actions v0.1' \
    frontend/public/assets/css/jaroa-admin.css
then

cat >> frontend/public/assets/css/jaroa-admin.css <<'CSS'

/* ----------------------------------------------------------
   Admin Post Actions v0.1
   ---------------------------------------------------------- */

.admin-recent-posts {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.admin-recent-post {
    padding: 0.95rem 0 0.95rem;
    border-bottom: 1px solid var(--admin-border, #e5e1d8);
}

.admin-recent-post:last-child {
    border-bottom: 0;
    padding-bottom: 0.25rem;
}

.admin-recent-post .admin-list-row {
    margin: 0;
}

.admin-post-actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.7rem;
    padding-left: 2.7rem;
}

.admin-post-actions form {
    margin: 0;
}

.admin-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0 0.7rem;
    border: 1px solid var(--admin-border, #dcd8cf);
    border-radius: 0.35rem;
    background: transparent;
    color: inherit;
    font: inherit;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-decoration: none;
    text-transform: uppercase;
    cursor: pointer;
    transition:
        background-color 150ms ease,
        border-color 150ms ease,
        color 150ms ease;
}

.admin-action-button:hover {
    background: rgba(0, 0, 0, 0.055);
}

.admin-action-button-delete {
    color: #9a3838;
}

.admin-action-button-delete:hover {
    border-color: #9a3838;
    background: rgba(154, 56, 56, 0.08);
}

.admin-post-form-footer > form {
    margin: 0;
}

@media (max-width: 760px) {
    .admin-post-actions {
        padding-left: 0;
    }

    .admin-post-actions,
    .admin-post-form-footer {
        flex-wrap: wrap;
    }
}

CSS

echo "✅ Post-action CSS added."

else

    echo "✅ Post-action CSS already present. Skipping duplicate append."

fi

echo
echo "===== PHP SYNTAX CHECKS ====="

(
    cd "$ROOT/frontend"

    FILES=(
        app/Admin/AdminAuth.php
        app/Api/ApiClient.php
        app/Application.php
        app/Controllers/AdminController.php
        app/View.php
        views/admin/dashboard.php
        views/admin/add-post.php
        views/admin/edit-post.php
        views/admin/posts.php
        views/layouts/admin.php
    )

    for file in "${FILES[@]}"; do
        echo
        echo "--- $file ---"
        ddev exec php -l "$file"
    done
)

echo
echo "===== VERIFY ADMIN ROUTES ====="

grep -nE \
    "/admin/posts|editPostForm|updatePost|deletePost|allPosts|addPostForm" \
    frontend/app/Application.php

echo
echo "===== VERIFY DASHBOARD ACTIONS ====="

grep -nE \
    'admin-recent-post|admin-action-button|/admin/posts/.*/edit|/admin/posts/.*/delete' \
    frontend/views/admin/dashboard.php \
    | head -80

echo
echo "===== VERIFY ENGINE POSTS API ====="

curl -sS \
    -o /tmp/jaroa-admin-post-actions-api.json \
    -w 'HTTP %{http_code}\n' \
    'https://jaroa-engine.ddev.site/api/v1/posts?page=1&limit=5&sort=created_at&order=desc'

cat /tmp/jaroa-admin-post-actions-api.json

rm -f /tmp/jaroa-admin-post-actions-api.json

echo
echo "===== VERIFY ADMIN LOGIN PAGE ====="

curl -sS \
    -o /tmp/jaroa-admin-login.html \
    -w 'HTTP %{http_code}\n' \
    https://frontend.ddev.site/admin/login

grep -nE \
    'jaroa-admin.css|Admin Console|Jaroa' \
    /tmp/jaroa-admin-login.html \
    | head -20

rm -f /tmp/jaroa-admin-login.html

echo
echo "===== GIT STATUS ====="

git status --short

echo
echo "============================================================"
echo " ✅ JAROA ADMIN POST ACTIONS v0.1 READY"
echo "============================================================"
echo
echo "Dashboard:"
echo "https://frontend.ddev.site/admin"
echo
echo "All Posts:"
echo "https://frontend.ddev.site/admin/posts"
echo
echo "Build log:"
echo "$LOG"
echo
echo "Backup:"
echo "$BACKUP"
echo
echo "============================================================"
