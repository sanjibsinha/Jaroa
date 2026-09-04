#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${PROJECT_ROOT}"

echo "========================================"
echo " JAROA AUTHORIZATION HAMMER"
echo "========================================"
echo
echo "Working directory:"
pwd
echo

# ============================================================
# 17.00 Environment
# ============================================================

if ! command -v ddev >/dev/null 2>&1; then
    echo "ERROR: ddev is required."
    exit 1
fi

if [ ! -d ".ddev" ]; then
    echo "ERROR: .ddev directory not found."
    exit 1
fi

echo "DDEV project detected."

# ============================================================
# 17.01 Create authorization migration
# ============================================================

echo
echo "Creating authorization migration..."

cat > database/migrations/202609030003_add_role_to_users_table.php <<'PHP'
<?php

declare(strict_types=1);

use Jaroa\Database\Migration;

return new class implements Migration {
    public function up(\PDO $pdo): void
    {
        $statement = $pdo->query(
            <<<'SQL'
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role'
SQL
        );

        $exists = (int) $statement->fetchColumn() > 0;

        if (!$exists) {
            $pdo->exec(
                <<<'SQL'
ALTER TABLE users
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'
AFTER email
SQL
            );
        }

        $pdo->exec(
            <<<'SQL'
UPDATE users
SET role = 'admin'
WHERE id = 1
  AND name = 'Jaroa Admin'
SQL
        );
    }

    public function down(\PDO $pdo): void
    {
        $statement = $pdo->query(
            <<<'SQL'
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role'
SQL
        );

        $exists = (int) $statement->fetchColumn() > 0;

        if ($exists) {
            $pdo->exec(
                'ALTER TABLE users DROP COLUMN role'
            );
        }
    }
};
PHP

echo "Authorization migration created."

# ============================================================
# 17.02 User model
# ============================================================

echo
echo "Updating User model..."

cat > app/Models/User.php <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Models;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $createdAt,
        public string $updatedAt,
        public string $role = 'user',
    ) {
    }

    public function publicData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
PHP

echo "User model updated."

# ============================================================
# 17.03 User repository
# ============================================================

echo
echo "Updating UserRepository..."

cat > app/Repositories/UserRepository.php <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Repositories;

use Jaroa\Models\User;
use PDO;
use RuntimeException;

final class UserRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    name,
    email,
    password_hash,
    role,
    created_at,
    updated_at
FROM users
WHERE id = :id
LIMIT 1
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->map($row);
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT
    id,
    name,
    email,
    password_hash,
    role,
    created_at,
    updated_at
FROM users
WHERE email = :email
LIMIT 1
SQL
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->map($row);
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role = 'user'
    ): User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
INSERT INTO users (
    name,
    email,
    password_hash,
    role
)
VALUES (
    :name,
    :email,
    :password_hash,
    :role
)
SQL
        );

        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        $user = $this->findById($id);

        if ($user === null) {
            throw new RuntimeException(
                'Created user could not be retrieved.'
            );
        }

        return $user;
    }

    public function setRole(
        int $id,
        string $role
    ): ?User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET role = :role
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'role' => $role,
        ]);

        return $this->findById($id);
    }

    private function map(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
            (string) ($row['role'] ?? 'user'),
        );
    }
}
PHP

echo "UserRepository updated."

# ============================================================
# 17.04 Authentication middleware
# ============================================================

echo
echo "Creating AuthenticationMiddleware..."

cat > app/Middleware/AuthenticationMiddleware.php <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Services\AuthService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;

final class AuthenticationMiddleware
{
    public function __construct(
        private readonly AuthService $auth
    ) {
    }

    public function authenticate(
        Request $request
    ): AuthenticatedUser|JsonResponse {
        $token = $request->bearerToken();

        if ($token === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        $authenticated = $this->auth->authenticate(
            $token
        );

        if ($authenticated === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        return $authenticated;
    }
}
PHP

echo "AuthenticationMiddleware created."

# ============================================================
# 17.05 Authorization middleware
# ============================================================

echo
echo "Creating AuthorizationMiddleware..."

cat > app/Middleware/AuthorizationMiddleware.php <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Middleware;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Support\JsonResponse;

final class AuthorizationMiddleware
{
    public function authorize(
        AuthenticatedUser $authenticated,
        array $allowedRoles
    ): ?JsonResponse {
        if (
            !in_array(
                $authenticated->user->role,
                $allowedRoles,
                true
            )
        ) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'You are not authorized to perform this action.',
                    ],
                ],
                403
            );
        }

        return null;
    }
}
PHP

