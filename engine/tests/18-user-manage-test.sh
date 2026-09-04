#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BASE_URL="https://jaroa-engine.ddev.site"
CONTAINER_ROOT="/var/www/html"

TEMP_PHP_HOST="$ROOT/.jaroa-user-manage-temp.php"
TEMP_PHP_CONTAINER="$CONTAINER_ROOT/.jaroa-user-manage-temp.php"

RESPONSE_FILE="/tmp/jaroa-user-manage-response.json"

ADMIN_EMAIL="admin@jaroa.local"
ADMIN_PASSWORD="AdminPass123!"

USER_EMAIL="user-manage-user@jaroa.local"
USER_PASSWORD="UserPass123!"
NEW_USER_PASSWORD="NewUserPass123!"

EDITOR_EMAIL="user-manage-editor@jaroa.local"
EDITOR_PASSWORD="EditorPass123!"

ADMIN_TEST_EMAIL="user-manage-admin@jaroa.local"
ADMIN_TEST_PASSWORD="AdminPass123!"

fail() {
    echo
    echo "ERROR: $1"
    echo
    exit 1
}

cleanup() {
    rm -f "$TEMP_PHP_HOST"
}

trap cleanup EXIT

json_field() {
    local json="$1"
    local field="$2"

    printf '%s' "$json" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
value = data

for key in sys.argv[1].split("."):
    value = value[key]

if isinstance(value, bool):
    print("true" if value else "false")
else:
    print(value)
' "$field"
}

http_status() {
    local method="$1"
    local url="$2"
    local token="${3:-}"
    local body="${4:-}"

    if [[ -n "$token" && -n "$body" ]]; then
        curl -ksS \
            -o "$RESPONSE_FILE" \
            -w "%{http_code}" \
            -X "$method" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            -d "$body" \
            "$url"

    elif [[ -n "$token" ]]; then
        curl -ksS \
            -o "$RESPONSE_FILE" \
            -w "%{http_code}" \
            -X "$method" \
            -H "Authorization: Bearer $token" \
            "$url"

    elif [[ -n "$body" ]]; then
        curl -ksS \
            -o "$RESPONSE_FILE" \
            -w "%{http_code}" \
            -X "$method" \
            -H "Content-Type: application/json" \
            -d "$body" \
            "$url"

    else
        curl -ksS \
            -o "$RESPONSE_FILE" \
            -w "%{http_code}" \
            -X "$method" \
            "$url"
    fi
}

login_token() {
    local email="$1"
    local password="$2"

    local response

    response="$(
        curl -ksS \
            -X POST \
            -H "Content-Type: application/json" \
            -d "{\"email\":\"$email\",\"password\":\"$password\"}" \
            "$BASE_URL/api/v1/auth/login"
    )"

    json_field "$response" "data.token"
}

write_php() {
    cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

PHP
}

echo
echo "========================================"
echo " JAROA USER MANAGEMENT HAMMER"
echo "========================================"
echo

echo "1. Checking DDEV..."

ddev describe >/dev/null ||
    fail "DDEV project is not available."

echo "   DDEV OK"

echo
echo "2. Checking existing migration state..."

ddev exec php database/migrate.php

echo
echo "3. Checking users.role column..."

cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=db;port=3306;dbname=db;charset=utf8mb4',
    'db',
    'db',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$statement = $pdo->query(
    "SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'role'"
);

echo (int) $statement->fetchColumn();
PHP

ROLE_EXISTS="$(
    ddev exec php "$TEMP_PHP_CONTAINER"
)"

[[ "$ROLE_EXISTS" == "1" ]] ||
    fail "users.role column is missing."

echo "   role column OK"

echo
echo "4. Writing UserRepository..."

