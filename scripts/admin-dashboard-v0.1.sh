#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND="$ROOT/frontend"
BACKUP_DIR="$ROOT/../jaroa-admin-v0.1-backup-$(date +%Y%m%d-%H%M%S)"
LOG_FILE="$ROOT/../jaroa-admin-v0.1-build-$(date +%Y%m%d-%H%M%S).txt"

mkdir -p "$BACKUP_DIR"

echo "============================================================"
echo " JAROA ADMIN DASHBOARD v0.1"
echo "============================================================"
echo
echo "Root     : $ROOT"
echo "Frontend : $FRONTEND"
echo "Backup   : $BACKUP_DIR"
echo "Log      : $LOG_FILE"
echo

exec > >(tee "$LOG_FILE") 2>&1

fail() {
    echo
    echo "❌ ADMIN DASHBOARD BUILD FAILED"
    echo "Backup is available at:"
    echo "$BACKUP_DIR"
    exit 1
}

trap 'fail' ERR

cd "$ROOT"

echo "===== PREFLIGHT ====="

test -d "$FRONTEND/app"
test -d "$FRONTEND/views"
test -f "$FRONTEND/app/Application.php"
test -f "$FRONTEND/app/Api/ApiClient.php"
test -f "$FRONTEND/app/View.php"

echo "Frontend structure verified."

echo
echo "===== BACKUP ====="

mkdir -p "$BACKUP_DIR/frontend/app/Api"
mkdir -p "$BACKUP_DIR/frontend/app"
mkdir -p "$BACKUP_DIR/frontend/views"

cp "$FRONTEND/app/Api/ApiClient.php" \
   "$BACKUP_DIR/frontend/app/Api/ApiClient.php"

cp "$FRONTEND/app/Application.php" \
   "$BACKUP_DIR/frontend/app/Application.php"

echo "Original frontend files backed up."

echo
echo "===== DIRECTORIES ====="

mkdir -p \
    "$FRONTEND/app/Admin" \
    "$FRONTEND/views/admin" \
    "$FRONTEND/views/layouts" \
    "$FRONTEND/public/assets/css"

echo "Admin directories created."

echo
echo "===== API CLIENT ====="

cat > "$FRONTEND/app/Api/ApiClient.php" <<'PHP'
<?php

namespace App\Api;

use App\Exceptions\NotFoundException;
use RuntimeException;

class ApiClient
{
    private readonly string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send a GET request to the API.
     */
    public function get(
        string $endpoint,
        array $query = [],
        array $headers = []
    ): array {
        return $this->request(
            'GET',
            $endpoint,
            $query,
            null,
            $headers
        );
    }

    /**
     * Send a POST request with a JSON body.
     */
    public function post(
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        return $this->request(
            'POST',
            $endpoint,
            [],
            $data,
            $headers
        );
    }

