#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${PROJECT_ROOT}"

echo "========================================"
echo " JAROA AUTHENTICATION HAMMER"
echo "========================================"
echo
echo "Working directory:"
pwd
echo

# ============================================================
# 16.00 Environment
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
# 16.01 Authentication foundation
# ============================================================

echo
echo "Checking authentication foundation..."

required_files=(
    "app/Models/User.php"
    "app/Repositories/UserRepository.php"
    "app/Repositories/AuthTokenRepository.php"
    "app/Auth/AuthenticatedUser.php"
    "app/Auth/TokenManager.php"
    "app/Services/AuthService.php"
    "app/Controllers/AuthController.php"
)

for file in "${required_files[@]}"; do
    if [ ! -f "${file}" ]; then
        echo "ERROR: Missing authentication file:"
        echo "       ${file}"
        exit 1
    fi
done

echo "Authentication foundation files detected."

# ============================================================
# 16.02 Authentication migration
# ============================================================

echo
echo "Creating authentication migration..."

cat > database/migrations/202609030002_create_auth_tokens_table.php <<'PHP'
<?php

declare(strict_types=1);

use Jaroa\Database\Migration;

return new class implements Migration {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            <<<'SQL'
CREATE TABLE auth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_auth_tokens_user_id (user_id),
    INDEX idx_auth_tokens_expires_at (expires_at),
    INDEX idx_auth_tokens_revoked_at (revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            'DROP TABLE IF EXISTS auth_tokens'
        );
    }
};
PHP

echo "Authentication migration created."

# ============================================================
# 16.03 Request bearer-token support
# ============================================================

echo
echo "Checking Request authentication support..."

if grep -q "function bearerToken" app/Support/Request.php; then

    echo "Request bearer-token support already exists."

else

    python3 - <<'PY'
from pathlib import Path

path = Path("app/Support/Request.php")
text = path.read_text()

needle = """    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->json()[$key] ?? $default;
    }
"""

replacement = """    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->json()[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(
            str_replace('-', '_', $name)
        );

        $value = $_SERVER[$serverKey] ?? null;

        return is_string($value) ? $value : null;
    }

    public function bearerToken(): ?string
    {
        $authorization = $this->header(
            'Authorization'
        );

        if ($authorization === null) {
            return null;
        }

        if (
            preg_match(
                '/^Bearer\\s+(.+)$/i',
                trim($authorization),
                $matches
            ) !== 1
        ) {
            return null;
        }

        return trim($matches[1]);
    }
}
"""

if needle not in text:
    raise SystemExit(
        "Request.php expected input() method was not found."
    )

path.write_text(
    text.replace(needle, replacement)
)
PY

    echo "Request bearer-token support added."

fi

# ============================================================
# 16.04 ControllerProvider integration
# ============================================================

echo
echo "Integrating authentication into ControllerProvider..."

python3 - <<'PY'
from pathlib import Path

path = Path("app/Providers/ControllerProvider.php")
text = path.read_text()

# ------------------------------------------------------------
# Imports
# ------------------------------------------------------------

imports = [
    (
        "use Jaroa\\Auth\\TokenManager;",
        "use Jaroa\\Auth\\TokenManager;\n"
    ),
    (
        "use Jaroa\\Controllers\\AuthController;",
        "use Jaroa\\Controllers\\AuthController;\n"
    ),
    (
        "use Jaroa\\Repositories\\AuthTokenRepository;",
        "use Jaroa\\Repositories\\AuthTokenRepository;\n"
    ),
    (
        "use Jaroa\\Repositories\\UserRepository;",
        "use Jaroa\\Repositories\\UserRepository;\n"
    ),
    (
        "use Jaroa\\Services\\AuthService;",
        "use Jaroa\\Services\\AuthService;\n"
    ),
]

# Insert missing imports immediately after namespace.
namespace_line = "namespace Jaroa\\Providers;\n"

if namespace_line not in text:
    raise SystemExit(
        "ControllerProvider.php namespace was not found."
    )

missing_imports = []

for import_line, _ in imports:
    if import_line not in text:
        missing_imports.append(import_line)

if missing_imports:
    block = "\n" + "\n".join(missing_imports) + "\n"
    text = text.replace(
        namespace_line,
        namespace_line + block,
        1
    )