cat > "$ROOT/app/Repositories/UserRepository.php" <<'PHP'
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

    public function emailExistsForAnotherUser(
        string $email,
        int $exceptId
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
SELECT COUNT(*)
FROM users
WHERE email = :email
AND id <> :id
SQL
        );

        $statement->execute([
            'email' => $email,
            'id' => $exceptId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function updateProfile(
        int $id,
        string $name,
        string $email
    ): ?User {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET
    name = :name,
    email = :email
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);

        return $this->findById($id);
    }

    public function updatePasswordHash(
        int $id,
        string $passwordHash
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE users
SET password_hash = :password_hash
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
DELETE FROM users
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
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

echo "   UserRepository written"

echo
echo "5. Writing UserService..."

cat > "$ROOT/app/Services/UserService.php" <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Services;

use Jaroa\Models\User;
use Jaroa\Repositories\UserRepository;
use InvalidArgumentException;

final class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {
    }

    public function find(int $id): ?User
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'User ID must be a positive integer.'
            );
        }

        return $this->repository->findById($id);
    }

    public function updateProfile(
        int $id,
        string $name,
        string $email
    ): User {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'User ID must be a positive integer.'
            );
        }

        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            throw new InvalidArgumentException(
                'Name is required.'
            );
        }

        if (mb_strlen($name) > 120) {
            throw new InvalidArgumentException(
                'Name is too long.'
            );
        }

        if (
            $email === '' ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException(
                'A valid email address is required.'
            );
        }

        if (
            $this->repository->emailExistsForAnotherUser(
                $email,
                $id
            )
        ) {
            throw new InvalidArgumentException(
                'Email address is already in use.'
            );
        }

        $user = $this->repository->updateProfile(
            $id,
            $name,
            $email
        );

        if ($user === null) {
            throw new InvalidArgumentException(
                'User not found.'
            );
        }

        return $user;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'User ID must be a positive integer.'
            );
        }

        return $this->repository->delete($id);
    }
}
PHP

echo "   UserService written"

echo
echo "6. Writing UserController..."

cat > "$ROOT/app/Controllers/UserController.php" <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Controllers;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Services\AuthService;
use Jaroa\Services\UserService;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;
use InvalidArgumentException;

final class UserController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuthService $auth
    ) {
    }

    public function show(
        int $id,
        AuthenticatedUser $authenticated
    ): JsonResponse {
        $user = $this->users->find($id);

        if ($user === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'User not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }

    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        try {
            $user = $this->users->updateProfile(
                $id,
                (string) $request->input('name', ''),
                (string) $request->input('email', '')
            );
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'User not found.') {
                return new JsonResponse(
                    [
                        'error' => [
                            'code' => 'not_found',
                            'message' => 'User not found.',
                        ],
                    ],
                    404
                );
            }

            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $deleted = $this->users->delete($id);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        if (!$deleted) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'User not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse([
            'data' => [
                'deleted' => true,
                'id' => $id,
            ],
        ]);
    }

    public function changePassword(
        int $id,
        Request $request
    ): JsonResponse {
        $currentPassword = (string) $request->input(
            'current_password',
            ''
        );

        $newPassword = (string) $request->input(
            'new_password',
            ''
        );

        try {
            $user = $this->auth->changePassword(
                $id,
                $currentPassword,
                $newPassword
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422
            );
        }

        return new JsonResponse([
            'data' => $user->publicData(),
        ]);
    }
}
PHP

echo "   UserController written"

echo
echo "7. Updating AuthenticationMiddleware..."

cat > "$ROOT/app/Middleware/AuthenticationMiddleware.php" <<'PHP'
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
                        'code' => 'unauthorized',
                        'message' => 'Authentication required.',
                    ],
                ],
                401
            );
        }

        $authenticated = $this->auth->authenticateToken(
            $token
        );

        if ($authenticated === null) {
            return new JsonResponse(
                [
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'Invalid or expired authentication token.',
                    ],
                ],
                401
            );
        }

        return $authenticated;
    }
}
PHP

echo "   AuthenticationMiddleware verified"

echo
echo "8. Updating AuthorizationMiddleware..."

cat > "$ROOT/app/Middleware/AuthorizationMiddleware.php" <<'PHP'
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

    public function authorizeOwnerOrAdmin(
        AuthenticatedUser $authenticated,
        int $targetUserId
    ): ?JsonResponse {
        if (
            $authenticated->user->role === 'admin' ||
            $authenticated->user->id === $targetUserId
        ) {
            return null;
        }

        return new JsonResponse(
            [
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'You are not authorized to access this user.',
                ],
            ],
            403
        );
    }

    public function authorizeSelf(
        AuthenticatedUser $authenticated,
        int $targetUserId
    ): ?JsonResponse {
        if ($authenticated->user->id === $targetUserId) {
            return null;
        }

        return new JsonResponse(
            [
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This operation is restricted to your own account.',
                ],
            ],
            403
        );
    }
}
PHP