    /**
     * Build and execute an HTTP request.
     */
    private function request(
        string $method,
        string $endpoint,
        array $query = [],
        ?array $json = null,
        array $headers = []
    ): array {
        $url = $this->buildUrl($endpoint, $query);

        $requestHeaders = [
            'Accept: application/json',
        ];

        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => $requestHeaders,
                'ignore_errors' => true,
            ],
        ];

        if ($json !== null) {
            $encoded = json_encode(
                $json,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            if (false === $encoded) {
                throw new RuntimeException(
                    'Unable to encode API request JSON.'
                );
            }

            $options['http']['header'][] =
                'Content-Type: application/json';

            $options['http']['content'] = $encoded;
        }

        $context = stream_context_create($options);

        $response = file_get_contents(
            $url,
            false,
            $context
        );

        if (false === $response) {
            throw new RuntimeException(
                "Unable to connect to API: {$url}"
            );
        }

        $statusCode = $this->getStatusCode(
            $http_response_header ?? []
        );

        $data = json_decode(
            $response,
            true
        );

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                'API returned invalid JSON: ' .
                json_last_error_msg()
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message =
                $data['error']['message']
                ?? $data['message']
                ?? 'API request failed.';

            if (404 === $statusCode) {
                throw new NotFoundException($message);
            }

            throw new RuntimeException(
                "API request failed ({$statusCode}): {$message}"
            );
        }

        return $data;
    }

    /**
     * Build the complete API URL.
     */
    private function buildUrl(
        string $endpoint,
        array $query = []
    ): string {
        $endpoint = '/' . ltrim(
            $endpoint,
            '/'
        );

        $url = $this->baseUrl . $endpoint;

        if ([] !== $query) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * Extract the HTTP status code from response headers.
     */
    private function getStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (
                preg_match(
                    '/^HTTP\/\S+\s+(\d{3})/',
                    $header,
                    $matches
                )
            ) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
PHP

echo "ApiClient upgraded."

echo
echo "===== ADMIN AUTHENTICATION ====="

cat > "$FRONTEND/app/Admin/AdminAuth.php" <<'PHP'
<?php

namespace App\Admin;

use App\Api\ApiClient;
use RuntimeException;

final class AdminAuth
{
    private const SESSION_TOKEN = 'jaroa_admin_token';
    private const SESSION_USER = 'jaroa_admin_user';

    public function __construct(
        private readonly ApiClient $api
    ) {
    }

    public function login(
        string $email,
        string $password
    ): array {
        $this->startSession();

        $response = $this->api->post(
            '/auth/login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        $token = $this->findString(
            $response,
            [
                'token',
                'access_token',
            ]
        );

        if ($token === null || $token === '') {
            throw new RuntimeException(
                'Authentication token was not returned by the Engine.'
            );
        }

        $user = $this->findArray(
            $response,
            ['user']
        );

        if ($user === null) {
            $me = $this->api->get(
                '/auth/me',
                [],
                [
                    'Authorization' => 'Bearer ' . $token,
                ]
            );

            $user = $this->unwrapData($me);
        }

        if (!is_array($user)) {
            throw new RuntimeException(
                'Authenticated user data was not returned by the Engine.'
            );
        }

        session_regenerate_id(true);

        $_SESSION[self::SESSION_TOKEN] = $token;
        $_SESSION[self::SESSION_USER] = $user;

        return $user;
    }

    public function logout(): void
    {
        $this->startSession();

        $token = $_SESSION[self::SESSION_TOKEN] ?? null;

        if (is_string($token) && $token !== '') {
            try {
                $this->api->post(
                    '/auth/logout',
                    [],
                    [
                        'Authorization' => 'Bearer ' . $token,
                    ]
                );
            } catch (\Throwable) {
                // Local session logout should still succeed.
            }
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        $this->startSession();

        return isset(
            $_SESSION[self::SESSION_TOKEN]
        );
    }

    public function currentUser(): ?array
    {
        $this->startSession();

        $user = $_SESSION[self::SESSION_USER] ?? null;

        return is_array($user) ? $user : null;
    }

    public function requireRole(
        array $allowedRoles
    ): array {
        if (!$this->isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }

        $user = $this->currentUser();

        if ($user === null) {
            $this->logout();

            header('Location: /admin/login');
            exit;
        }

        $role = $user['role'] ?? '';

        if (
            !is_string($role) ||
            !in_array($role, $allowedRoles, true)
        ) {
            http_response_code(403);

            throw new RuntimeException(
                'You do not have permission to access the admin area.'
            );
        }

        return $user;
    }

    public function token(): ?string
    {
        $this->startSession();

        $token = $_SESSION[self::SESSION_TOKEN] ?? null;

        return is_string($token) ? $token : null;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private function findString(
        array $data,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            if (
                isset($data[$key]) &&
                is_string($data[$key])
            ) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findString(
                    $value,
                    $keys
                );

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function findArray(
        array $data,
        array $keys
    ): ?array {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findArray(
                    $value,
                    $keys
                );

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function unwrapData(
        array $response
    ): mixed {
        return $response['data'] ?? $response;
    }
}
PHP

echo "Admin authentication service created."

echo
echo "===== ADMIN CONTROLLER ====="

cat > "$FRONTEND/app/Controllers/AdminController.php" <<'PHP'
<?php

namespace App\Controllers;

use App\Admin\AdminAuth;
use App\Api\ApiClient;
use App\Services\PostService;
use App\Templates\TemplateInstaller;
use App\Templates\TemplateManager;
use App\View;
use RuntimeException;

final class AdminController
{
    public function __construct(
        private readonly AdminAuth $auth,
        private readonly PostService $posts,
        private readonly TemplateManager $templates,
        private readonly TemplateInstaller $templateInstaller,
        private readonly ApiClient $api
    ) {
    }

    public function loginForm(
        array $parameters = []
    ): void {
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/admin');
        }

        View::render(
            'admin/login',
            [
                'error' => null,
                'email' => '',
            ],
            'admin'
        );
    }

    public function login(
        array $parameters = []
    ): void {
        $email = trim(
            (string) ($_POST['email'] ?? '')
        );

        $password = (string) (
            $_POST['password'] ?? ''
        );

        if (
            $email === '' ||
            $password === ''
        ) {
            View::render(
                'admin/login',
                [
                    'error' =>
                        'Email and password are required.',
                    'email' => $email,
                ],
                'admin'
            );

            return;
        }

        try {
            $user = $this->auth->login(
                $email,
                $password
            );

            $role = $user['role'] ?? '';

            if (
                !in_array(
                    $role,
                    ['admin', 'editor'],
                    true
                )
            ) {
                $this->auth->logout();

                View::render(
                    'admin/login',
                    [
                        'error' =>
                            'This account does not have admin access.',
                        'email' => $email,
                    ],
                    'admin'
                );

                return;
            }
        } catch (\Throwable) {
            View::render(
                'admin/login',
                [
                    'error' =>
                        'Unable to sign in. Check your credentials.',
                    'email' => $email,
                ],
                'admin'
            );

            return;
        }

        $this->redirect('/admin');
    }

    public function dashboard(
        array $parameters = []
    ): void {
        try {
            $user = $this->auth->requireRole(
                ['admin', 'editor']
            );
        } catch (RuntimeException $exception) {
            http_response_code(403);

            View::render(
                'admin/forbidden',
                [
                    'message' => $exception->getMessage(),
                ],
                'admin'
            );

            return;
        }

        $posts = $this->posts->paginate(
            1,
            5
        );

        $status = null;

        try {
            $statusResponse =
                $this->api->get('/status');

            $status =
                $statusResponse['status']
                ?? null;
        } catch (\Throwable) {
            $status = null;
        }

        View::render(
            'admin/dashboard',
            [
                'user' => $user,
                'postCount' =>
                    (int) (
                        $posts['meta']['total']
                        ?? 0
                    ),
                'recentPosts' =>
                    $posts['data']
                    ?? [],
                'engineOnline' =>
                    $status === 'ok',
                'activeTemplate' =>
                    $this->templates->active(),
                'templates' =>
                    $this->templates->available(),
            ],
            'admin'
        );
    }

    public function templates(
        array $parameters = []
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        View::render(
            'admin/templates',
            [
                'user' => $user,
                'templates' =>
                    $this->templates->available(),
                'installedTemplates' =>
                    $this->templates->installed(),
                'activeTemplate' =>
                    $this->templates->active(),
            ],
            'admin'
        );
    }

    public function installTemplate(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $slug = $parameters['slug'] ?? '';

        if (!is_string($slug) || $slug === '') {
            throw new RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templateInstaller->install(
            $slug
        );

        $this->redirect('/admin/templates');
    }

    public function activateTemplate(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $slug = $parameters['slug'] ?? '';

        if (!is_string($slug) || $slug === '') {
            throw new RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templates->activate($slug);

        $this->redirect('/admin/templates');
    }

    public function logout(
        array $parameters = []
    ): void {
        $this->auth->logout();

        $this->redirect('/admin/login');
    }

    private function redirect(
        string $path
    ): never {
        header('Location: ' . $path);
        exit;
    }
}
PHP

echo "Admin controller created."

echo
echo "===== APPLICATION WIRING ====="

python3 - "$FRONTEND/app/Application.php" <<'PY'
from pathlib import Path
import re
import sys

path = Path(sys.argv[1])
text = path.read_text()

# Imports.
text = text.replace(
    "use App\\Api\\ApiClient;\n",
    "use App\\Admin\\AdminAuth;\n"
    "use App\\Api\\ApiClient;\n"
)

if "use App\\Controllers\\AdminController;" not in text:
    text = text.replace(
        "use App\\Controllers\\ArticleController;\n",
        "use App\\Controllers\\AdminController;\n"
        "use App\\Controllers\\ArticleController;\n"
    )

text = text.replace(
    "use App\\Services\\AppService;\n",
    ""
)

# Remove obsolete AppService property.
text = re.sub(
    r"\n\s*private AppService \$appService;\n",
    "\n",
    text
)

# Remove obsolete AppService initialization.
text = re.sub(
    r"\n\s*\$this->appService = new AppService\(\s*\n\s*\$this->api\s*\n\s*\);\n",
    "\n",
    text
)

# Add admin wiring immediately after router creation.
needle = """        $this->router = new Router();

"""

if needle not in text:
    raise SystemExit(
        "Could not locate Router creation in Application.php."
    )

admin_wiring = """        $this->router = new Router();

        $adminAuth = new AdminAuth(
            $this->api
        );

        $adminController = new AdminController(
            $adminAuth,
            $this->postService,
            $templateManager,
            $templateInstaller,
            $this->api
        );

        $this->router->get(
            '/admin/login',
            [$adminController, 'loginForm']
        );

        $this->router->post(
            '/admin/login',
            [$adminController, 'login']
        );

        $this->router->get(
            '/admin',
            [$adminController, 'dashboard']
        );

        $this->router->get(
            '/admin/templates',
            [$adminController, 'templates']
        );

        $this->router->post(
            '/admin/templates/install/{slug}',
            [$adminController, 'installTemplate']
        );

        $this->router->post(
            '/admin/templates/activate/{slug}',
            [$adminController, 'activateTemplate']
        );

        $this->router->post(
            '/admin/logout',
            [$adminController, 'logout']
        );

"""

text = text.replace(
    needle,
    admin_wiring,
    1
)

path.write_text(text)
PY

echo "Admin routes wired into Application."

echo
echo "===== ADMIN LAYOUT ====="

cat > "$FRONTEND/views/layouts/admin.php" <<'PHP'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars(
        $pageTitle ?? 'Jaroa Admin'
    ) ?></title>

    <link
        rel="stylesheet"
        href="/assets/css/jaroa-admin.css"
    >
</head>

<body class="jaroa-admin-body">

<div class="admin-shell">

    <?= $content ?>

</div>

</body>
</html>
PHP

echo "Admin layout created."

echo
echo "===== ADMIN LOGIN VIEW ====="

cat > "$FRONTEND/views/admin/login.php" <<'PHP'
<?php

$pageTitle = 'Jaroa · Admin Login';

$error = $error ?? null;
$email = $email ?? '';
?>

<section class="admin-login-page">

    <div class="admin-login-panel">

        <div class="admin-login-brand">
            <span class="admin-logo">J</span>

            <div>
                <strong>Jaroa</strong>
                <span>Admin Console</span>
            </div>
        </div>

        <div class="admin-login-intro">

            <p class="admin-kicker">
                ADMINISTRATION
            </p>

            <h1>
                Welcome back.
            </h1>

            <p>
                Sign in to manage your Jaroa application,
                content and starter templates.
            </p>

        </div>

        <?php if ($error !== null): ?>

            <div class="admin-alert admin-alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form
            class="admin-form"
            method="post"
            action="/admin/login"
        >

            <label>
                <span>Email</span>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
                    required
                    autofocus
                >
            </label>

            <label>
                <span>Password</span>

                <input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </label>

            <button
                class="admin-button admin-button-primary"
                type="submit"
            >
                Enter Jaroa
                <span>↗</span>
            </button>

        </form>

        <a
            class="admin-back-link"
            href="/"
        >
            ← Return to application
        </a>

    </div>

    <div class="admin-login-art">
        <div class="admin-orbit admin-orbit-a"></div>
        <div class="admin-orbit admin-orbit-b"></div>
        <div class="admin-orbit admin-orbit-c"></div>

        <div class="admin-orbit-core">
            J
        </div>

        <span class="admin-coordinate">
            JAROA / 01
        </span>
    </div>

</section>
PHP

echo "Admin login view created."

echo
echo "===== ADMIN FORBIDDEN VIEW ====="

cat > "$FRONTEND/views/admin/forbidden.php" <<'PHP'
<?php

$pageTitle = 'Jaroa · Access Denied';
?>

<section class="admin-message-page">

    <div class="admin-message-card">

        <p class="admin-kicker">
            ACCESS CONTROL
        </p>

        <h1>
            Permission required.
        </h1>

        <p>
            <?= htmlspecialchars(
                $message
                ?? 'You do not have access to this area.'
            ) ?>
        </p>

        <a
            class="admin-button admin-button-primary"
            href="/"
        >
            Return to Jaroa
            <span>↗</span>
        </a>

    </div>

</section>
PHP

echo "Forbidden view created."

echo
echo "===== ADMIN DASHBOARD VIEW ====="

cat > "$FRONTEND/views/admin/dashboard.php" <<'PHP'
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
PHP

echo "Dashboard view created."

echo
echo "===== ADMIN TEMPLATES VIEW ====="

cat > "$FRONTEND/views/admin/templates.php" <<'PHP'
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
PHP

echo "Admin template manager view created."

echo
echo "===== ADMIN CSS ====="

cat > "$FRONTEND/public/assets/css/jaroa-admin.css" <<'CSS'
:root {
    --admin-ink: #161616;
    --admin-muted: #737373;
    --admin-border: #dedbd4;
    --admin-soft: #f5f3ee;
    --admin-paper: #fbfaf7;
    --admin-white: #ffffff;
    --admin-accent: #161616;
    --admin-success: #2f7d4a;
    --admin-danger: #a34343;
}

* {
    box-sizing: border-box;
}

body.jaroa-admin-body {
    margin: 0;
    background: var(--admin-paper);
    color: var(--admin-ink);
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

.admin-app {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
}

.admin-sidebar {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 30px 22px;
    background: var(--admin-ink);
    color: var(--admin-white);
    position: sticky;
    top: 0;
    height: 100vh;
}

.admin-sidebar-brand,
.admin-login-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-sidebar-brand strong,
.admin-login-brand strong {
    display: block;
    font-size: 17px;
    letter-spacing: -0.02em;
}

.admin-sidebar-brand span:not(.admin-logo),
.admin-login-brand span:not(.admin-logo) {
    display: block;
    margin-top: 3px;
    font-size: 11px;
    color: #a9a9a9;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.admin-logo {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid currentColor;
    font-size: 18px;
}

.admin-nav {
    display: grid;
    gap: 7px;
    margin-top: 58px;
}

.admin-nav-link {
    color: #c7c7c7;
    text-decoration: none;
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 9px;
    padding: 13px 12px;
    border: 1px solid transparent;
    font-size: 13px;
}

.admin-nav-link:hover,
.admin-nav-link-active {
    color: #fff;
    border-color: #353535;
    background: #202020;
}

.admin-nav-link span {
    color: #777;
    font-size: 10px;
    padding-top: 2px;
}

.admin-nav-link-active span {
    color: #fff;
}

.admin-sidebar-footer {
    margin-top: auto;
    display: grid;
    gap: 16px;
}

.admin-user-mini {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 0;
    border-top: 1px solid #343434;
}

.admin-user-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--admin-success);
}

.admin-user-mini strong,
.admin-user-mini span {
    display: block;
}

.admin-user-mini strong {
    font-size: 12px;
}

.admin-user-mini span {
    margin-top: 3px;
    color: #858585;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.admin-logout,
.admin-quiet-link {
    color: #9d9d9d;
    background: none;
    border: 0;
    padding: 0;
    font: inherit;
    font-size: 11px;
    text-decoration: none;
    cursor: pointer;
}

.admin-logout:hover,
.admin-quiet-link:hover {
    color: #fff;
}

.admin-main {
    min-width: 0;
    padding: 44px clamp(24px, 5vw, 72px) 70px;
}

.admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 30px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--admin-border);
}

.admin-kicker,
.admin-overline {
    margin: 0;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.17em;
    text-transform: uppercase;
    color: var(--admin-muted);
}

.admin-topbar h1 {
    margin: 7px 0 0;
    font-size: clamp(34px, 4vw, 54px);
    line-height: 0.95;
    letter-spacing: -0.05em;
}

.admin-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border: 1px solid var(--admin-border);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    white-space: nowrap;
}

.admin-status > span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--admin-success);
}

.admin-status-offline > span {
    background: var(--admin-danger);
}

.admin-welcome {
    margin-top: 32px;
    padding: 28px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 130px;
    align-items: center;
    gap: 30px;
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
}

.admin-welcome h2 {
    margin: 10px 0 10px;
    max-width: 700px;
    font-size: clamp(28px, 3vw, 44px);
    line-height: 1;
    letter-spacing: -0.05em;
}

.admin-welcome h2 em,
.admin-template-intro h2 em {
    font-style: normal;
    font-weight: 400;
}

.admin-welcome p:last-child,
.admin-template-intro p:last-child {
    max-width: 650px;
    margin: 0;
    color: var(--admin-muted);
    line-height: 1.7;
}

.admin-workspace-mark {
    width: 106px;
    height: 106px;
    margin-left: auto;
    display: grid;
    place-items: center;
    border: 1px solid var(--admin-border);
    font-size: 44px;
}

.admin-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 14px;
}

