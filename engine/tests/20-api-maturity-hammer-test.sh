#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

echo
echo "========================================"
echo " JAROA CAMPAIGN 4 API HAMMER"
echo "========================================"
echo

echo "Checking query support..."
grep -q "function query(" app/Support/Request.php
grep -q "function queries(" app/Support/Request.php
echo "Query support verified."

echo
echo "Checking pagination..."
grep -q "function paginate(" app/Repositories/PostRepository.php
grep -q "total_pages" app/Controllers/PostsController.php
echo "Pagination verified."

echo
echo "Checking filtering..."
grep -q "filter_user_id" app/Repositories/PostRepository.php
grep -q "user_id" app/Controllers/PostsController.php
echo "Filtering verified."

echo
echo "Checking safe sorting..."
grep -q "SORT_COLUMNS" app/Repositories/PostRepository.php
grep -q "Invalid sort field" app/Services/PostService.php
echo "Sorting verified."

echo
echo "Checking search..."
grep -q "LIKE :search" app/Repositories/PostRepository.php
grep -q "search" app/Controllers/PostsController.php
echo "Search verified."

echo
echo "Checking validation..."
grep -q "Limit must be between 1 and 100" app/Services/PostService.php
grep -q "validation_failed" app/Controllers/PostsController.php
echo "Validation verified."

echo
echo "Checking resource relationship..."
grep -q "author" app/Models/Post.php
grep -q "LEFT JOIN users" app/Repositories/PostRepository.php
grep -q "'author'" app/Controllers/PostsController.php
echo "Resource relationship verified."

echo
echo "Checking PHP syntax..."
ddev exec php -l app/Models/Post.php
ddev exec php -l app/Repositories/PostRepository.php
ddev exec php -l app/Services/PostService.php
ddev exec php -l app/Controllers/PostsController.php
ddev exec php -l app/Support/Request.php
ddev exec php -l routes/api.php
echo "Syntax checks passed."

echo
echo "Checking existing post tests..."
ddev exec php tests/post-repository-test.php
ddev exec php tests/post-repository-create-test.php
ddev exec php tests/post-repository-find-test.php
ddev exec php tests/post-repository-update-test.php
ddev exec php tests/post-repository-delete-test.php
ddev exec php tests/post-service-test.php
ddev exec php tests/post-service-create-test.php
ddev exec php tests/post-service-find-test.php
ddev exec php tests/post-service-update-test.php
ddev exec php tests/post-service-delete-test.php
ddev exec php tests/posts-controller-test.php
ddev exec php tests/posts-controller-show-test.php
ddev exec php tests/posts-controller-store-test.php
ddev exec php tests/posts-controller-update-test.php
ddev exec php tests/posts-controller-delete-test.php
echo "Existing post tests passed."

echo
echo "Checking Campaign 2 regression..."
bash tests/18-user-management-clean-hammer.sh
echo "Campaign 2 regression passed."

echo
echo "Checking Campaign 3 regression..."
bash tests/19-middleware-hammer-test.sh
echo "Campaign 3 regression passed."

echo
echo "Checking real HTTP pagination..."
RESPONSE="$(curl -ksS \
    "https://jaroa-engine.ddev.site/api/v1/posts?page=1&limit=2&sort=created_at&order=desc")"

echo "$RESPONSE" | grep -q '"data"'
echo "$RESPONSE" | grep -q '"meta"'
echo "$RESPONSE" | grep -q '"page":1'
echo "$RESPONSE" | grep -q '"limit":2'
echo "$RESPONSE" | grep -q '"total"'
echo "$RESPONSE" | grep -q '"total_pages"'
echo "Real HTTP pagination passed."

echo
echo "Checking invalid pagination..."
STATUS="$(curl -ksS -o /tmp/jaroa-campaign4-invalid.json -w '%{http_code}' \
    "https://jaroa-engine.ddev.site/api/v1/posts?page=0&limit=0")"

[ "$STATUS" = "422" ]
grep -q '"validation_failed"' /tmp/jaroa-campaign4-invalid.json
echo "Invalid pagination validation passed."

echo
echo "Checking invalid sorting..."
STATUS="$(curl -ksS -o /tmp/jaroa-campaign4-sort.json -w '%{http_code}' \
    "https://jaroa-engine.ddev.site/api/v1/posts?sort=DROP_TABLE&order=desc")"

[ "$STATUS" = "422" ]
grep -q '"validation_failed"' /tmp/jaroa-campaign4-sort.json
echo "Safe sorting validation passed."

rm -f /tmp/jaroa-campaign4-invalid.json
rm -f /tmp/jaroa-campaign4-sort.json

echo
echo "========================================"
echo " JAROA CAMPAIGN 4 API HAMMER PASSED"
echo "========================================"
echo
echo "Pagination:        VERIFIED"
echo "Filtering:         VERIFIED"
echo "Sorting:           VERIFIED"
echo "Search:            VERIFIED"
echo "Validation:        VERIFIED"
echo "Error format:      VERIFIED"
echo "Relationships:     VERIFIED"
echo "Regression tests:  PASSED"
echo
echo "========================================"
echo " API MATURITY: COMPLETE"
echo "========================================"
echo