echo "   AuthorizationMiddleware updated"

echo
echo "9. Updating AuthTokenRepository..."

python3 - <<'PY'
from pathlib import Path

path = Path("app/Repositories/AuthTokenRepository.php")
text = path.read_text()

method = r'''
    public function revokeAllForUser(int $userId): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE auth_tokens
SET revoked_at = CURRENT_TIMESTAMP
WHERE user_id = :user_id
AND revoked_at IS NULL
SQL
        );

        $statement->execute([
            'user_id' => $userId,
        ]);
    }

'''

if "function revokeAllForUser" not in text:
    marker = "\n    private function"
    if marker not in text:
        raise SystemExit(
            "Could not find insertion point in AuthTokenRepository."
        )

    text = text.replace(marker, "\n" + method + "    private function", 1)
    path.write_text(text)
PY

echo "   AuthTokenRepository updated"

echo
echo "10. Updating AuthService..."

cat > "$ROOT/app/Services/AuthService.php" <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Services;

use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Auth\TokenManager;
use Jaroa\Repositories\AuthTokenRepository;
use Jaroa\Repositories\UserRepository;
use InvalidArgumentException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthTokenRepository $tokens,
        private readonly TokenManager $tokenManager
    ) {
    }

    public function register(
        string $name,
        string $email,
        string $password
    ): \Jaroa\Models\User {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            throw new InvalidArgumentException(
                'Name is required.'
            );
        }

        if (
            $email === '' ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException(
                'A valid email address is required.'
            );
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'Password must be at least 8 characters.'
            );
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new InvalidArgumentException(
                'Email address is already registered.'
            );
        }

        $hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($hash === false) {
            throw new \RuntimeException(
                'Unable to hash password.'
            );
        }

        return $this->users->create(
            $name,
            $email,
            $hash
        );
    }

    public function login(
        string $email,
        string $password
    ): array {
        $email = strtolower(trim($email));

        $user = $this->users->findByEmail($email);

        if (
            $user === null ||
            !password_verify(
                $password,
                $user->passwordHash
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid email or password.'
            );
        }

        $plainToken = $this->tokenManager->createToken();

        $this->tokens->create(
            $user->id,
            $this->tokenManager->hashToken($plainToken)
        );

        return [
            'token' => $plainToken,
            'user' => $user,
        ];
    }

    public function authenticateToken(
        string $plainToken
    ): ?AuthenticatedUser {
        $hash = $this->tokenManager->hashToken(
            $plainToken
        );

        $record = $this->tokens->findValidByHash($hash);

        if ($record === null) {
            return null;
        }

        $user = $this->users->findById(
            (int) $record['user_id']
        );

        if ($user === null) {
            return null;
        }

        return new AuthenticatedUser(
            $user,
            (int) $record['id'],
            $hash
        );
    }

    public function logout(
        AuthenticatedUser $authenticated
    ): void {
        $this->tokens->revokeByHash(
            $authenticated->tokenHash
        );
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): \Jaroa\Models\User {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'User ID must be a positive integer.'
            );
        }

        if ($currentPassword === '') {
            throw new InvalidArgumentException(
                'Current password is required.'
            );
        }

        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException(
                'New password must be at least 8 characters.'
            );
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException(
                'User not found.'
            );
        }

        if (
            !password_verify(
                $currentPassword,
                $user->passwordHash
            )
        ) {
            throw new InvalidArgumentException(
                'Current password is incorrect.'
            );
        }

        if (
            password_verify(
                $newPassword,
                $user->passwordHash
            )
        ) {
            throw new InvalidArgumentException(
                'New password must be different from the current password.'
            );
        }

        $hash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($hash === false) {
            throw new \RuntimeException(
                'Unable to hash password.'
            );
        }

        if (
            !$this->users->updatePasswordHash(
                $userId,
                $hash
            )
        ) {
            throw new \RuntimeException(
                'Password could not be updated.'
            );
        }

        $this->tokens->revokeAllForUser($userId);

        $updated = $this->users->findById($userId);

        if ($updated === null) {
            throw new \RuntimeException(
                'Updated user could not be retrieved.'
            );
        }

        return $updated;
    }
}
PHP

echo "   AuthService updated"

echo
echo "11. Updating ControllerProvider..."

