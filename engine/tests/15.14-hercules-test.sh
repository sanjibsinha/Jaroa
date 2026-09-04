#!/usr/bin/env bash

set -e

echo
echo "========================================"
echo " JAROA 15.14 HERCULES TEST"
echo "========================================"
echo

python3 - <<'PY'
from pathlib import Path

# ============================================================
# 15.14.1 Repository::update()
# ============================================================

path = Path("app/Repositories/PostRepository.php")

text = path.read_text()

needle = """    private function mapToPost(array $row): Post
"""

method = """    public function update(
        int $id,
        string $title,
        string $slug,
        string $content
    ): ?Post {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE posts
SET
    title = :title,
    slug = :slug,
    content = :content
WHERE id = :id
SQL
        );

        $statement->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
        ]);

        return $this->find($id);
    }

"""

if "public function update(" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate PostRepository::mapToPost()."
        )

    text = text.replace(needle, method + needle)

path.write_text(text)

# ============================================================
# 15.14.2 Repository test
# ============================================================

Path("tests/post-repository-update-test.php").write_text("""<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\\Application();

$repository = new Jaroa\\Repositories\\PostRepository(
    $app->database()->connection()
);

$post = $repository->find(1);

if ($post === null) {
    throw new RuntimeException(
        'Expected post 1 to exist before update.'
    );
}

$unique = date('YmdHis');

$updated = $repository->update(
    id: 1,
    title: 'Updated First Jaroa Post ' . $unique,
    slug: 'updated-first-jaroa-post-' . $unique,
    content: 'This post was updated by the repository test ' . $unique . '.'
);

if ($updated === null) {
    throw new RuntimeException(
        'Expected updated post to be returned.'
    );
}

if ($updated->id !== 1) {
    throw new RuntimeException(
        'Expected updated post ID to remain 1.'
    );
}

if ($updated->title !== 'Updated First Jaroa Post ' . $unique) {
    throw new RuntimeException(
        'Updated title does not match.'
    );
}

if ($updated->slug !== 'updated-first-jaroa-post-' . $unique) {
    throw new RuntimeException(
        'Updated slug does not match.'
    );
}

if (
    $updated->content !==
    'This post was updated by the repository test ' . $unique . '.'
) {
    throw new RuntimeException(
        'Updated content does not match.'
    );
}

$missing = $repository->update(
    id: 999999,
    title: 'Missing',
    slug: 'missing-' . $unique,
    content: 'Missing post.'
);

if ($missing !== null) {
    throw new RuntimeException(
        'Expected update of missing post to return null.'
    );
}

echo 'Repository updated post ID: ' . $updated->id . PHP_EOL;
echo 'Repository correctly returned null for missing post.' . PHP_EOL;
echo 'Repository update test passed.' . PHP_EOL;
""")

# ============================================================
# 15.14.3 Service::update()
# ============================================================

Path("app/Services/PostService.php").write_text("""<?php

declare(strict_types=1);

namespace Jaroa\\Services;

use InvalidArgumentException;
use Jaroa\\Models\\Post;
use Jaroa\\Repositories\\PostRepository;

final class PostService
{
    public function __construct(
        private readonly PostRepository $repository
    ) {
    }

    /**
     * @return Post[]
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    public function create(
        int $userId,
        string $title,
        string $slug,
        string $content
    ): Post {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'User ID must be greater than zero.'
            );
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException(
                'Title is required.'
            );
        }

        if (trim($slug) === '') {
            throw new InvalidArgumentException(
                'Slug is required.'
            );
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                'Content is required.'
            );
        }

        return $this->repository->create(
            userId: $userId,
            title: $title,
            slug: $slug,
            content: $content
        );
    }

    public function update(
        int $id,
        string $title,
        string $slug,
        string $content
    ): ?Post {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Post ID must be greater than zero.'
            );
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException(
                'Title is required.'
            );
        }

        if (trim($slug) === '') {
            throw new InvalidArgumentException(
                'Slug is required.'
            );
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                'Content is required.'
            );
        }

        return $this->repository->update(
            id: $id,
            title: $title,
            slug: $slug,
            content: $content
        );
    }
}
""")

# ============================================================
# 15.14.4 Service test
# ============================================================