# ------------------------------------------------------------
# auth() method
# ------------------------------------------------------------

if "public function auth():" not in text:

    class_close = "\n}\n"

    if not text.endswith(class_close):
        raise SystemExit(
            "ControllerProvider.php does not have the expected class ending."
        )

    auth_method = """
    public function auth(): AuthController
    {
        $userRepository = new UserRepository(
            $this->pdo
        );

        $tokenRepository = new AuthTokenRepository(
            $this->pdo
        );

        $service = new AuthService(
            $userRepository,
            $tokenRepository,
            new TokenManager()
        );

        return new AuthController(
            $service
        );
    }
"""

    text = text[:-len(class_close)] + auth_method + class_close

path.write_text(text)
PY

echo "ControllerProvider authentication integration complete."

# ============================================================
# 16.05 Authentication routes
# ============================================================

echo
echo "Integrating authentication routes..."

python3 - <<'PY'
from pathlib import Path

path = Path("routes/api.php")
text = path.read_text()

if "/api/v1/auth/register" in text:

    print("Authentication routes already exist.")

else:

    needle = """    $postsController = $controllerProvider->posts();
"""

    if needle not in text:
        raise SystemExit(
            "Expected posts controller initialization was not found in routes/api.php."
        )

    replacement = needle + """
    
    $authController = $controllerProvider->auth();

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

"""

    text = text.replace(
        needle,
        replacement,
        1
    )

    path.write_text(text)

    print("Authentication routes added.")
PY

echo "Authentication routes integration complete."

# ============================================================
# 16.06 Remove old PDO warning
# ============================================================

echo
echo "Cleaning previous migration warning..."

python3 - <<'PY'
from pathlib import Path

path = Path(
    "database/migrations/"
    "202609030001_create_users_table.php"
)

if path.is_file():
    text = path.read_text()
    text = text.replace(
        "\nuse PDO;\n",
        "\n"
    )
    path.write_text(text)

print("Migration warning cleanup complete.")
PY

# ============================================================
# 16.07 Composer
# ============================================================

echo
echo "Running Composer autoload generation..."

ddev exec composer dump-autoload

# ============================================================
# 16.08 Migration
# ============================================================

echo
echo "Running migrations..."

ddev exec php database/migrate.php

echo
echo "Verifying auth_tokens table..."

ddev exec mysql \
    -h db \
    -u db \
    -pdb \
    db \
    -N \
    -e "SHOW TABLES LIKE 'auth_tokens';" \
    | grep -qx "auth_tokens"

echo "auth_tokens table verified."

# ============================================================
# 16.09 Authentication API tests
# ============================================================

echo
echo "========================================"
echo " AUTHENTICATION API TESTS"
echo "========================================"

TIMESTAMP="$(date +%Y%m%d%H%M%S)"
EMAIL="hammer-${TIMESTAMP}@jaroa.local"
PASSWORD="hammer-password-${TIMESTAMP}"

# ------------------------------------------------------------
# Registration
# ------------------------------------------------------------

echo
echo "16.09.1 Registration"

REGISTER_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"Hercules User\",\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${REGISTER_RESPONSE}"

echo "${REGISTER_RESPONSE}" | grep -q '"data"'
echo "${REGISTER_RESPONSE}" | grep -q '"email":"'"${EMAIL}"'"'

if echo "${REGISTER_RESPONSE}" | grep -q 'password_hash'; then
    echo "ERROR: password_hash leaked."
    exit 1
fi

echo "Registration passed."

# ------------------------------------------------------------
# Duplicate registration
# ------------------------------------------------------------

echo
echo "16.09.2 Duplicate registration rejection"

DUPLICATE_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"Duplicate\",\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${DUPLICATE_RESPONSE}"

echo "${DUPLICATE_RESPONSE}" | grep -q '"validation_failed"'

echo "Duplicate registration correctly rejected."

# ------------------------------------------------------------
# Invalid registration
# ------------------------------------------------------------

echo
echo "16.09.3 Invalid registration rejection"

INVALID_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"name":"","email":"not-an-email","password":"123"}' \
        "https://jaroa-engine.ddev.site/api/v1/auth/register"
)"

echo "${INVALID_RESPONSE}"