cat > "$ROOT/app/Providers/ControllerProvider.php" <<'PHP'
<?php

declare(strict_types=1);

namespace Jaroa\Providers;

use Jaroa\Auth\TokenManager;
use Jaroa\Controllers\AuthController;
use Jaroa\Controllers\PostsController;
use Jaroa\Controllers\UserController;
use Jaroa\Middleware\AuthenticationMiddleware;
use Jaroa\Middleware\AuthorizationMiddleware;
use Jaroa\Repositories\AuthTokenRepository;
use Jaroa\Repositories\PostRepository;
use Jaroa\Repositories\UserRepository;
use Jaroa\Services\AuthService;
use Jaroa\Services\PostService;
use Jaroa\Services\UserService;
use PDO;

final class ControllerProvider
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function posts(): PostsController
    {
        return new PostsController(
            new PostService(
                new PostRepository($this->pdo)
            )
        );
    }

    public function auth(): AuthController
    {
        return new AuthController(
            $this->authService()
        );
    }

    public function users(): UserController
    {
        return new UserController(
            new UserService(
                new UserRepository($this->pdo)
            ),
            $this->authService()
        );
    }

    public function authenticationMiddleware(): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            $this->authService()
        );
    }

    public function authorizationMiddleware(): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware();
    }

    private function authService(): AuthService
    {
        return new AuthService(
            new UserRepository($this->pdo),
            new AuthTokenRepository($this->pdo),
            new TokenManager()
        );
    }
}
PHP

echo "   ControllerProvider updated"

echo
echo "12. Updating API routes..."

cat > "$ROOT/routes/api.php" <<'PHP'
<?php

declare(strict_types=1);

use Jaroa\Application;
use Jaroa\Support\JsonResponse;
use Jaroa\Support\Request;
use Jaroa\Middleware\AuthenticationMiddleware;
use Jaroa\Middleware\AuthorizationMiddleware;

return static function (
    $router,
    Application $app
): void {
    $router->get(
        '/api/v1/status',
        static function (): JsonResponse {
            return new JsonResponse([
                'status' => 'ok',
                'application' => 'Jaroa Engine',
                'version' => '0.1.0',
            ]);
        }
    );

    $controllers = new \Jaroa\Providers\ControllerProvider(
        $app->database()->connection()
    );

    $auth = $controllers->auth();
    $posts = $controllers->posts();
    $users = $controllers->users();

    $authentication =
        $controllers->authenticationMiddleware();

    $authorization =
        $controllers->authorizationMiddleware();

    $router->post(
        '/api/v1/auth/register',
        static function () use ($auth): JsonResponse {
            return $auth->register(
                new Request()
            );
        }
    );

    $router->post(
        '/api/v1/auth/login',
        static function () use ($auth): JsonResponse {
            return $auth->login(
                new Request()
            );
        }
    );

    $router->get(
        '/api/v1/auth/me',
        static function () use (
            $authentication
        ): JsonResponse {
            $result = $authentication->authenticate(
                new Request()
            );

            if ($result instanceof JsonResponse) {
                return $result;
            }

            return new JsonResponse([
                'data' => $result->user->publicData(),
            ]);
        }
    );

    $router->post(
        '/api/v1/auth/logout',
        static function () use (
            $authentication,
            $auth
        ): JsonResponse {
            $result = $authentication->authenticate(
                new Request()
            );

            if ($result instanceof JsonResponse) {
                return $result;
            }

            return $auth->logout($result);
        }
    );

    $router->get(
        '/api/v1/posts',
        static fn (): JsonResponse =>
            $posts->index([])
    );

    $router->get(
        '/api/v1/posts/{id}',
        static function (array $parameters) use (
            $posts
        ): JsonResponse {
            return $posts->show($parameters);
        }
    );

    $router->post(
        '/api/v1/posts',
        static function () use (
            $authentication,
            $authorization,
            $posts
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorization->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $posts->store($request);
        }
    );

    $router->put(
        '/api/v1/posts/{id}',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $posts
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorization->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $posts->update(
                (int) $parameters['id'],
                $request
            );
        }
    );

    $router->delete(
        '/api/v1/posts/{id}',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $posts
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $forbidden =
                $authorization->authorize(
                    $authenticated,
                    ['editor', 'admin']
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $posts->delete(
                (int) $parameters['id']
            );
        }
    );

    $router->get(
        '/api/v1/users/{id}',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $users
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $id = (int) $parameters['id'];

            $forbidden =
                $authorization->authorizeOwnerOrAdmin(
                    $authenticated,
                    $id
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $users->show(
                $id,
                $authenticated
            );
        }
    );

    $router->put(
        '/api/v1/users/{id}',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $users
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $id = (int) $parameters['id'];

            $forbidden =
                $authorization->authorizeOwnerOrAdmin(
                    $authenticated,
                    $id
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $users->update(
                $id,
                $request
            );
        }
    );

    $router->post(
        '/api/v1/users/{id}/password',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $users
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $id = (int) $parameters['id'];

            $forbidden =
                $authorization->authorizeSelf(
                    $authenticated,
                    $id
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            return $users->changePassword(
                $id,
                $request
            );
        }
    );

    $router->delete(
        '/api/v1/users/{id}',
        static function (
            array $parameters
        ) use (
            $authentication,
            $authorization,
            $users
        ): JsonResponse {
            $request = new Request();

            $authenticated =
                $authentication->authenticate($request);

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $id = (int) $parameters['id'];

            $forbidden =
                $authorization->authorizeOwnerOrAdmin(
                    $authenticated,
                    $id
                );

            if ($forbidden !== null) {
                return $forbidden;
            }

            if (
                $authenticated->user->role === 'admin' &&
                $authenticated->user->id === $id
            ) {
                return new JsonResponse(
                    [
                        'error' => [
                            'code' => 'forbidden',
                            'message' => 'Administrators cannot delete their own account.',
                        ],
                    ],
                    403
                );
            }

            return $users->delete($id);
        }
    );
};
PHP

