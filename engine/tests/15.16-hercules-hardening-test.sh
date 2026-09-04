```bash
#!/usr/bin/env bash

set -euo pipefail

# ============================================================
# JAROA 15.16 HERCULES HARDENING TEST
# ============================================================

cd "$(dirname "$0")/.."

echo
echo "========================================"
echo " JAROA 15.16 HERCULES HARDENING TEST"
echo "========================================"
echo
echo "Working directory:"
pwd

# ============================================================
# Environment checks
# ============================================================

echo
echo "Environment checks"

if ! command -v ddev >/dev/null 2>&1; then
    echo "ERROR: DDEV is not installed or not available."
    exit 1
fi

if ! ddev describe >/dev/null 2>&1; then
    echo "ERROR: DDEV project is not running."
    echo "Run: ddev start"
    exit 1
fi

echo "DDEV project detected."

# ============================================================
# 15.16.1 Router hardening
# ============================================================

echo
echo "15.16.1  Router hardening"

if grep -q "use RuntimeException;" app/Support/Router.php; then
    echo "Router RuntimeException import already present."
else
    echo "Adding RuntimeException import..."

    python3 - <<'PY'
from pathlib import Path

path = Path("app/Support/Router.php")
text = path.read_text()

needle = "use Jaroa\\Support\\NotFoundException;\n"

if needle not in text:
    raise SystemExit(
        "ERROR: Could not find NotFoundException import in Router.php."
    )

text = text.replace(
    needle,
    needle + "use RuntimeException;\n",
    1
)

path.write_text(text)
PY

    echo "RuntimeException import added."
fi

php -l app/Support/Router.php

echo "Router hardening passed."

# ============================================================
# 15.16.2 Core infrastructure regression
# ============================================================

echo
echo "15.16.2  Core infrastructure regression"

ddev exec php tests/database-test.php

ddev exec php tests/migration-test.php

ddev exec php tests/router-test.php

ddev exec php tests/routes-test.php

ddev exec php tests/json-response-test.php

ddev exec php tests/request-test.php

ddev exec php tests/exception-handler-test.php

ddev exec php tests/status-controller-test.php

echo "Core infrastructure regression passed."

# ============================================================
# 15.16.3 Repository regression
# ============================================================

echo
echo "15.16.3  Repository regression"

ddev exec php tests/post-repository-test.php
ddev exec php tests/post-repository-find-test.php
ddev exec php tests/post-repository-create-test.php
ddev exec php tests/post-repository-update-test.php
ddev exec php tests/post-repository-delete-test.php

echo "Repository regression passed."

# ============================================================
# 15.16.4 Service regression
# ============================================================

echo
echo "15.16.4  Service regression"

ddev exec php tests/post-service-test.php
ddev exec php tests/post-service-find-test.php
ddev exec php tests/post-service-create-test.php
ddev exec php tests/post-service-update-test.php
ddev exec php tests/post-service-delete-test.php

echo "Service regression passed."

# ============================================================
# 15.16.5 Controller regression
# ============================================================

echo
echo "15.16.5  Controller regression"

ddev exec php tests/posts-controller-test.php
ddev exec php tests/posts-controller-show-test.php
ddev exec php tests/posts-controller-store-test.php
ddev exec php tests/posts-controller-update-test.php
ddev exec php tests/posts-controller-delete-test.php

echo "Controller regression passed."

# ============================================================
# 15.16.6 Test isolation
# ============================================================

echo
echo "15.16.6  Test isolation"

BEFORE_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT COUNT(*) FROM posts"
)"

echo "Post count before isolated tests: ${BEFORE_COUNT}"

ddev exec php tests/post-repository-delete-test.php
ddev exec php tests/post-service-delete-test.php
ddev exec php tests/posts-controller-delete-test.php

AFTER_COUNT="$(
    ddev exec mysql \
        -h db \
        -u db \
        -pdb \
        db \
        -N \
        -e "SELECT COUNT(*) FROM posts"
)"

echo "Post count after isolated tests: ${AFTER_COUNT}"

if [ "${BEFORE_COUNT}" != "${AFTER_COUNT}" ]; then
    echo
    echo "ERROR: Test isolation failed."
    echo "Post count changed from ${BEFORE_COUNT} to ${AFTER_COUNT}."
    exit 1
fi

echo "Test isolation passed."

# ============================================================
# 15.16.7 Database constraint verification
# ============================================================

echo
echo "15.16.7  Database constraint verification"

ddev exec mysql \
    -h db \
    -u db \
    -pdb \
    db \
    -e "SHOW CREATE TABLE posts\G" \
    | grep -q "UNIQUE"

echo "Unique slug constraint detected."

echo "Database constraint verification passed."

# ============================================================
# 15.16.8 Real HTTP status/posts regression
# ============================================================

echo
echo "15.16.8  Real HTTP status/posts regression"

STATUS_RESPONSE="$(
    curl -ksS \
        https://jaroa-engine.ddev.site/api/v1/status
)"

echo "Status response:"
echo "${STATUS_RESPONSE}"

python3 - "${STATUS_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert data["status"] == "ok"
assert data["application"] == "Jaroa Engine"
assert data["version"] == "0.1.0"

print("HTTP status endpoint verified.")
PY

POSTS_RESPONSE="$(
    curl -ksS \
        https://jaroa-engine.ddev.site/api/v1/posts
)"

python3 - "${POSTS_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert "data" in data
assert isinstance(data["data"], list)

print("HTTP posts endpoint verified.")
PY

echo "Real HTTP status/posts regression passed."

# ============================================================
# 15.16.9 Real HTTP CRUD regression
# ============================================================

echo
echo "15.16.9  Real HTTP CRUD regression"

TIMESTAMP="$(date +%Y%m%d%H%M%S)"

CREATE_RESPONSE="$(
    curl -ksS \
        -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"user_id\": 1,
            \"title\": \"Hercules HTTP Post ${TIMESTAMP}\",
            \"slug\": \"hercules-http-post-${TIMESTAMP}\",
            \"content\": \"Created by the Jaroa 15.16 Hercules test ${TIMESTAMP}.\"
        }" \
        https://jaroa-engine.ddev.site/api/v1/posts
)"

echo "Create response:"
echo "${CREATE_RESPONSE}"

POST_ID="$(
    python3 - "${CREATE_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert "data" in data
assert "id" in data["data"]

print(data["data"]["id"])
PY
)"

echo "Created temporary post ID: ${POST_ID}"

GET_RESPONSE="$(
    curl -ksS \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

python3 - "${GET_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert data["data"]["id"] > 0

print("HTTP GET verified.")
PY

UPDATE_RESPONSE="$(
    curl -ksS \
        -X PUT \
        -H "Content-Type: application/json" \
        -d "{
            \"title\": \"Hercules HTTP Updated ${TIMESTAMP}\",
            \"slug\": \"hercules-http-updated-${TIMESTAMP}\",
            \"content\": \"Updated by the Jaroa 15.16 Hercules test ${TIMESTAMP}.\"
        }" \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

echo "Update response:"
echo "${UPDATE_RESPONSE}"

python3 - "${UPDATE_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert data["data"]["title"].startswith("Hercules HTTP Updated")

print("HTTP PUT verified.")
PY

DELETE_RESPONSE="$(
    curl -ksS \
        -X DELETE \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

echo "Delete response:"
echo "${DELETE_RESPONSE}"

python3 - "${DELETE_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert data["data"]["deleted"] is True

print("HTTP DELETE verified.")
PY

MISSING_RESPONSE="$(
    curl -ksS \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

python3 - "${MISSING_RESPONSE}" <<'PY'
import json
import sys

data = json.loads(sys.argv[1])

assert data["error"]["code"] == "not_found"

print("Deleted post correctly returns not_found.")
PY

echo "Real HTTP CRUD regression passed."

# ============================================================
# 15.16.10 Final syntax and autoload verification
# ============================================================

echo
echo "15.16.10  Final syntax and autoload verification"

find app config bootstrap database routes public tests \
    -type f \
    -name "*.php" \
    -print0 |
while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done

echo "All PHP files passed syntax checks."

ddev exec php -r '
require "vendor/autoload.php";

if (!class_exists("Jaroa\\Application")) {
    throw new RuntimeException(
        "Jaroa\\Application could not be autoloaded."
    );
}

if (!class_exists("Jaroa\\Support\\Router")) {
    throw new RuntimeException(
        "Jaroa\\Support\\Router could not be autoloaded."
    );
}

if (!class_exists("Jaroa\\Repositories\\PostRepository")) {
    throw new RuntimeException(
        "Jaroa\\Repositories\\PostRepository could not be autoloaded."
    );
}

if (!class_exists("Jaroa\\Services\\PostService")) {
    throw new RuntimeException(
        "Jaroa\\Services\\PostService could not be autoloaded."
    );
}

if (!class_exists("Jaroa\\Controllers\\PostsController")) {
    throw new RuntimeException(
        "Jaroa\\Controllers\\PostsController could not be autoloaded."
    );
}

echo "Composer autoload verification passed." . PHP_EOL;
'

echo
echo "========================================"
echo " JAROA 15.16 HERCULES TEST PASSED"
echo "========================================"
echo
echo "15.16 Hardening: COMPLETE"
echo
```