echo "AuthorizationMiddleware created."

# ============================================================
# 17.06 ControllerProvider
# ============================================================

echo
echo "Updating ControllerProvider..."

python3 - <<'PY'
from pathlib import Path

path = Path("app/Providers/ControllerProvider.php")
text = path.read_text()

required_imports = [
    "use Jaroa\\Middleware\\AuthenticationMiddleware;",
    "use Jaroa\\Middleware\\AuthorizationMiddleware;",
]

namespace_line = "namespace Jaroa\\Providers;\n"

if namespace_line not in text:
    raise SystemExit(
        "ControllerProvider namespace not found."
    )

missing = [
    item
    for item in required_imports
    if item not in text
]

if missing:
    text = text.replace(
        namespace_line,
        namespace_line + "\n" + "\n".join(missing) + "\n",
        1
    )

if "public function authenticationMiddleware():" not in text:

    class_end = "\n}\n"

    if not text.endswith(class_end):
        raise SystemExit(
            "ControllerProvider class ending not found."
        )

    methods = """
    public function authenticationMiddleware(): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            new AuthService(
                new UserRepository(
                    $this->pdo
                ),
                new AuthTokenRepository(
                    $this->pdo
                ),
                new TokenManager()
            )
        );
    }

    public function authorizationMiddleware(): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware();
    }
"""

    text = text[:-len(class_end)] + methods + class_end

path.write_text(text)
PY

echo "ControllerProvider updated."

# ============================================================
# 17.07 API routes
# ============================================================

echo
echo "Updating API authorization routes..."

cat > routes/api.php <<'PHP'
<?php

declare(strict_types=1);

use Jaroa\Application;
use Jaroa\Controllers\StatusController;
use Jaroa\Providers\ControllerProvider;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;
use Jaroa\Support\Router;

return function (
    Router $router,
    Application $application
): void {
    $statusController = new StatusController();

    $router->get(
        '/api/v1/status',
        [$statusController, 'index']
    );

    $controllerProvider = new ControllerProvider(
        $application->database()->connection()
    );

    $postsController = $controllerProvider->posts();

    $authController = $controllerProvider->auth();

    $authenticationMiddleware =
        $controllerProvider->authenticationMiddleware();

    $authorizationMiddleware =
        $controllerProvider->authorizationMiddleware();

    /*
     * Public authentication endpoints.
     */

    $router->post(
        '/api/v1/auth/register',
        static function () use ($authController): mixed {
            return $authController->register(
                new Request()
            );
        }
    );

    $router->post(
        '/api/v1/auth/login',
        static function () use ($authController): mixed {
            return $authController->login(
                new Request()
            );
        }
    );

    $router->get(
        '/api/v1/auth/me',
        static function () use ($authController): mixed {
            return $authController->me(
                new Request()
            );
        }
    );

    $router->post(
        '/api/v1/auth/logout',
        static function () use ($authController): mixed {
            return $authController->logout(
                new Request()
            );
        }
    );

    /*
     * Public post endpoints.
     */

    $router->get(
        '/api/v1/posts',
        [$postsController, 'index']
    );

    $router->get(
        '/api/v1/posts/{id}',
        [$postsController, 'show']
    );

    /*
     * Editor/admin post creation.
     */

    $router->post(
        '/api/v1/posts',
        static function () use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->store(
                $request
            );
        }
    );

    /*
     * Editor/admin post update.
     */

    $router->put(
        '/api/v1/posts/{id}',
        static function (array $parameters) use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->update(
                (int) $parameters['id'],
                $request
            );
        }
    );

    /*
     * Editor/admin post deletion.
     */

    $router->delete(
        '/api/v1/posts/{id}',
        static function (array $parameters) use (
            $postsController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorizationMiddleware->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $postsController->delete(
                (int) $parameters['id']
            );
        }
    );
};
PHP

echo "API authorization routes installed."

# ============================================================
# 17.08 Composer
# ============================================================

echo
echo "Running Composer autoload generation..."

ddev exec composer dump-autoload