echo "   routes updated"

echo
echo "13. Refreshing Composer autoload..."

ddev exec composer dump-autoload --no-interaction

echo "   autoload refreshed"

echo
echo "14. Running PHP syntax checks..."

ddev exec bash -c '
find \
    /var/www/html/app \
    /var/www/html/bootstrap \
    /var/www/html/config \
    /var/www/html/database \
    /var/www/html/public \
    /var/www/html/routes \
    -type f \
    -name "*.php" \
    -print0 |
while IFS= read -r -d "" file; do
    php -l "$file" >/dev/null
done
'

echo "   PHP syntax OK"

echo
echo "15. Cleaning previous test users..."

cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=db;port=3306;dbname=db;charset=utf8mb4',
    'db',
    'db',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$emails = [
    'user-manage-user@jaroa.local',
    'user-manage-editor@jaroa.local',
    'user-manage-admin@jaroa.local',
];

$statement = $pdo->prepare(
    'DELETE FROM users WHERE email = :email'
);

foreach ($emails as $email) {
    $statement->execute([
        'email' => $email,
    ]);
}
PHP

ddev exec php "$TEMP_PHP_CONTAINER"

echo "   cleanup OK"

echo
echo "16. Creating normal test user..."

USER_RESPONSE="$(
    curl -ksS \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"name\":\"User Management User\",
            \"email\":\"$USER_EMAIL\",
            \"password\":\"$USER_PASSWORD\"
        }" \
        "$BASE_URL/api/v1/auth/register"
)"

USER_ID="$(
    json_field "$USER_RESPONSE" "data.id"
)"

[[ "$USER_ID" =~ ^[0-9]+$ ]] ||
    fail "Could not create normal test user."

echo "   user ID: $USER_ID"

echo
echo "17. Creating editor test user..."

EDITOR_RESPONSE="$(
    curl -ksS \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"name\":\"User Management Editor\",
            \"email\":\"$EDITOR_EMAIL\",
            \"password\":\"$EDITOR_PASSWORD\"
        }" \
        "$BASE_URL/api/v1/auth/register"
)"

EDITOR_ID="$(
    json_field "$EDITOR_RESPONSE" "data.id"
)"

[[ "$EDITOR_ID" =~ ^[0-9]+$ ]] ||
    fail "Could not create editor test user."

echo "   editor ID: $EDITOR_ID"

echo
echo "18. Creating admin test user..."

ADMIN_TEST_RESPONSE="$(
    curl -ksS \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"name\":\"User Management Admin\",
            \"email\":\"$ADMIN_TEST_EMAIL\",
            \"password\":\"$ADMIN_TEST_PASSWORD\"
        }" \
        "$BASE_URL/api/v1/auth/register"
)"

