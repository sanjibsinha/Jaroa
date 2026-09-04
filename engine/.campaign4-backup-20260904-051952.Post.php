<?php

declare(strict_types=1);

namespace Jaroa\Models;

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $content,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }
}