# ============================================================
# 17.09 Migration
# ============================================================

echo
echo "Running migrations..."

ddev exec php database/migrate.php

# ============================================================
# 17.10 Verify roles
# ============================================================

echo
echo "========================================"
echo " AUTHORIZATION DATABASE VERIFICATION"
echo "========================================"

echo
echo "Checking users.role..."

ddev exec mysql \
    -h db \
    -u db \
    -pdb \
    db \
    -N \
    -e "SHOW COLUMNS FROM users LIKE 'role';" \
    | grep -q '^role'

echo "users.role exists."

echo
echo "Checking administrator role..."

ADMIN_ROLE="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT role FROM users WHERE id=1 LIMIT 1;"
)"

if [ "${ADMIN_ROLE}" != "admin" ]; then
    echo "ERROR: Jaroa Admin does not have admin role."
    exit 1
fi

echo "Jaroa Admin role verified."

# ============================================================
# 17.11 Create test users
# ============================================================

echo
echo "========================================"
echo " AUTHORIZATION API TESTS"
echo "========================================"

TIMESTAMP="$(date +%Y%m%d%H%M%S)"

USER_EMAIL="auth-user-${TIMESTAMP}@jaroa.local"
EDITOR_EMAIL="auth-editor-${TIMESTAMP}@jaroa.local"
ADMIN_EMAIL="auth-admin-${TIMESTAMP}@jaroa.local"

PASSWORD="authorization-password-${TIMESTAMP}"

echo
echo "Creating normal user..."

USER_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"Authorization User\",\"email\":\"${USER_EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${USER_RESPONSE}"

echo "${USER_RESPONSE}" | grep -q '"role":"user"'

echo "Normal user created."

echo
echo "Creating editor user..."

EDITOR_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"Authorization Editor\",\"email\":\"${EDITOR_EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${EDITOR_RESPONSE}"

EDITOR_ID="$(
    printf '%s' "${EDITOR_RESPONSE}" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["id"])
'
)"

if [ -z "${EDITOR_ID}" ]; then
    echo "ERROR: Editor ID could not be determined."
    exit 1
fi

echo "Editor created with ID ${EDITOR_ID}."

echo
echo "Assigning editor role..."

ddev exec mysql \
    -h db \
    -u db \
    -pdb \
    db \
    -e "UPDATE users SET role='editor' WHERE id=${EDITOR_ID};"

EDITOR_ROLE="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT role FROM users WHERE id=${EDITOR_ID};"
)"

if [ "${EDITOR_ROLE}" != "editor" ]; then
    echo "ERROR: Editor role was not assigned."
    exit 1
fi

echo "Editor role assigned."

echo
echo "Creating second administrator..."

ADMIN_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"Authorization Admin\",\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${ADMIN_RESPONSE}"

ADMIN_ID="$(
    printf '%s' "${ADMIN_RESPONSE}" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["id"])
'
)"

if [ -z "${ADMIN_ID}" ]; then
    echo "ERROR: Admin ID could not be determined."
    exit 1
fi

ddev exec mysql \
    -h db \
    -u db \
    -pdb \
    db \
    -e "UPDATE users SET role='admin' WHERE id=${ADMIN_ID};"

echo "Second administrator created."

# ============================================================
# 17.12 Login helper
# ============================================================

login_user() {
    local email="$1"

    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${email}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/login"
}

extract_token() {
    printf '%s' "$1" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["token"])
'
}

echo
echo "Logging in normal user..."

USER_LOGIN="$(login_user "${USER_EMAIL}")"
USER_TOKEN="$(extract_token "${USER_LOGIN}")"

if [ -z "${USER_TOKEN}" ]; then
    echo "ERROR: Normal user login failed."
    exit 1
fi

echo "Normal user authenticated."

echo
echo "Logging in editor..."

EDITOR_LOGIN="$(login_user "${EDITOR_EMAIL}")"
EDITOR_TOKEN="$(extract_token "${EDITOR_LOGIN}")"

if [ -z "${EDITOR_TOKEN}" ]; then
    echo "ERROR: Editor login failed."
    exit 1
fi

echo "Editor authenticated."

echo
echo "Logging in administrator..."

ADMIN_LOGIN="$(login_user "${ADMIN_EMAIL}")"
ADMIN_TOKEN="$(extract_token "${ADMIN_LOGIN}")"

