#!/usr/bin/env bash

set -e

echo
echo "========================================"
echo " JAROA 15.15 HERCULES TEST"
echo "========================================"
echo

python3 - <<'PY'
from pathlib import Path

# ============================================================
# 15.15.1 Repository::delete()
# ============================================================

path = Path("app/Repositories/PostRepository.php")
text = path.read_text()

needle = """    private function mapToPost(array $row): Post
"""

method = """    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
DELETE FROM posts
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

"""

if "public function delete(int $id): bool" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate PostRepository::mapToPost()."
        )

    text = text.replace(needle, method + needle)
    path.write_text(text)

# ============================================================
# 15.15.2 Repository test
# ============================================================

Path("tests/post-repository-delete-test.php").write_text("""<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\\Application();

$repository = new Jaroa\\Repositories\\PostRepository(
    $app->database()->connection()
);

$unique = date('YmdHis');

$post = $repository->create(
    userId: 1,
    title: 'Repository Delete Test ' . $unique,
    slug: 'repository-delete-test-' . $unique,
    content: 'Temporary post for repository delete testing.'
);

if ($post->id <= 0) {
    throw new RuntimeException(
        'Expected created post to have a valid ID.'
    );
}

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$deleted = $repository->delete($post->id);

if ($deleted !== true) {
    throw new RuntimeException(
        'Expected existing post to be deleted successfully.'
    );
}

echo 'Repository deleted temporary post.' . PHP_EOL;

$deletedAgain = $repository->delete($post->id);

if ($deletedAgain !== false) {
    throw new RuntimeException(
        'Expected deleting the same post again to return false.'
    );
}

echo 'Repository correctly returned false for missing post.' . PHP_EOL;
echo 'Repository delete test passed.' . PHP_EOL;
""")

# ============================================================
# 15.15.3 Service::delete()
# ============================================================

path = Path("app/Services/PostService.php")
text = path.read_text()

needle = """    public function update(
"""

method = """    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        return $this->repository->delete($id);
    }

"""

if "public function delete(int $id): bool" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate PostService::update()."
        )

    text = text.replace(needle, method + needle)
    path.write_text(text)

# ============================================================
# 15.15.4 Service test
# ============================================================

Path("tests/post-service-delete-test.php").write_text("""<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\\Application();

$repository = new Jaroa\\Repositories\\PostRepository(
    $app->database()->connection()
);

$service = new Jaroa\\Services\\PostService(
    $repository
);

$unique = date('YmdHis');

$post = $repository->create(
    userId: 1,
    title: 'Service Delete Test ' . $unique,
    slug: 'service-delete-test-' . $unique,
    content: 'Temporary post for service delete testing.'
);

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$deleted = $service->delete($post->id);

if ($deleted !== true) {
    throw new RuntimeException(
        'Expected service to delete existing post.'
    );
}

echo 'Service deleted temporary post.' . PHP_EOL;

$deletedAgain = $service->delete($post->id);

if ($deletedAgain !== false) {
    throw new RuntimeException(
        'Expected service to return false for missing post.'
    );
}

echo 'Service correctly returned false for missing post.' . PHP_EOL;

try {
    $service->delete(0);

    throw new RuntimeException(
        'Expected invalid post ID to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Invalid post ID correctly rejected.' . PHP_EOL;
}

echo 'Service delete test passed.' . PHP_EOL;
""")

# ============================================================
# 15.15.5 Controller::delete()
# ============================================================

path = Path("app/Controllers/PostsController.php")
text = path.read_text()

needle = """    public function update(
"""

method = """    public function delete(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->delete($id);
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
                        'message' => 'Post not found.',
                    ],
                ],
                404
            );
        }

        return new JsonResponse(
            [
                'data' => [
                    'deleted' => true,
                    'id' => $id,
                ],
            ]
        );
    }

"""

if "public function delete(int $id): JsonResponse" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate PostsController::update()."
        )

    text = text.replace(needle, method + needle)
    path.write_text(text)

# ============================================================
# 15.15.6 Controller test
# ============================================================

Path("tests/posts-controller-delete-test.php").write_text("""<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\\Application();

$repository = new Jaroa\\Repositories\\PostRepository(
    $app->database()->connection()
);

$service = new Jaroa\\Services\\PostService(
    $repository
);

$controller = new Jaroa\\Controllers\\PostsController(
    $service
);

$unique = date('YmdHis');

$post = $repository->create(
    userId: 1,
    title: 'Controller Delete Test ' . $unique,
    slug: 'controller-delete-test-' . $unique,
    content: 'Temporary post for controller delete testing.'
);

echo 'Created temporary post ID: ' . $post->id . PHP_EOL;

$response = $controller->delete($post->id);

if ($response->status() !== 200) {
    throw new RuntimeException(
        'Expected status 200 for successful delete.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful delete response.'
    );
}

if ($data['data']['deleted'] !== true) {
    throw new RuntimeException(
        'Expected deleted to be true.'
    );
}

if ($data['data']['id'] !== $post->id) {
    throw new RuntimeException(
        'Expected deleted post ID in response.'
    );
}

echo 'Controller deleted temporary post successfully.' . PHP_EOL;

$missingResponse = $controller->delete($post->id);

if ($missingResponse->status() !== 404) {
    throw new RuntimeException(
        'Expected 404 when deleting missing post.'
    );
}

echo 'Controller correctly returned 404 for missing post.' . PHP_EOL;

$invalidResponse = $controller->delete(0);

if ($invalidResponse->status() !== 422) {
    throw new RuntimeException(
        'Expected 422 for invalid post ID.'
    );
}

echo 'Controller correctly returned 422 for invalid post ID.' . PHP_EOL;

echo 'Controller delete test passed.' . PHP_EOL;
""")