echo "${INVALID_RESPONSE}" | grep -q '"validation_failed"'

echo "Invalid registration correctly rejected."

# ------------------------------------------------------------
# Wrong password
# ------------------------------------------------------------

echo
echo "16.09.4 Login with wrong password"

WRONG_LOGIN="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${EMAIL}\",\"password\":\"wrong-password\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/login"
)"

echo "${WRONG_LOGIN}"

echo "${WRONG_LOGIN}" | grep -q '"authentication_failed"'

echo "Wrong password correctly rejected."

# ------------------------------------------------------------
# Successful login
# ------------------------------------------------------------

echo
echo "16.09.5 Successful login"

LOGIN_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/login"
)"

echo "${LOGIN_RESPONSE}"

TOKEN="$(
    printf '%s' "${LOGIN_RESPONSE}" |
        python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["token"])
'
)"

if [ -z "${TOKEN}" ]; then
    echo "ERROR: Login token was empty."
    exit 1
fi

if [ "${#TOKEN}" -lt 32 ]; then
    echo "ERROR: Login token is unexpectedly short."
    exit 1
fi

if echo "${LOGIN_RESPONSE}" | grep -q 'password_hash'; then
    echo "ERROR: password_hash leaked during login."
    exit 1
fi

echo "Login passed."

# ------------------------------------------------------------
# Token hash storage
# ------------------------------------------------------------

echo
echo "16.09.6 Verify token is stored only as a hash"

TOKEN_HASH="$(
    printf '%s' "${TOKEN}" |
        sha256sum |
        awk '{print $1}'
)"

TOKEN_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT COUNT(*) FROM auth_tokens WHERE token_hash='${TOKEN_HASH}';"
)"

if [ "${TOKEN_COUNT}" != "1" ]; then
    echo "ERROR: Hashed token was not stored correctly."
    exit 1
fi

RAW_TOKEN_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT COUNT(*) FROM auth_tokens WHERE token_hash='${TOKEN}';"
)"

if [ "${RAW_TOKEN_COUNT}" != "0" ]; then
    echo "ERROR: Raw token appears to be stored."
    exit 1
fi

echo "Token storage security passed."

# ------------------------------------------------------------
# /me without authentication
# ------------------------------------------------------------

echo
echo "16.09.7 /me without authentication"

ME_NO_AUTH="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${ME_NO_AUTH}"

echo "${ME_NO_AUTH}" | grep -q '"unauthenticated"'

echo "Unauthenticated /me correctly rejected."

# ------------------------------------------------------------
# /me invalid token
# ------------------------------------------------------------

echo
echo "16.09.8 /me with invalid token"

ME_INVALID="$(
    curl -sk \
        -X GET \
        -H "Authorization: Bearer definitely-invalid-token" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${ME_INVALID}"

echo "${ME_INVALID}" | grep -q '"unauthenticated"'

echo "Invalid token correctly rejected."

# ------------------------------------------------------------
# /me valid token
# ------------------------------------------------------------

echo
echo "16.09.9 /me with valid token"

ME_VALID="$(
    curl -sk \
        -X GET \
        -H "Authorization: Bearer ${TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${ME_VALID}"

echo "${ME_VALID}" | grep -q '"email":"'"${EMAIL}"'"'

echo "Authenticated /me passed."

# ------------------------------------------------------------
# Logout
# ------------------------------------------------------------

echo
echo "16.09.10 Logout"

LOGOUT_RESPONSE="$(
    curl -sk \
        -X POST \
        -H "Authorization: Bearer ${TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/logout"
)"

echo "${LOGOUT_RESPONSE}"

echo "${LOGOUT_RESPONSE}" | grep -q '"logged_out":true'

echo "Logout passed."

# ------------------------------------------------------------
# Revoked token
# ------------------------------------------------------------

echo
echo "16.09.11 Revoked token must fail"

ME_REVOKED="$(
    curl -sk \
        -X GET \
        -H "Authorization: Bearer ${TOKEN}" \
        "https://jaroa-engine.ddev.site/api/v1/auth/me"
)"

echo "${ME_REVOKED}"

echo "${ME_REVOKED}" | grep -q '"unauthenticated"'

echo "Revoked token correctly rejected."