if [ -z "${ADMIN_TOKEN}" ]; then
    echo "ERROR: Administrator login failed."
    exit 1
fi

echo "Administrator authenticated."

# ============================================================
# 17.13 /me role verification
# ============================================================

echo
echo "17.13.1 Normal user /me"

USER_ME="$(
    curl -sk \
        -H "Authorization: Bearer ${USER_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${USER_ME}"

echo "${USER_ME}" | grep -q '"role":"user"'

echo "Normal user role verified."

echo
echo "17.13.2 Editor /me"

EDITOR_ME="$(
    curl -sk \
        -H "Authorization: Bearer ${EDITOR_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${EDITOR_ME}"

echo "${EDITOR_ME}" | grep -q '"role":"editor"'

echo "Editor role verified."

echo
echo "17.13.3 Admin /me"

ADMIN_ME="$(
    curl -sk \
        -H "Authorization: Bearer ${ADMIN_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${ADMIN_ME}"

echo "${ADMIN_ME}" | grep -q '"role":"admin"'

echo "Admin role verified."

# ============================================================
# 17.14 Public GET access
# ============================================================

echo
echo "17.14 Public posts access without authentication"

PUBLIC_POSTS="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${PUBLIC_POSTS}"

echo "${PUBLIC_POSTS}" | grep -q '"data"'

echo "Public posts access passed."

# ============================================================
# 17.15 Normal user forbidden
# ============================================================

echo
echo "17.15 Normal user POST must return 403"

USER_POST_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${USER_TOKEN}" \
        -d '{"user_id":1,"title":"Unauthorized Post","slug":"unauthorized-post","content":"Should not be created."}' \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${USER_POST_RESPONSE}"

echo "${USER_POST_RESPONSE}" | grep -q '"forbidden"'

echo "Normal user correctly forbidden."

# ============================================================
# 17.16 Unauthenticated mutation
# ============================================================

echo
echo "17.16 Unauthenticated POST must return 401"

NO_AUTH_POST_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"user_id":1,"title":"Unauthenticated Post","slug":"unauthenticated-post","content":"Should not be created."}' \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${NO_AUTH_POST_RESPONSE}"

echo "${NO_AUTH_POST_RESPONSE}" | grep -q '"unauthenticated"'

echo "Unauthenticated mutation correctly rejected."

# ============================================================
# 17.17 Invalid token
# ============================================================

echo
echo "17.17 Invalid token POST must return 401"

INVALID_TOKEN_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer invalid-authorization-token" \
        -d '{"user_id":1,"title":"Invalid Token Post","slug":"invalid-token-post","content":"Should not be created."}' \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${INVALID_TOKEN_RESPONSE}"

echo "${INVALID_TOKEN_RESPONSE}" | grep -q '"unauthenticated"'

echo "Invalid token correctly rejected."

# ============================================================
# 17.18 Editor create
# ============================================================

echo
echo "17.18 Editor POST must succeed"

EDITOR_SLUG="authorization-editor-${TIMESTAMP}"

EDITOR_POST_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${EDITOR_TOKEN}" \
        -d "{\"user_id\":${EDITOR_ID},\"title\":\"Authorization Editor Post\",\"slug\":\"${EDITOR_SLUG}\",\"content\":\"Created by the authorization hammer.\"}" \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${EDITOR_POST_RESPONSE}"

EDITOR_POST_ID="$(
    printf '%s' "${EDITOR_POST_RESPONSE}" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["id"])
'
)"

if [ -z "${EDITOR_POST_ID}" ]; then
    echo "ERROR: Editor post ID could not be determined."
    exit 1
fi

echo "Editor successfully created post ${EDITOR_POST_ID}."

# ============================================================
# 17.19 Normal user cannot update
# ============================================================

echo
echo "17.19 Normal user PUT must return 403"

USER_UPDATE_RESPONSE="$(
    curl -sk \
        -X PUT \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${USER_TOKEN}" \
        -d '{"title":"Unauthorized Update","slug":"unauthorized-update","content":"Should not update."}' \
        "https://jaroa-engine.ddev.site/api/v1/posts/${EDITOR_POST_ID}"
)"

echo "${USER_UPDATE_RESPONSE}"

echo "${USER_UPDATE_RESPONSE}" | grep -q '"forbidden"'

