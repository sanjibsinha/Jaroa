<?php

namespace App\Admin;

use App\Api\ApiClient;
use RuntimeException;

final class AdminAuth
{
    private const SESSION_TOKEN = 'jaroa_admin_token';
    private const SESSION_USER = 'jaroa_admin_user';

    public function __construct(
        private readonly ApiClient $api
    ) {
    }

    public function login(
        string $email,
        string $password
    ): array {
        $this->startSession();

        $response = $this->api->post(
            '/auth/login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        $token = $this->findString(
            $response,
            [
                'token',
                'access_token',
            ]
        );

        if ($token === null || $token === '') {
            throw new RuntimeException(
                'Authentication token was not returned by the Engine.'
            );
        }

        $user = $this->findArray(
            $response,
            ['user']
        );

        if ($user === null) {
            $me = $this->api->get(
                '/auth/me',
                [],
                [
                    'Authorization' => 'Bearer ' . $token,
                ]
            );

            $user = $this->unwrapData($me);
        }

        if (!is_array($user)) {
            throw new RuntimeException(
                'Authenticated user data was not returned by the Engine.'
            );
        }

        session_regenerate_id(true);

        $_SESSION[self::SESSION_TOKEN] = $token;
        $_SESSION[self::SESSION_USER] = $user;

        return $user;
    }

    public function logout(): void
    {
        $this->startSession();

        $token = $_SESSION[self::SESSION_TOKEN] ?? null;

        if (is_string($token) && $token !== '') {
            try {
                $this->api->post(
                    '/auth/logout',
                    [],
                    [
                        'Authorization' => 'Bearer ' . $token,
                    ]
                );
            } catch (\Throwable) {
                // Local session logout should still succeed.
            }
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        $this->startSession();

        return isset(
            $_SESSION[self::SESSION_TOKEN]
        );
    }

    public function currentUser(): ?array
    {
        $this->startSession();

        $user = $_SESSION[self::SESSION_USER] ?? null;

        return is_array($user) ? $user : null;
    }

    public function requireRole(
        array $allowedRoles
    ): array {
        if (!$this->isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }

        $user = $this->currentUser();

        if ($user === null) {
            $this->logout();

            header('Location: /admin/login');
            exit;
        }

        $role = $user['role'] ?? '';

        if (
            !is_string($role) ||
            !in_array($role, $allowedRoles, true)
        ) {
            http_response_code(403);

            throw new RuntimeException(
                'You do not have permission to access the admin area.'
            );
        }

        return $user;
    }

    public function token(): ?string
    {
        $this->startSession();

        $token = $_SESSION[self::SESSION_TOKEN] ?? null;

        return is_string($token) ? $token : null;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private function findString(
        array $data,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            if (
                isset($data[$key]) &&
                is_string($data[$key])
            ) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findString(
                    $value,
                    $keys
                );

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function findArray(
        array $data,
        array $keys
    ): ?array {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findArray(
                    $value,
                    $keys
                );

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function unwrapData(
        array $response
    ): mixed {
        return $response['data'] ?? $response;
    }
}
