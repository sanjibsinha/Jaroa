<?php

declare(strict_types=1);

namespace Jaroa\Services;

use InvalidArgumentException;
use Jaroa\Auth\AuthenticatedUser;
use Jaroa\Auth\TokenManager;
use Jaroa\Models\User;
use Jaroa\Repositories\AuthTokenRepository;
use Jaroa\Repositories\UserRepository;

final class AuthService
{
    private const TOKEN_TTL_HOURS = 24;

    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthTokenRepository $tokens,
        private readonly TokenManager $tokenManager,
    ) {
    }

    public function register(
        string $name,
        string $email,
        string $password
    ): User {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            throw new InvalidArgumentException(
                'Name is required.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'A valid email address is required.'
            );
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'Password must be at least 8 characters.'
            );
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new InvalidArgumentException(
                'An account with this email already exists.'
            );
        }

        $hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        return $this->users->create(
            $name,
            $email,
            $hash
        );
    }

    public function login(
        string $email,
        string $password
    ): array {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Invalid email or password.'
            );
        }

        $user = $this->users->findByEmail($email);

        if (
            $user === null ||
            !password_verify(
                $password,
                $user->passwordHash
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid email or password.'
            );
        }

        $token = $this->tokenManager->generate();

        $tokenHash = $this->tokenManager->hash($token);

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + (self::TOKEN_TTL_HOURS * 3600)
        );

        $tokenId = $this->tokens->create(
            $user->id,
            $tokenHash,
            $expiresAt
        );

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => $user,
            'token_id' => $tokenId,
        ];
    }

    public function authenticate(
        string $token
    ): ?AuthenticatedUser {
        if ($token === '') {
            return null;
        }

        $hash = $this->tokenManager->hash($token);

        $tokenRecord = $this->tokens->findValidByHash(
            $hash
        );

        if ($tokenRecord === null) {
            return null;
        }

        $user = $this->users->findById(
            (int) $tokenRecord['user_id']
        );

        if ($user === null) {
            return null;
        }

        return new AuthenticatedUser(
            $user,
            (int) $tokenRecord['id']
        );
    }

    public function logout(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        return $this->tokens->revokeByHash(
            $this->tokenManager->hash($token)
        );
    }
}