.admin-stat-card {
    padding: 22px;
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
}

.admin-stat-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    color: var(--admin-muted);
    letter-spacing: 0.14em;
}

.admin-stat-card strong {
    display: block;
    margin-top: 10px;
    font-size: 43px;
    line-height: 1;
    letter-spacing: -0.05em;
}

.admin-stat-card .admin-stat-word {
    font-size: 27px;
    padding-top: 7px;
}

.admin-stat-note {
    display: block;
    margin-top: 9px;
    color: var(--admin-muted);
    font-size: 11px;
}

.admin-content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.8fr);
    gap: 14px;
    margin-top: 14px;
}

.admin-panel {
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
    padding: 24px;
}

.admin-panel-accent {
    background: var(--admin-ink);
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.admin-panel-accent .admin-overline,
.admin-panel-accent p {
    color: #999;
}

.admin-panel-accent h2 {
    margin: 10px 0 8px;
    font-size: 34px;
    letter-spacing: -0.04em;
}

.admin-panel-accent .admin-button {
    margin-top: auto;
}

.admin-panel-heading {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: end;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--admin-border);
}

.admin-panel-heading h2 {
    margin: 7px 0 0;
    font-size: 23px;
    letter-spacing: -0.03em;
}

.admin-panel-heading > span {
    color: var(--admin-muted);
    font-size: 11px;
}

