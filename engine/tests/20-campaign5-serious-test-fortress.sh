#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="https://jaroa-engine.ddev.site"

cd "$ROOT"

fail() {
    echo
    echo "============================================================"
    echo " ❌ CAMPAIGN 5 FAILURE"
    echo "============================================================"
    echo
    echo "Reason: $1"
    echo
    exit 1
}

run_suite() {
    local label="$1"
    local script="$2"

    echo
    echo "============================================================"
    echo " $label"
    echo "============================================================"
    echo

    if [[ ! -f "$ROOT/$script" ]]; then
        fail "Required test suite not found: $script"
    fi

    bash "$ROOT/$script"

    echo
    echo "------------------------------------------------------------"
    echo " ✅ $label PASSED"
    echo "------------------------------------------------------------"
}

echo
echo "============================================================"
echo "        JAROA CAMPAIGN 5 SERIOUS TEST FORTRESS"
echo "============================================================"
echo
echo " Project : Jaroa Engine"
echo " Root    : $ROOT"
echo " URL     : $BASE_URL"
echo
echo " This is the master regression campaign."
echo " Existing Campaigns will be executed as the primary"
echo " subsystem verification, followed by final fortress checks."
echo
echo "============================================================"

# ------------------------------------------------------------
# 1. DDEV PREFLIGHT
# ------------------------------------------------------------

echo
echo "[1/10] Checking DDEV environment..."

if ! command -v ddev >/dev/null 2>&1; then
    fail "DDEV command is not available."
fi

if ! ddev describe >/dev/null 2>&1; then
    fail "Jaroa Engine DDEV environment is not available."
fi

echo "✅ DDEV environment is available."

# ------------------------------------------------------------
# 2. DATABASE MIGRATION
# ------------------------------------------------------------

echo
echo "[2/10] Running database migrations..."

ddev exec php database/migrate.php

echo
echo "✅ Database migration check passed."

# ------------------------------------------------------------
# 3. CORE HERCULES REGRESSION
# ------------------------------------------------------------

echo
echo "[3/10] Running foundational Hercules regression..."

run_suite \
    "FOUNDATIONAL HERCULES HARDENING" \
    "tests/15.16-hercules-hardening-test.sh"

# ------------------------------------------------------------
# 4. AUTHENTICATION
# ------------------------------------------------------------

echo
echo "[4/10] Running authentication subsystem..."

run_suite \
    "AUTHENTICATION HAMMER" \
    "tests/16-authentication-hammer-test.sh"

# ------------------------------------------------------------
# 5. AUTHORIZATION
# ------------------------------------------------------------

echo
echo "[5/10] Running authorization subsystem..."

run_suite \
    "AUTHORIZATION HAMMER" \
    "tests/17-authorization-hammer-test.sh"

# ------------------------------------------------------------
# 6. MIDDLEWARE
# ------------------------------------------------------------

echo
echo "[6/10] Running middleware subsystem..."

run_suite \
    "MIDDLEWARE HAMMER" \
    "tests/19-middleware-hammer-test.sh"

# ------------------------------------------------------------
# 7. USER MANAGEMENT
# ------------------------------------------------------------

echo
echo "[7/10] Running user-management subsystem..."

run_suite \
    "USER MANAGEMENT HAMMER" \
    "tests/18-user-management-hammer-test.sh"

# ------------------------------------------------------------
# 8. PHP SYNTAX FORTRESS
# ------------------------------------------------------------

echo
echo "[8/10] Running final PHP syntax fortress..."

PHP_FAILURES=0

while IFS= read -r -d '' file; do
    if ! ddev exec php -l "$file" >/dev/null 2>&1; then
        echo "❌ PHP syntax failure: $file"
        PHP_FAILURES=$((PHP_FAILURES + 1))
    fi
done < <(
    find app bootstrap config database public routes tests \
        -type f \
        -name "*.php" \
        -print0 2>/dev/null
)

if [[ "$PHP_FAILURES" -ne 0 ]]; then
    fail "$PHP_FAILURES PHP file(s) failed syntax validation."
fi

echo "✅ All PHP files passed syntax validation."

# ------------------------------------------------------------
# 9. SHELL SYNTAX + COMPOSER AUTOLOAD
# ------------------------------------------------------------

echo
echo "[9/10] Running shell syntax and Composer autoload fortress..."

SHELL_FAILURES=0

while IFS= read -r -d '' file; do
    if ! bash -n "$file" >/dev/null 2>&1; then
        echo "❌ Shell syntax failure: $file"
        SHELL_FAILURES=$((SHELL_FAILURES + 1))
    fi
done < <(
    find tests \
        -type f \
        -name "*.sh" \
        -print0
)

if [[ "$SHELL_FAILURES" -ne 0 ]]; then
    fail "$SHELL_FAILURES shell script(s) failed syntax validation."
fi

echo "✅ All shell test scripts passed syntax validation."

echo
echo "Checking Composer autoload..."

ddev exec php -r '
require "vendor/autoload.php";