Path("tests/post-service-update-test.php").write_text("""<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$app = new Jaroa\\Application();

$repository = new Jaroa\\Repositories\\PostRepository(
    $app->database()->connection()
);

$service = new Jaroa\\Services\\PostService(
    $repository
);

$post = $service->find(1);

if ($post === null) {
    throw new RuntimeException(
        'Expected post 1 to exist before service update.'
    );
}

$unique = date('YmdHis');

$updated = $service->update(
    id: 1,
    title: 'Service Updated Post ' . $unique,
    slug: 'service-updated-post-' . $unique,
    content: 'Updated through the service layer ' . $unique . '.'
);

if ($updated === null) {
    throw new RuntimeException(
        'Expected service update to return a post.'
    );
}

if ($updated->id !== 1) {
    throw new RuntimeException(
        'Expected updated post ID to remain 1.'
    );
}

echo 'Service updated post ID: ' . $updated->id . PHP_EOL;

$missing = $service->update(
    id: 999999,
    title: 'Missing',
    slug: 'missing-service-' . $unique,
    content: 'Missing.'
);

if ($missing !== null) {
    throw new RuntimeException(
        'Expected missing service update to return null.'
    );
}

echo 'Service correctly returned null for missing post.' . PHP_EOL;

foreach ([
    'title' => ['', 'valid-slug', 'valid-content'],
    'slug' => ['Valid title', '', 'valid-content'],
    'content' => ['Valid title', 'valid-slug', ''],
] as $field => $values) {
    try {
        $service->update(
            id: 1,
            title: $values[0],
            slug: $values[1],
            content: $values[2]
        );

        throw new RuntimeException(
            "Expected empty {$field} to be rejected."
        );
    } catch (InvalidArgumentException $exception) {
        echo "Empty {$field} correctly rejected." . PHP_EOL;
    }
}

try {
    $service->update(
        id: 0,
        title: 'Valid title',
        slug: 'valid-id-test-' . $unique,
        content: 'Valid content'
    );

    throw new RuntimeException(
        'Expected invalid post ID to be rejected.'
    );
} catch (InvalidArgumentException $exception) {
    echo 'Invalid post ID correctly rejected.' . PHP_EOL;
}

echo 'Service update test passed.' . PHP_EOL;
""")

# ============================================================
# 15.14.5 Controller::update()
# ============================================================

path = Path("app/Controllers/PostsController.php")

text = path.read_text()

needle = """    public function store(Request $request): JsonResponse
"""

method = """    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        $data = $request->json();

        try {
            $post = $this->service->update(
                id: $id,
                title: (string) ($data['title'] ?? ''),
                slug: (string) ($data['slug'] ?? ''),
                content: (string) ($data['content'] ?? '')
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

        if ($post === null) {
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

        return new JsonResponse([
            'data' => [
                'id' => $post->id,
                'user_id' => $post->userId,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'created_at' => $post->createdAt,
                'updated_at' => $post->updatedAt,
            ],
        ]);
    }

"""

if "public function update(" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate PostsController::store()."
        )

    text = text.replace(needle, method + needle)

path.write_text(text)

# ============================================================
# 15.14.6 Controller test
# ============================================================

Path("tests/posts-controller-update-test.php").write_text("""<?php

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

$request = new Jaroa\\Support\\Request(
    json_encode([
        'title' => 'Controller Updated Post ' . $unique,
        'slug' => 'controller-updated-post-' . $unique,
        'content' => 'Updated through the controller ' . $unique . '.',
    ])
);

$response = $controller->update(1, $request);

if ($response->status() !== 200) {
    throw new RuntimeException(
        'Expected status 200 for successful update.'
    );
}

$data = $response->data();

if (!isset($data['data'])) {
    throw new RuntimeException(
        'Expected data key in successful update response.'
    );
}

if ($data['data']['id'] !== 1) {
    throw new RuntimeException(
        'Expected updated post ID 1.'
    );
}

echo 'Controller updated post successfully.' . PHP_EOL;

$missingRequest = new Jaroa\\Support\\Request(
    json_encode([
        'title' => 'Missing',
        'slug' => 'missing-controller-' . $unique,
        'content' => 'Missing.',
    ])
);

$missingResponse = $controller->update(
    999999,
    $missingRequest
);

if ($missingResponse->status() !== 404) {
    throw new RuntimeException(
        'Expected 404 for missing post.'
    );
}

echo 'Controller correctly returned 404 for missing post.' . PHP_EOL;

$invalidRequest = new Jaroa\\Support\\Request(
    json_encode([
        'title' => '',
        'slug' => 'invalid-controller-' . $unique,
        'content' => 'Invalid.',
    ])
);

$invalidResponse = $controller->update(
    1,
    $invalidRequest
);

if ($invalidResponse->status() !== 422) {
    throw new RuntimeException(
        'Expected 422 for validation failure.'
    );
}

echo 'Controller correctly returned 422 for invalid input.' . PHP_EOL;

echo 'Controller update test passed.' . PHP_EOL;
""")