.admin-list-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 15px 0;
    border-bottom: 1px solid #ece9e2;
    color: inherit;
    text-decoration: none;
}

.admin-list-row:last-child {
    border-bottom: 0;
}

.admin-list-row:hover .admin-list-arrow {
    transform: translateX(3px);
}

.admin-list-number {
    color: var(--admin-muted);
    font-size: 10px;
}

.admin-list-copy strong,
.admin-list-copy span {
    display: block;
}

.admin-list-copy strong {
    font-size: 13px;
}

.admin-list-copy span {
    margin-top: 5px;
    color: var(--admin-muted);
    font-size: 10px;
}

.admin-list-arrow {
    transition: transform 0.2s ease;
}

.admin-empty {
    padding: 32px 0;
    color: var(--admin-muted);
    font-size: 13px;
}

.admin-foundation {
    margin-top: 14px;
    padding: 28px;
    border: 1px solid var(--admin-border);
    background: var(--admin-soft);
}

.admin-foundation-heading h2 {
    margin: 9px 0 0;
    max-width: 700px;
    font-size: 31px;
    line-height: 1;
    letter-spacing: -0.04em;
}

.admin-foundation-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-top: 30px;
}

.admin-foundation-grid article {
    padding-top: 16px;
    border-top: 1px solid var(--admin-border);
}