# ============================================================
# 15.15.7 DELETE route
# ============================================================

path = Path("routes/api.php")
text = path.read_text()

route = """
    $router->delete(
        '/api/v1/posts/{id}',
        static function (array $parameters) use ($postsController): mixed {
            return $postsController->delete(
                (int) $parameters['id']
            );
        }
    );
"""

if "$router->delete(" not in text:
    marker = """    $router->put(
        '/api/v1/posts/{id}',
        static function (array $parameters) use ($postsController): mixed {
            return $postsController->update(
                (int) $parameters['id'],
                new Request()
            );
        }
    );"""

    if marker not in text:
        raise RuntimeError(
            "Could not locate PUT /api/v1/posts/{id} route."
        )

    text = text.replace(
        marker,
        marker + route
    )

    path.write_text(text)

print("Source and test files prepared.")
PY

echo "1/10  Syntax checks"

ddev exec php -l app/Repositories/PostRepository.php
ddev exec php -l app/Services/PostService.php
ddev exec php -l app/Controllers/PostsController.php
ddev exec php -l routes/api.php

echo
echo "2/10  Repository delete test"

ddev exec php tests/post-repository-delete-test.php

echo
echo "3/10  Service delete test"

ddev exec php tests/post-service-delete-test.php

echo
echo "4/10  Controller delete test"

ddev exec php tests/posts-controller-delete-test.php

echo
echo "5/10  Composer autoload"

ddev exec composer dump-autoload

UNIQUE="$(date +%Y%m%d%H%M%S)"

echo
echo "6/10  Create temporary post through real HTTP API"

CREATE_OUTPUT="$(
    curl -k -sS \
        -X POST \
        https://jaroa-engine.ddev.site/api/v1/posts \
        -H "Content-Type: application/json" \
        -d "{
            \"user_id\": 1,
            \"title\": \"HTTP Delete Test ${UNIQUE}\",
            \"slug\": \"http-delete-test-${UNIQUE}\",
            \"content\": \"Temporary post for real HTTP DELETE testing.\"
        }"
)"

printf '%s\n' "$CREATE_OUTPUT"

POST_ID="$(
    printf '%s\n' "$CREATE_OUTPUT" |
    python3 -c '
import json
import sys

data = json.load(sys.stdin)
print(data["data"]["id"])
'
)"

if [ -z "$POST_ID" ]; then
    echo "ERROR: Could not extract temporary post ID."
    exit 1
fi

echo "Temporary HTTP post ID: ${POST_ID}"

echo
echo "7/10  Real HTTP DELETE"

HTTP_DELETE="$(
    curl -k -sS -i \
        -X DELETE \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

printf '%s\n' "$HTTP_DELETE"

printf '%s\n' "$HTTP_DELETE" | grep -q "HTTP/2 200"

echo
echo "8/10  Verify deleted post returns 404"

HTTP_GET_404="$(
    curl -k -sS -i \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

printf '%s\n' "$HTTP_GET_404"

printf '%s\n' "$HTTP_GET_404" | grep -q "HTTP/2 404"

printf '%s\n' "$HTTP_GET_404" | grep -q "Post not found."

echo
echo "9/10  Real HTTP DELETE missing post"

HTTP_DELETE_404="$(
    curl -k -sS -i \
        -X DELETE \
        "https://jaroa-engine.ddev.site/api/v1/posts/${POST_ID}"
)"

printf '%s\n' "$HTTP_DELETE_404"

printf '%s\n' "$HTTP_DELETE_404" | grep -q "HTTP/2 404"

printf '%s\n' "$HTTP_DELETE_404" | grep -q "Post not found."

echo
echo "10/10  Real HTTP DELETE invalid ID"

HTTP_DELETE_422="$(
    curl -k -sS -i \
        -X DELETE \
        "https://jaroa-engine.ddev.site/api/v1/posts/0"
)"

printf '%s\n' "$HTTP_DELETE_422"

printf '%s\n' "$HTTP_DELETE_422" | grep -q "HTTP/2 422"

printf '%s\n' "$HTTP_DELETE_422" | grep -q "Post ID must be greater than zero."

echo
echo "========================================"
echo " HERCULES TEST PASSED"
echo " 15.15 Delete Post: COMPLETE"
echo "========================================"
echo
