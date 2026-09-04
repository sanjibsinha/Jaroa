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