.admin-foundation-grid article > span {
    color: var(--admin-muted);
    font-size: 10px;
}

.admin-foundation-grid strong {
    display: block;
    margin-top: 13px;
    font-size: 18px;
}

.admin-foundation-grid p {
    margin: 8px 0 0;
    color: var(--admin-muted);
    line-height: 1.6;
    font-size: 12px;
}

.admin-button {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 10px 15px;
    border: 1px solid var(--admin-ink);
    background: transparent;
    color: var(--admin-ink);
    font: inherit;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
}

.admin-button:hover {
    background: var(--admin-ink);
    color: #fff;
}

.admin-button-primary {
    background: var(--admin-ink);
    color: #fff;
}

.admin-button-primary:hover {
    background: #2b2b2b;
}

.admin-template-intro {
    margin-top: 32px;
    max-width: 840px;
}

.admin-template-intro h2 {
    margin: 10px 0;
    font-size: clamp(31px, 4vw, 51px);
    line-height: 0.97;
    letter-spacing: -0.05em;
}

.admin-template-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 30px;
}

.admin-template-card {
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
}

.admin-template-card-active {
    border-color: var(--admin-ink);
}

.admin-template-art {
    min-height: 170px;
    padding: 17px;
    position: relative;
    overflow: hidden;
    background: var(--admin-soft);
}

