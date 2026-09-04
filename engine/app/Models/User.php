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