ADMIN_TEST_ID="$(
    json_field "$ADMIN_TEST_RESPONSE" "data.id"
)"

[[ "$ADMIN_TEST_ID" =~ ^[0-9]+$ ]] ||
    fail "Could not create admin test user."

echo "   admin ID: $ADMIN_TEST_ID"

echo
echo "19. Assigning editor/admin roles..."

cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=db;port=3306;dbname=db;charset=utf8mb4',
    'db',
    'db',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$editorId = (int) ($argv[1] ?? 0);
$adminId = (int) ($argv[2] ?? 0);

$statement = $pdo->prepare(
    'UPDATE users SET role = :role WHERE id = :id'
);

$statement->execute([
    'role' => 'editor',
    'id' => $editorId,
]);

$statement->execute([
    'role' => 'admin',
    'id' => $adminId,
]);
PHP

ddev exec php \
    "$TEMP_PHP_CONTAINER" \
    "$EDITOR_ID" \
    "$ADMIN_TEST_ID"

echo "   roles assigned"

echo
echo "20. Logging in test accounts..."

ADMIN_TOKEN="$(
    login_token "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
)"

USER_TOKEN="$(
    login_token "$USER_EMAIL" "$USER_PASSWORD"
)"

EDITOR_TOKEN="$(
    login_token "$EDITOR_EMAIL" "$EDITOR_PASSWORD"
)"

ADMIN_TEST_TOKEN="$(
    login_token "$ADMIN_TEST_EMAIL" "$ADMIN_TEST_PASSWORD"
)"

[[ -n "$ADMIN_TOKEN" ]] ||
    fail "Existing admin login failed."

[[ -n "$USER_TOKEN" ]] ||
    fail "Normal user login failed."

[[ -n "$EDITOR_TOKEN" ]] ||
    fail "Editor login failed."

[[ -n "$ADMIN_TEST_TOKEN" ]] ||
    fail "Test admin login failed."

echo "   tokens acquired"

echo
echo "21. GET own profile..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$USER_TOKEN"
)"

[[ "$STATUS" == "200" ]] ||
    fail "Own profile GET failed: $STATUS"

echo "   OK"

echo
echo "22. GET another profile as normal user..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$EDITOR_ID" \
        "$USER_TOKEN"
)"

[[ "$STATUS" == "403" ]] ||
    fail "Normal user should receive 403: $STATUS"

echo "   403 OK"

echo
echo "23. GET another profile as editor..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$EDITOR_TOKEN"
)"

[[ "$STATUS" == "403" ]] ||
    fail "Editor should receive 403: $STATUS"

echo "   403 OK"

echo
echo "24. GET another profile as admin..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$ADMIN_TOKEN"
)"

[[ "$STATUS" == "200" ]] ||
    fail "Admin should receive 200: $STATUS"

echo "   200 OK"

echo
echo "25. PUT own profile..."

STATUS="$(
    http_status \
        PUT \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$USER_TOKEN" \
        '{
            "name":"Updated User",
            "email":"user-manage-user@jaroa.local"
        }'
)"

[[ "$STATUS" == "200" ]] ||
    fail "Own profile update failed: $STATUS"

echo "   OK"

echo
echo "26. Normal user cannot update another user..."

STATUS="$(
    http_status \
        PUT \
        "$BASE_URL/api/v1/users/$EDITOR_ID" \
        "$USER_TOKEN" \
        '{
            "name":"Unauthorized",
            "email":"user-manage-editor@jaroa.local"
        }'
)"

[[ "$STATUS" == "403" ]] ||
    fail "Unauthorized update should be 403: $STATUS"

echo "   403 OK"

echo
echo "27. Admin can update another user..."

STATUS="$(
    http_status \
        PUT \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$ADMIN_TOKEN" \
        '{
            "name":"Admin Updated User",
            "email":"user-manage-user@jaroa.local",
            "role":"admin"
        }'
)"

[[ "$STATUS" == "200" ]] ||
    fail "Admin update failed: $STATUS"

echo "   OK"

echo
echo "28. Verifying profile update cannot change role..."

ROLE="$(
    json_field \
        "$(cat "$RESPONSE_FILE")" \
        "data.role"
)"

[[ "$ROLE" == "user" ]] ||
    fail "Profile update illegally changed user role."