.admin-template-art::after,
.admin-template-art::before {
    content: "";
    position: absolute;
    border: 1px solid #cfcbc1;
    border-radius: 50%;
}

.admin-template-art::after {
    width: 240px;
    height: 240px;
    right: -60px;
    top: -100px;
}

.admin-template-art::before {
    width: 150px;
    height: 150px;
    left: 10%;
    bottom: -85px;
}

.admin-template-art span {
    position: relative;
    z-index: 2;
    font-size: 10px;
    color: var(--admin-muted);
}

.admin-template-art strong {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-size: 75px;
    line-height: 1;
    font-weight: 400;
}

.admin-template-body {
    padding: 21px;
}

.admin-template-status {
    min-height: 20px;
}

.admin-template-badge {
    display: inline-block;
    padding: 4px 7px;
    border: 1px solid var(--admin-border);
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.admin-template-badge-active {
    border-color: var(--admin-ink);
}

.admin-template-body h3 {
    margin: 16px 0 8px;
    font-size: 25px;
    letter-spacing: -0.04em;
}

.admin-template-body p {
    min-height: 42px;
    margin: 0;
    color: var(--admin-muted);
    font-size: 12px;
    line-height: 1.6;
}

.admin-template-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 20px;
}

.admin-template-actions form {
    margin: 0;
}