echo "Normal user correctly forbidden from update."

# ============================================================
# 17.20 Editor update
# ============================================================

echo
echo "17.20 Editor PUT must succeed"

EDITOR_UPDATE_RESPONSE="$(
    curl -sk \
        -X PUT \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${EDITOR_TOKEN}" \
        -d "{\"title\":\"Authorization Editor Updated\",\"slug\":\"authorization-editor-updated-${TIMESTAMP}\",\"content\":\"Updated by the editor.\"}" \
        "https://jaroa-engine.ddev.site/api/v1/posts/${EDITOR_POST_ID}"
)"

echo "${EDITOR_UPDATE_RESPONSE}"

echo "${EDITOR_UPDATE_RESPONSE}" |
    grep -q '"title":"Authorization Editor Updated"'

echo "Editor update passed."

# ============================================================
# 17.21 Normal user cannot delete
# ============================================================

echo
echo "17.21 Normal user DELETE must return 403"

USER_DELETE_RESPONSE="$(
    curl -sk \
        -X DELETE \
        -H "Authorization: Bearer ${USER_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/posts/${EDITOR_POST_ID}"
)"

echo "${USER_DELETE_RESPONSE}"

echo "${USER_DELETE_RESPONSE}" | grep -q '"forbidden"'

echo "Normal user correctly forbidden from delete."

# ============================================================
# 17.22 Editor delete
# ============================================================

echo
echo "17.22 Editor DELETE must succeed"

EDITOR_DELETE_RESPONSE="$(
    curl -sk \
        -X DELETE \
        -H "Authorization: Bearer ${EDITOR_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/posts/${EDITOR_POST_ID}"
)"

echo "${EDITOR_DELETE_RESPONSE}"

echo "${EDITOR_DELETE_RESPONSE}" | grep -q '"deleted":true'

echo "Editor delete passed."

# ============================================================
# 17.23 Administrator create
# ============================================================

echo
echo "17.23 Administrator POST must succeed"

ADMIN_SLUG="authorization-admin-${TIMESTAMP}"

ADMIN_POST_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${ADMIN_TOKEN}" \
        -d "{\"user_id\":${ADMIN_ID},\"title\":\"Authorization Admin Post\",\"slug\":\"${ADMIN_SLUG}\",\"content\":\"Created by the administrator.\"}" \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${ADMIN_POST_RESPONSE}"

ADMIN_POST_ID="$(
    printf '%s' "${ADMIN_POST_RESPONSE}" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["id"])
'
)"

if [ -z "${ADMIN_POST_ID}" ]; then
    echo "ERROR: Admin post ID could not be determined."
    exit 1
fi

echo "Administrator successfully created post ${ADMIN_POST_ID}."

# ============================================================
# 17.24 Administrator delete
# ============================================================

echo
echo "17.24 Administrator DELETE must succeed"

ADMIN_DELETE_RESPONSE="$(
    curl -sk \
        -X DELETE \
        -H "Authorization: Bearer ${ADMIN_TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/posts/${ADMIN_POST_ID}"
)"

echo "${ADMIN_DELETE_RESPONSE}"

echo "${ADMIN_DELETE_RESPONSE}" | grep -q '"deleted":true'

echo "Administrator delete passed."

# ============================================================
# 17.25 Ensure forbidden operations did not create data
# ============================================================

echo
echo "17.25 Authorization isolation"

UNAUTHORIZED_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "
SELECT COUNT(*)
FROM posts
WHERE slug IN (
    'unauthorized-post',
    'unauthorized-update',
    'unauthenticated-post',
    'invalid-token-post'
);
"
)"

if [ "${UNAUTHORIZED_COUNT}" != "0" ]; then
    echo "ERROR: Unauthorized requests created or modified data."
    exit 1
fi

echo "Authorization isolation passed."

# ============================================================
# 17.26 Previous regression suite
# ============================================================

echo
echo "========================================"
echo " PREVIOUS REGRESSION SUITE"
echo "========================================"