$classes = [
    "Jaroa\\Application",
    "Jaroa\\Models\\User",
    "Jaroa\\Models\\Post",
    "Jaroa\\Repositories\\UserRepository",
    "Jaroa\\Repositories\\PostRepository",
    "Jaroa\\Services\\AuthService",
    "Jaroa\\Services\\UserService",
    "Jaroa\\Services\\PostService",
    "Jaroa\\Controllers\\AuthController",
    "Jaroa\\Controllers\\UserController",
    "Jaroa\\Controllers\\PostsController",
    "Jaroa\\Middleware\\MiddlewareInterface",
    "Jaroa\\Middleware\\MiddlewarePipeline",
];

foreach ($classes as $class) {
    if (!class_exists($class) && !interface_exists($class)) {
        fwrite(STDERR, "Missing autoloadable class/interface: {$class}\n");
        exit(1);
    }
}

echo "Composer autoload verification passed.\n";
'

echo "✅ Composer autoload fortress passed."

# ------------------------------------------------------------
# 10. FINAL REAL HTTP SMOKE FORTRESS
# ------------------------------------------------------------

echo
echo "[10/10] Running final real HTTP smoke fortress..."

STATUS_BODY="$(mktemp)"
POSTS_BODY="$(mktemp)"
NOT_FOUND_BODY="$(mktemp)"
LOGIN_BODY="$(mktemp)"

cleanup() {
    rm -f \
        "$STATUS_BODY" \
        "$POSTS_BODY" \
        "$NOT_FOUND_BODY" \
        "$LOGIN_BODY"
}

trap cleanup EXIT

# ------------------------------------------------------------
# Status endpoint
# ------------------------------------------------------------

STATUS_CODE="$(
    curl -ksS \
        -o "$STATUS_BODY" \
        -w "%{http_code}" \
        "$BASE_URL/api/v1/status"
)"

if [[ "$STATUS_CODE" != "200" ]]; then
    echo "Response:"
    cat "$STATUS_BODY"
    fail "GET /api/v1/status returned HTTP $STATUS_CODE instead of 200."
fi

if ! grep -q '"status"' "$STATUS_BODY"; then
    echo "Response:"
    cat "$STATUS_BODY"
    fail "Status endpoint returned an unexpected response."
fi

echo "✅ GET /api/v1/status -> HTTP 200"

# ------------------------------------------------------------
# Public posts endpoint
# ------------------------------------------------------------

POSTS_CODE="$(
    curl -ksS \
        -o "$POSTS_BODY" \
        -w "%{http_code}" \
        "$BASE_URL/api/v1/posts"
)"

if [[ "$POSTS_CODE" != "200" ]]; then
    echo "Response:"
    cat "$POSTS_BODY"
    fail "GET /api/v1/posts returned HTTP $POSTS_CODE instead of 200."
fi

echo "✅ GET /api/v1/posts -> HTTP 200"

# ------------------------------------------------------------
# Unknown route
# ------------------------------------------------------------

NOT_FOUND_CODE="$(
    curl -ksS \
        -o "$NOT_FOUND_BODY" \
        -w "%{http_code}" \
        "$BASE_URL/api/v1/campaign5-this-route-does-not-exist"
)"

if [[ "$NOT_FOUND_CODE" != "404" ]]; then
    echo "Response:"
    cat "$NOT_FOUND_BODY"
    fail "Unknown route returned HTTP $NOT_FOUND_CODE instead of 404."
fi

echo "✅ Unknown route -> HTTP 404"

# ------------------------------------------------------------
# Authentication endpoint sanity
# ------------------------------------------------------------

LOGIN_CODE="$(
    curl -ksS \
        -o "$LOGIN_BODY" \
        -w "%{http_code}" \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"email":"campaign5-invalid@example.test","password":"definitely-not-valid"}' \
        "$BASE_URL/api/v1/auth/login"
)"

if [[ "$LOGIN_CODE" != "401" && "$LOGIN_CODE" != "422" ]]; then
    echo "Response:"
    cat "$LOGIN_BODY"
    fail "Invalid login returned unexpected HTTP $LOGIN_CODE."
fi

echo "✅ Invalid authentication request -> HTTP $LOGIN_CODE"

# ------------------------------------------------------------
# FINAL DECLARATION
# ------------------------------------------------------------

echo
echo
echo "============================================================"
echo "             🛡️ JAROA CAMPAIGN 5 HAMMER PASSED"
echo "============================================================"
echo
echo " Core regression             : PASSED"
echo " Authentication              : PASSED"
echo " Authorization               : PASSED"
echo " Middleware                  : PASSED"
echo " User Management             : PASSED"
echo " Database migrations         : PASSED"
echo " PHP syntax                  : PASSED"
echo " Shell syntax                : PASSED"
echo " Composer autoload           : PASSED"
echo " Real HTTP smoke tests       : PASSED"
echo
echo "============================================================"
echo "       JAROA ENGINE TEST FORTRESS: STANDING"
echo "============================================================"
echo
echo "Campaign 5 is officially COMPLETE."
echo