.admin-message-page,
.admin-login-page {
    min-height: 100vh;
}

.admin-message-page {
    display: grid;
    place-items: center;
    padding: 30px;
}

.admin-message-card {
    width: min(560px, 100%);
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
    padding: 42px;
}

.admin-message-card h1 {
    margin: 10px 0;
    font-size: 45px;
    line-height: 0.95;
    letter-spacing: -0.05em;
}

.admin-message-card p:not(.admin-kicker) {
    color: var(--admin-muted);
    line-height: 1.7;
    margin-bottom: 26px;
}

.admin-login-page {
    display: grid;
    grid-template-columns: minmax(360px, 0.85fr) minmax(0, 1.15fr);
}

.admin-login-panel {
    padding: clamp(28px, 6vw, 80px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: var(--admin-paper);
}

.admin-login-brand {
    align-self: flex-start;
}

.admin-login-intro {
    margin-top: 70px;
    max-width: 500px;
}

.admin-login-intro h1 {
    margin: 11px 0;
    font-size: clamp(43px, 5vw, 70px);
    line-height: 0.92;
    letter-spacing: -0.06em;
}

.admin-login-intro p:last-child {
    color: var(--admin-muted);
    line-height: 1.7;
    max-width: 450px;
}

.admin-form {
    display: grid;
    gap: 18px;
    margin-top: 33px;
    max-width: 500px;
}

.admin-form label {
    display: grid;
    gap: 8px;
}

.admin-form label span {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--admin-muted);
}

.admin-form input {
    width: 100%;
    min-height: 49px;
    padding: 12px 14px;
    border: 1px solid var(--admin-border);
    background: var(--admin-white);
    color: var(--admin-ink);
    font: inherit;
    outline: none;
}

.admin-form input:focus {
    border-color: var(--admin-ink);
}

.admin-alert {
    max-width: 500px;
    margin-top: 20px;
    padding: 12px 14px;
    font-size: 12px;
}

.admin-alert-error {
    border: 1px solid #d6b4b4;
    background: #fcf2f2;
    color: var(--admin-danger);
}

.admin-back-link {
    display: inline-block;
    margin-top: 22px;
    color: var(--admin-muted);
    text-decoration: none;
    font-size: 11px;
}

.admin-back-link:hover {
    color: var(--admin-ink);
}