for test in \
    tests/database-test.php \
    tests/exception-handler-test.php \
    tests/json-response-test.php \
    tests/migration-test.php \
    tests/post-repository-create-test.php \
    tests/post-repository-delete-test.php \
    tests/post-repository-find-test.php \
    tests/post-repository-test.php \
    tests/post-repository-update-test.php \
    tests/post-service-create-test.php \
    tests/post-service-delete-test.php \
    tests/post-service-find-test.php \
    tests/post-service-test.php \
    tests/post-service-update-test.php \
    tests/posts-controller-delete-test.php \
    tests/posts-controller-show-test.php \
    tests/posts-controller-store-test.php \
    tests/posts-controller-test.php \
    tests/posts-controller-update-test.php \
    tests/request-test.php \
    tests/router-test.php \
    tests/routes-test.php \
    tests/status-controller-test.php
do
    echo
    echo "Running ${test}..."
    ddev exec php "${test}"
done

echo
echo "Previous regression suite passed."

# ============================================================
# 17.27 Real HTTP status/posts regression
# ============================================================

echo
echo "========================================"
echo " REAL HTTP REGRESSION"
echo "========================================"

echo
echo "17.27.1 Status endpoint"

STATUS_RESPONSE="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/status"
)"

echo "${STATUS_RESPONSE}"

echo "${STATUS_RESPONSE}" | grep -q '"status":"ok"'

echo "HTTP status endpoint verified."

echo
echo "17.27.2 Public posts endpoint"

POSTS_RESPONSE="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${POSTS_RESPONSE}"

echo "${POSTS_RESPONSE}" | grep -q '"data"'

echo "HTTP posts endpoint verified."

# ============================================================
# 17.28 Final syntax
# ============================================================

echo
echo "========================================"
echo " FINAL SYNTAX VERIFICATION"
echo "========================================"

PHP_FAILURES=0

while IFS= read -r file; do

    if ! ddev exec php -l "${file}" >/dev/null; then
        echo "SYNTAX ERROR: ${file}"
        PHP_FAILURES=$((PHP_FAILURES + 1))
    fi

done < <(
    find app bootstrap config database public routes tests \
        -type f \
        -name '*.php' \
        -print
)

if [ "${PHP_FAILURES}" -ne 0 ]; then
    echo
    echo "ERROR: ${PHP_FAILURES} PHP file(s) failed syntax verification."
    exit 1
fi

echo "All PHP files passed syntax verification."

# ============================================================
# 17.29 Composer autoload
# ============================================================

echo
echo "Running Composer autoload verification..."

ddev exec php <<'PHP'
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$classes = [
    'Jaroa\\Application',
    'Jaroa\\Support\\Router',
    'Jaroa\\Support\\Request',
    'Jaroa\\Models\\User',
    'Jaroa\\Repositories\\UserRepository',
    'Jaroa\\Repositories\\AuthTokenRepository',
    'Jaroa\\Auth\\AuthenticatedUser',
    'Jaroa\\Auth\\TokenManager',
    'Jaroa\\Services\\AuthService',
    'Jaroa\\Controllers\\AuthController',
    'Jaroa\\Middleware\\AuthenticationMiddleware',
    'Jaroa\\Middleware\\AuthorizationMiddleware',
];

foreach ($classes as $class) {

    if (!class_exists($class)) {
        throw new RuntimeException(
            "{$class} could not be autoloaded."
        );
    }
}

echo "Authorization autoload verification passed." . PHP_EOL;
PHP

# ============================================================
# 17.30 Final authorization verification
# ============================================================

echo
echo "========================================"
echo " FINAL AUTHORIZATION VERIFICATION"
echo "========================================"

echo
echo "Checking role support..."

grep -q "public string \$role" app/Models/User.php
grep -q "role" app/Repositories/UserRepository.php

echo "Role support verified."

echo
echo "Checking authentication middleware..."

grep -q "class AuthenticationMiddleware" \
    app/Middleware/AuthenticationMiddleware.php

echo "Authentication middleware verified."

echo
echo "Checking authorization middleware..."

grep -q "class AuthorizationMiddleware" \
    app/Middleware/AuthorizationMiddleware.php

echo "Authorization middleware verified."

echo
echo "Checking protected post routes..."

grep -q "authenticationMiddleware" routes/api.php
grep -q "authorizationMiddleware" routes/api.php
grep -q "'editor', 'admin'" routes/api.php

echo "Protected post routes verified."

echo
echo "========================================"
echo " JAROA AUTHORIZATION HAMMER PASSED"
echo "========================================"
echo
echo "Authorization subsystem: COMPLETE"
echo