echo "   role protected"

echo
echo "29. Duplicate email protection..."

STATUS="$(
    http_status \
        PUT \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$USER_TOKEN" \
        '{
            "name":"Updated User",
            "email":"user-manage-editor@jaroa.local"
        }'
)"

[[ "$STATUS" == "422" ]] ||
    fail "Duplicate email should return 422: $STATUS"

echo "   422 OK"

echo
echo "30. Password change without authentication..."

STATUS="$(
    http_status \
        POST \
        "$BASE_URL/api/v1/users/$USER_ID/password" \
        "" \
        '{
            "current_password":"UserPass123!",
            "new_password":"NewUserPass123!"
        }'
)"

[[ "$STATUS" == "401" ]] ||
    fail "Unauthenticated password change should return 401: $STATUS"

echo "   401 OK"

echo
echo "31. Password change against another account..."

STATUS="$(
    http_status \
        POST \
        "$BASE_URL/api/v1/users/$EDITOR_ID/password" \
        "$USER_TOKEN" \
        '{
            "current_password":"EditorPass123!",
            "new_password":"NewEditorPass123!"
        }'
)"

[[ "$STATUS" == "403" ]] ||
    fail "Cross-account password change should return 403: $STATUS"

echo "   403 OK"

echo
echo "32. Wrong current password..."

STATUS="$(
    http_status \
        POST \
        "$BASE_URL/api/v1/users/$USER_ID/password" \
        "$USER_TOKEN" \
        '{
            "current_password":"WrongPassword123!",
            "new_password":"NewUserPass123!"
        }'
)"

[[ "$STATUS" == "422" ]] ||
    fail "Wrong current password should return 422: $STATUS"

echo "   422 OK"

echo
echo "33. Successful password change..."

STATUS="$(
    http_status \
        POST \
        "$BASE_URL/api/v1/users/$USER_ID/password" \
        "$USER_TOKEN" \
        '{
            "current_password":"UserPass123!",
            "new_password":"NewUserPass123!"
        }'
)"

[[ "$STATUS" == "200" ]] ||
    fail "Password change failed: $STATUS"

echo "   password changed"

echo
echo "34. Old password rejected..."

OLD_STATUS="$(
    curl -ksS \
        -o /tmp/jaroa-old-login.json \
        -w "%{http_code}" \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"email\":\"$USER_EMAIL\",
            \"password\":\"$USER_PASSWORD\"
        }" \
        "$BASE_URL/api/v1/auth/login"
)"

[[ "$OLD_STATUS" == "401" ]] ||
    fail "Old password still works: $OLD_STATUS"

echo "   old password rejected"

echo
echo "35. New password accepted..."

NEW_TOKEN="$(
    login_token "$USER_EMAIL" "$NEW_USER_PASSWORD"
)"

[[ -n "$NEW_TOKEN" ]] ||
    fail "New password login failed."

echo "   new password works"

echo
echo "36. Existing sessions revoked after password change..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/auth/me" \
        "$USER_TOKEN"
)"

[[ "$STATUS" == "401" ]] ||
    fail "Old token should be revoked after password change: $STATUS"

echo "   old session revoked"

echo
echo "37. Normal user self-delete..."

STATUS="$(
    http_status \
        DELETE \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$NEW_TOKEN"
)"

[[ "$STATUS" == "200" ]] ||
    fail "Self-delete failed: $STATUS"

echo "   self-delete OK"

echo
echo "38. Deleted user token rejected..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$USER_ID" \
        "$NEW_TOKEN"
)"

[[ "$STATUS" == "401" ]] ||
    fail "Deleted user's token should return 401: $STATUS"

echo "   token rejected"

echo
echo "39. Editor cannot delete another account..."

STATUS="$(
    http_status \
        DELETE \
        "$BASE_URL/api/v1/users/$ADMIN_TEST_ID" \
        "$EDITOR_TOKEN"
)"

[[ "$STATUS" == "403" ]] ||
    fail "Editor deletion should return 403: $STATUS"

echo "   403 OK"

echo
echo "40. Admin can delete another account..."

STATUS="$(
    http_status \
        DELETE \
        "$BASE_URL/api/v1/users/$EDITOR_ID" \
        "$ADMIN_TOKEN"
)"

[[ "$STATUS" == "200" ]] ||
    fail "Admin deletion failed: $STATUS"