.admin-login-art {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    display: grid;
    place-items: center;
    background: #171717;
    color: #fff;
}

.admin-orbit {
    position: absolute;
    border: 1px solid #444;
    border-radius: 50%;
}

.admin-orbit-a {
    width: 280px;
    height: 280px;
}

.admin-orbit-b {
    width: 500px;
    height: 500px;
    transform: rotate(35deg);
}

.admin-orbit-c {
    width: 720px;
    height: 720px;
    transform: rotate(-27deg);
}

.admin-orbit-core {
    width: 130px;
    height: 130px;
    display: grid;
    place-items: center;
    border: 1px solid #777;
    font-size: 54px;
    z-index: 2;
}

.admin-coordinate {
    position: absolute;
    right: 32px;
    bottom: 28px;
    color: #777;
    font-size: 10px;
    letter-spacing: 0.17em;
}

@media (max-width: 1000px) {
    .admin-app {
        grid-template-columns: 205px minmax(0, 1fr);
    }

    .admin-content-grid {
        grid-template-columns: 1fr;
    }

    .admin-template-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .admin-app {
        display: block;
    }

    .admin-sidebar {
        min-height: auto;
        height: auto;
        position: static;
        padding: 18px;
    }

    .admin-nav {
        margin-top: 25px;
    }

    .admin-sidebar-footer {
        margin-top: 25px;
    }

    .admin-main {
        padding: 28px 18px 50px;
    }

    .admin-stat-grid,
    .admin-foundation-grid {
        grid-template-columns: 1fr;
    }

    .admin-welcome {
        grid-template-columns: 1fr;
    }

    .admin-workspace-mark {
        margin-left: 0;
    }

    .admin-topbar {
        flex-direction: column;
    }

    .admin-login-page {
        grid-template-columns: 1fr;
    }

    .admin-login-art {
        display: none;
    }

    .admin-login-intro {
        margin-top: 45px;
    }
}
CSS

echo "Admin stylesheet created."

echo
echo "===== POST-BUILD SYNTAX CHECK ====="

cd "$FRONTEND"

ddev exec php -l app/Api/ApiClient.php
ddev exec php -l app/Admin/AdminAuth.php
ddev exec php -l app/Controllers/AdminController.php
ddev exec php -l app/Application.php
ddev exec php -l views/admin/login.php
ddev exec php -l views/admin/dashboard.php
ddev exec php -l views/admin/templates.php
ddev exec php -l views/admin/forbidden.php
ddev exec php -l views/layouts/admin.php

echo
echo "===== ROUTE CHECK ====="

LOGIN_CODE="$(
    curl -ksS -o /dev/null -w "%{http_code}" \
    https://frontend.ddev.site/admin/login
)"

ADMIN_CODE="$(
    curl -ksS -o /dev/null -w "%{http_code}" \
    https://frontend.ddev.site/admin
)"

TEMPLATES_CODE="$(
    curl -ksS -o /dev/null -w "%{http_code}" \
    https://frontend.ddev.site/admin/templates
)"

echo "GET /admin/login      -> HTTP $LOGIN_CODE"
echo "GET /admin            -> HTTP $ADMIN_CODE"
echo "GET /admin/templates  -> HTTP $TEMPLATES_CODE"

if [ "$LOGIN_CODE" != "200" ]; then
    echo "ERROR: Admin login page did not return HTTP 200."
    exit 1
fi

if [ "$ADMIN_CODE" != "302" ]; then
    echo "WARNING: Expected /admin to redirect to login (302)."
fi

if [ "$TEMPLATES_CODE" != "302" ]; then
    echo "WARNING: Expected /admin/templates to redirect to login (302)."
fi

echo
echo "============================================================"
echo " ✅ JAROA ADMIN DASHBOARD v0.1 CREATED"
echo "============================================================"
echo
echo "Admin login:"
echo "https://frontend.ddev.site/admin/login"
echo
echo "Admin dashboard:"
echo "https://frontend.ddev.site/admin"
echo
echo "Template control:"
echo "https://frontend.ddev.site/admin/templates"
echo
echo "Build log:"
echo "$LOG_FILE"
echo
echo "Backup:"
echo "$BACKUP_DIR"
echo
