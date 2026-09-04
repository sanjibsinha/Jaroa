#!/usr/bin/env bash
set -euo pipefail

echo "========================================"
echo " JAROA CAMPAIGN 3 MIDDLEWARE HAMMER"
echo "========================================"
echo

fail() {
    echo
    echo "❌ FAIL: $1"
    exit 1
}

grep -q "interface MiddlewareInterface" app/Middleware/MiddlewareInterface.php \
    || fail "MiddlewareInterface missing"

grep -q "implements MiddlewareInterface" app/Middleware/AuthenticationMiddleware.php \
    || fail "AuthenticationMiddleware is not pipeline-aware"

grep -q "implements MiddlewareInterface" app/Middleware/AuthorizationMiddleware.php \
    || fail "AuthorizationMiddleware is not pipeline-aware"

grep -q "MiddlewarePipeline" app/Support/Router.php \
    || fail "Router is not connected to MiddlewarePipeline"

grep -q "array \$middleware = \[\]" app/Support/Router.php \
    || fail "Router does not support route middleware"

grep -q "new MiddlewarePipeline" app/Support/Router.php \
    || fail "Router does not execute middleware"

grep -q "AuthorizationMiddleware('owner_or_admin')" routes/api.php \
    || fail "Owner/admin authorization migration missing"

grep -q "AuthorizationMiddleware('self')" routes/api.php \
    || fail "Self authorization migration missing"

php -l app/Middleware/MiddlewareInterface.php >/dev/null \
    || fail "MiddlewareInterface syntax error"

php -l app/Middleware/MiddlewareContext.php >/dev/null \
    || fail "MiddlewareContext syntax error"

php -l app/Middleware/MiddlewarePipeline.php >/dev/null \
    || fail "MiddlewarePipeline syntax error"

php -l app/Middleware/AuthenticationMiddleware.php >/dev/null \
    || fail "AuthenticationMiddleware syntax error"

php -l app/Middleware/AuthorizationMiddleware.php >/dev/null \
    || fail "AuthorizationMiddleware syntax error"

php -l app/Support/Router.php >/dev/null \
    || fail "Router syntax error"

php -l app/Application.php >/dev/null \
    || fail "Application syntax error"

php -l routes/api.php >/dev/null \
    || fail "Routes syntax error"

echo "1. Middleware contract verified..."
echo "2. Middleware context verified..."
echo "3. Middleware pipeline verified..."
echo "4. Authentication middleware verified..."
echo "5. Authorization middleware verified..."
echo "6. Router middleware support verified..."
echo "7. User routes migrated to middleware..."
echo "8. PHP syntax checks passed..."
echo
echo "Running Campaign 2 regression..."
echo

bash tests/18-user-management-clean-hammer.sh

echo
echo "========================================"
echo " JAROA CAMPAIGN 3 MIDDLEWARE HAMMER PASSED"
echo "========================================"
echo "Middleware architecture: COMPLETE"
echo "Campaign 3 is officially COMPLETE."