# ------------------------------------------------------------
# Password hashing
# ------------------------------------------------------------

echo
echo "16.09.12 Database password-hash verification"

HASH_INFO="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT password_hash FROM users WHERE email='${EMAIL}' LIMIT 1;"
)"

if [ -z "${HASH_INFO}" ]; then
    echo "ERROR: Password hash was not stored."
    exit 1
fi

if [ "${HASH_INFO}" = "${PASSWORD}" ]; then
    echo "ERROR: Plain-text password was stored."
    exit 1
fi

case "${HASH_INFO}" in
    '$2y$'*|'$2a$'*|'$2b$'*|'$argon2i$'*|'$argon2id$'*)
        ;;
    *)
        echo "ERROR: Password does not appear to use a secure password hash."
        exit 1
        ;;
esac

echo "Password hashing verification passed."

# ============================================================
# 16.10 Database security verification
# ============================================================

echo
echo "========================================"
echo " DATABASE SECURITY VERIFICATION"
echo "========================================"

echo
echo "16.10.1 Token table structure"

TOKEN_COLUMNS="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "DESCRIBE auth_tokens;"
)"

echo "${TOKEN_COLUMNS}"

echo "${TOKEN_COLUMNS}" | grep -q '^token_hash'
echo "${TOKEN_COLUMNS}" | grep -q '^expires_at'
echo "${TOKEN_COLUMNS}" | grep -q '^revoked_at'

echo "Token table structure verified."

echo
echo "16.10.2 Foreign-key relationship"

FK_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "
SELECT COUNT(*)
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'db'
  AND TABLE_NAME = 'auth_tokens'
  AND COLUMN_NAME = 'user_id'
  AND REFERENCED_TABLE_NAME = 'users';
"
)"

if [ "${FK_COUNT}" != "1" ]; then
    echo "ERROR: auth_tokens.user_id foreign key missing."
    exit 1
fi

echo "Foreign-key relationship verified."

# ============================================================
# 16.11 Previous regression suite
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
# 16.12 Real HTTP regression
# ============================================================

echo
echo "========================================"
echo " REAL HTTP REGRESSION"
echo "========================================"

echo
echo "16.12.1 Status endpoint"

STATUS_RESPONSE="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/status"
)"

echo "${STATUS_RESPONSE}"

echo "${STATUS_RESPONSE}" | grep -q '"status":"ok"'

echo "HTTP status endpoint verified."

echo
echo "16.12.2 Posts endpoint"

POSTS_RESPONSE="$(
    curl -sk \
        -X GET \
        "https://jaroa-engine.ddev.site/api/v1/posts"
)"

echo "${POSTS_RESPONSE}"

echo "${POSTS_RESPONSE}" | grep -q '"data"'

echo "HTTP posts endpoint verified."

# ============================================================
# 16.13 Final syntax verification
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
# 16.14 Composer autoload verification
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
];

foreach ($classes as $class) {

    if (!class_exists($class)) {
        throw new RuntimeException(
            "{$class} could not be autoloaded."
        );
    }
}

echo "Authentication autoload verification passed." . PHP_EOL;
PHP

# ============================================================
# 16.15 Final authentication verification
# ============================================================

echo
echo "========================================"
echo " FINAL AUTHENTICATION VERIFICATION"
echo "========================================"

echo
echo "Checking authentication routes..."

for route in \
    "/api/v1/auth/register" \
    "/api/v1/auth/login" \
    "/api/v1/auth/me" \
    "/api/v1/auth/logout"
do

    if ! grep -q "${route}" routes/api.php; then
        echo "ERROR: Missing route ${route}"
        exit 1
    fi

done

echo "All authentication routes verified."

echo
echo "Checking password hashing implementation..."

grep -q "password_hash" app/Services/AuthService.php
grep -q "password_verify" app/Services/AuthService.php

echo "Password hashing implementation verified."

echo
echo "Checking secure token generation..."

grep -q "random_bytes" app/Auth/TokenManager.php
grep -q "sha256" app/Auth/TokenManager.php

echo "Secure token generation verified."

echo
echo "========================================"
echo " JAROA AUTHENTICATION HAMMER PASSED"
echo "========================================"
echo
echo "Authentication subsystem: COMPLETE"
echo