echo "   admin delete OK"

echo
echo "41. Deleted editor returns 404..."

STATUS="$(
    http_status \
        GET \
        "$BASE_URL/api/v1/users/$EDITOR_ID" \
        "$ADMIN_TOKEN"
)"

[[ "$STATUS" == "404" ]] ||
    fail "Deleted editor should return 404: $STATUS"

echo "   404 OK"

echo
echo "42. Admin cannot delete own account..."

STATUS="$(
    http_status \
        DELETE \
        "$BASE_URL/api/v1/users/$ADMIN_TEST_ID" \
        "$ADMIN_TEST_TOKEN"
)"

[[ "$STATUS" == "403" ]] ||
    fail "Admin self-delete should return 403: $STATUS"

echo "   self-delete protection OK"

echo
echo "43. Password hash is never exposed..."

RESPONSE="$(
    curl -ksS \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        "$BASE_URL/api/v1/users/$ADMIN_TEST_ID"
)"

if printf '%s' "$RESPONSE" | grep -q "password_hash"; then
    fail "password_hash leaked through API."
fi

echo "   password hash protected"

echo
echo "44. Database password hash verification..."

cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=db;port=3306;dbname=db;charset=utf8mb4',
    'db',
    'db',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$statement = $pdo->prepare(
    'SELECT password_hash
     FROM users
     WHERE email = :email'
);

$statement->execute([
    'email' => 'admin@jaroa.local',
]);

$hash = $statement->fetchColumn();

echo strlen((string) $hash);
PHP

HASH_LENGTH="$(
    ddev exec php "$TEMP_PHP_CONTAINER"
)"

[[ "$HASH_LENGTH" -ge 50 ]] ||
    fail "Password hash appears suspiciously short."

echo "   password hashing OK"

echo
echo "45. Checking posts.user_id foreign key..."

cat > "$TEMP_PHP_HOST" <<'PHP'
<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=db;port=3306;dbname=db;charset=utf8mb4',
    'db',
    'db'
);

$statement = $pdo->query(
    "SELECT COUNT(*)
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'posts'
       AND COLUMN_NAME = 'user_id'
       AND REFERENCED_TABLE_NAME = 'users'"
);

echo (int) $statement->fetchColumn();
PHP

FK_COUNT="$(
    ddev exec php "$TEMP_PHP_CONTAINER"
)"

[[ "$FK_COUNT" == "1" ]] ||
    fail "posts.user_id foreign key missing."

echo "   foreign key OK"

echo
echo "46. Running authentication regression hammer..."

./tests/16-authentication-hammer-test.sh

echo
echo "47. Running authorization regression hammer..."

./tests/17-authorization-hammer-test.sh

echo
echo "48. Final PHP syntax check..."

ddev exec bash -c '
find \
    /var/www/html/app \
    /var/www/html/bootstrap \
    /var/www/html/config \
    /var/www/html/database \
    /var/www/html/public \
    /var/www/html/routes \
    -type f \
    -name "*.php" \
    -print0 |
while IFS= read -r -d "" file; do
    php -l "$file" >/dev/null
done
'

echo "   PHP syntax OK"

echo
echo "49. Final Composer autoload check..."

ddev exec php -r '
require "/var/www/html/vendor/autoload.php";

$classes = [
    \Jaroa\Application::class,
    \Jaroa\Models\User::class,
    \Jaroa\Repositories\UserRepository::class,
    \Jaroa\Services\AuthService::class,
    \Jaroa\Services\UserService::class,
    \Jaroa\Controllers\UserController::class,
    \Jaroa\Controllers\AuthController::class,
];

foreach ($classes as $class) {
    if (!class_exists($class)) {
        throw new RuntimeException(
            "Autoload failed: {$class}"
        );
    }
}

echo "autoload-ok";
' | grep -q "autoload-ok" ||
    fail "Composer autoload verification failed."

echo "   Composer autoload OK"

echo
echo "50. Final admin authentication..."

FINAL_TOKEN="$(
    login_token "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
)"

[[ -n "$FINAL_TOKEN" ]] ||
    fail "Final admin authentication failed."

echo "   authentication OK"

echo
echo "========================================"
echo " JAROA USER MANAGEMENT HAMMER PASSED"
echo "========================================"
echo
echo "User management subsystem: COMPLETE"
echo
```