# ============================================================
# 15.14.7 PUT route
# ============================================================

path = Path("routes/api.php")

text = path.read_text()

needle = """    $router->post(
        '/api/v1/posts',
        static function () use ($postsController): mixed {
            return $postsController->store(
                new Request()
            );
        }
    );
"""

replacement = """    $router->post(
        '/api/v1/posts',
        static function () use ($postsController): mixed {
            return $postsController->store(
                new Request()
            );
        }
    );

    $router->put(
        '/api/v1/posts/{id}',
        static function (array $parameters) use ($postsController): mixed {
            return $postsController->update(
                (int) $parameters['id'],
                new Request()
            );
        }
    );
"""

if "'/api/v1/posts/{id}'" not in text:
    if needle not in text:
        raise RuntimeError(
            "Could not locate POST /api/v1/posts route."
        )

    text = text.replace(needle, replacement)

path.write_text(text)

print("Source and test files prepared.")
PY

echo "1/10  Syntax checks"

ddev exec php -l app/Repositories/PostRepository.php
ddev exec php -l app/Services/PostService.php
ddev exec php -l app/Controllers/PostsController.php
ddev exec php -l routes/api.php

echo
echo "2/10  Repository update test"

ddev exec php tests/post-repository-update-test.php

echo
echo "3/10  Service update test"

ddev exec php tests/post-service-update-test.php

echo
echo "4/10  Controller update test"

ddev exec php tests/posts-controller-update-test.php

echo
echo "5/10  Composer autoload"

ddev exec composer dump-autoload

UNIQUE="$(date +%Y%m%d%H%M%S)"

SLUG="http-updated-post-${UNIQUE}"

echo
echo "6/10  Real HTTP PUT"

HTTP_OUTPUT="$(
    curl -k -sS -i \
        -X PUT \
        https://jaroa-engine.ddev.site/api/v1/posts/1 \
        -H "Content-Type: application/json" \
        -d "{
            \"title\": \"HTTP Updated Jaroa Post ${UNIQUE}\",
            \"slug\": \"${SLUG}\",
            \"content\": \"This post was updated through the real Jaroa HTTP API.\"
        }"
)"

printf '%s\n' "$HTTP_OUTPUT"

echo
echo "7/10  Assert HTTP PUT returned 200"

printf '%s\n' "$HTTP_OUTPUT" | grep -q "HTTP/2 200"

echo
echo "8/10  Real HTTP 404"

HTTP_404="$(
    curl -k -sS -i \
        -X PUT \
        https://jaroa-engine.ddev.site/api/v1/posts/999999 \
        -H "Content-Type: application/json" \
        -d "{
            \"title\": \"Missing Post\",
            \"slug\": \"missing-http-${UNIQUE}\",
            \"content\": \"This should return 404.\"
        }"
)"

printf '%s\n' "$HTTP_404"

printf '%s\n' "$HTTP_404" | grep -q "HTTP/2 404"

echo
echo "9/10  Real HTTP validation"

HTTP_422="$(
    curl -k -sS -i \
        -X PUT \
        https://jaroa-engine.ddev.site/api/v1/posts/1 \
        -H "Content-Type: application/json" \
        -d "{
            \"title\": \"\",
            \"slug\": \"invalid-http-${UNIQUE}\",
            \"content\": \"This should fail validation.\"
        }"
)"

printf '%s\n' "$HTTP_422"

printf '%s\n' "$HTTP_422" | grep -q "HTTP/2 422"

echo
echo "10/10  Verify updated post through GET"

GET_OUTPUT="$(
    curl -k -sS \
        https://jaroa-engine.ddev.site/api/v1/posts/1
)"

printf '%s\n' "$GET_OUTPUT"

printf '%s\n' "$GET_OUTPUT" | grep -q "HTTP Updated Jaroa Post ${UNIQUE}"

echo
echo "========================================"
echo " HERCULES TEST PASSED"
echo " 15.14 Update Post: COMPLETE"
echo "========================================"
echo
