#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo
echo "========================================"
echo " JAROA CAMPAIGN 3 SURGICAL PATCH"
echo "========================================"
echo

if [[ "$(pwd)" != "$HOME/Jaroa/engine" ]]; then
    echo "ERROR: Unexpected project directory."
    exit 1
fi

BACKUP=".campaign3-patch-backup-$(date +%Y%m%d-%H%M%S)"

echo "1. Creating safety backup..."
mkdir "$BACKUP"

cp app/Services/AuthService.php "$BACKUP/"
cp app/Middleware/AuthenticationMiddleware.php "$BACKUP/"
cp app/Repositories/AuthTokenRepository.php "$BACKUP/"
cp app/Providers/ControllerProvider.php "$BACKUP/"
cp routes/api.php "$BACKUP/"

echo "   Backup: $BACKUP"
echo

patch_file() {
    local file="$1"
    local old="$2"
    local new="$3"

    OLD="$old" NEW="$new" FILE="$file" perl -0pi -e '
        my $old = $ENV{OLD};
        my $new = $ENV{NEW};
        my $file = $ENV{FILE};

        my $count = () = / \Q$old\E /gx;

        die "ABORT: expected exactly one match in $file, found $count\n"
            unless $count == 1;

        s/\Q$old\E/$new/;
    ' "$file"
}

echo "2. Fixing AuthenticationMiddleware..."

patch_file \
    "app/Middleware/AuthenticationMiddleware.php" \
    '        $authenticated = $this->auth->authenticateToken(
            $token
        );' \
    '        $authenticated = $this->auth->authenticate(
            $token
        );'

echo "   Authentication middleware fixed."
echo

echo "3. Adding revokeAllForUser()..."

patch_file \
    "app/Repositories/AuthTokenRepository.php" \
    '    public function revokeByHash(
        string $tokenHash
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'SQL'
UPDATE auth_tokens
SET revoked_at = CURRENT_TIMESTAMP
WHERE token_hash = :token_hash
  AND revoked_at IS NULL
SQL
        );

        $statement->execute([
            '\''token_hash'\'' => $tokenHash,
        ]);

        return $statement->rowCount() > 0;
    }
}' \
    '    public function revokeByHash(
        string $tokenHash
    ): bool {
        $statement = $this->pdo->prepare(
            <<<'\''SQL'\''
UPDATE auth_tokens
SET revoked_at = CURRENT_TIMESTAMP
WHERE token_hash = :token_hash
  AND revoked_at IS NULL
SQL
        );

        $statement->execute([
            '\''token_hash'\'' => $tokenHash,
        ]);

        return $statement->rowCount() > 0;
    }

    public function revokeAllForUser(int $userId): int
    {
        $statement = $this->pdo->prepare(
            <<<'\''SQL'\''
UPDATE auth_tokens
SET revoked_at = CURRENT_TIMESTAMP
WHERE user_id = :user_id
  AND revoked_at IS NULL
SQL
        );

        $statement->execute([
            '\''user_id'\'' => $userId,
        ]);

        return $statement->rowCount();
    }
}'

echo "   Token revocation added."
echo

echo "4. Adding password-change support to AuthService..."

patch_file \
    "app/Services/AuthService.php" \
    '    public function logout(string $token): bool
    {
        if ($token === '\'\'') {
            return false;
        }

        return $this->tokens->revokeByHash(
            $this->tokenManager->hash($token)
        );
    }
}' \
    '    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): User {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                '\''User ID must be a positive integer.'\''
            );
        }

        if ($currentPassword === '\'\'') {
            throw new InvalidArgumentException(
                '\''Current password is required.'\''
            );
        }

        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException(
                '\''New password must be at least 8 characters.'\''
            );
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException(
                '\''User not found.'\''
            );
        }

        if (!password_verify($currentPassword, $user->passwordHash)) {
            throw new InvalidArgumentException(
                '\''Current password is incorrect.'\''
            );
        }

        $hash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($hash === false) {
            throw new InvalidArgumentException(
                '\''Unable to securely hash the new password.'\''
            );
        }

        if (!$this->users->updatePasswordHash($userId, $hash)) {
            throw new InvalidArgumentException(
                '\''Password could not be updated.'\''
            );
        }

        $this->tokens->revokeAllForUser($userId);

        $updated = $this->users->findById($userId);

        if ($updated === null) {
            throw new InvalidArgumentException(
                '\''User not found.'\''
            );
        }

        return $updated;
    }

    public function logout(string $token): bool
    {
        if ($token === '\'\'') {
            return false;
        }

        return $this->tokens->revokeByHash(
            $this->tokenManager->hash($token)
        );
    }
}'

echo "   Password change added."
echo

echo "5. Importing UserController into ControllerProvider..."

patch_file \
    "app/Providers/ControllerProvider.php" \
    'use Jaroa\Controllers\AuthController;
use Jaroa\Controllers\PostsController;' \
    'use Jaroa\Controllers\AuthController;
use Jaroa\Controllers\PostsController;
use Jaroa\Controllers\UserController;'

patch_file \
    "app/Providers/ControllerProvider.php" \
    'use Jaroa\Services\AuthService;
use Jaroa\Services\PostService;' \
    'use Jaroa\Services\AuthService;
use Jaroa\Services\PostService;
use Jaroa\Services\UserService;'

echo "   Imports added."
echo

echo "6. Adding users() provider..."

patch_file \
    "app/Providers/ControllerProvider.php" \
    '    public function authenticationMiddleware(): AuthenticationMiddleware
    {' \
    '    public function users(): UserController
    {
        $userRepository = new UserRepository(
            $this->pdo
        );

        $userService = new UserService(
            $userRepository
        );

        $authService = new AuthService(
            $userRepository,
            new AuthTokenRepository($this->pdo),
            new TokenManager()
        );

        return new UserController(
            $userService,
            $authService
        );
    }

    public function authenticationMiddleware(): AuthenticationMiddleware
    {'

echo "   UserController provider added."
echo

echo "7. Adding User Management routes..."

patch_file \
    "routes/api.php" \
    '    /*
     * Public post endpoints.
     */' \
    '    /*
     * User Management endpoints.
     */

    $userController = $controllerProvider->users();

    $router->get(
        '\''/api/v1/users/{id}'\'',
        static function (array $parameters) use (
            $userController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $userId = (int) $parameters['\''id'\''];

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    $userId
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $userController->show(
                $userId,
                $authenticated
            );
        }
    );

    $router->put(
        '\''/api/v1/users/{id}'\'',
        static function (array $parameters) use (
            $userController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $userId = (int) $parameters['\''id'\''];

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    $userId
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $userController->update(
                $userId,
                $request
            );
        }
    );

    $router->delete(
        '\''/api/v1/users/{id}'\'',
        static function (array $parameters) use (
            $userController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $userId = (int) $parameters['\''id'\''];

            if (
                $authenticated->user->role === '\''admin'\'' &&
                $authenticated->user->id === $userId
            ) {
                return new JsonResponse(
                    [
                        '\''error'\'' => [
                            '\''code'\'' => '\''forbidden'\'',
                            '\''message'\'' => '\''Administrators cannot delete their own account.'\'',
                        ],
                    ],
                    403
                );
            }

            $forbidden =
                $authorizationMiddleware->authorizeOwnerOrAdmin(
                    $authenticated,
                    $userId
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $userController->delete(
                $userId
            );
        }
    );

    $router->post(
        '\''/api/v1/users/{id}/password'\'',
        static function (array $parameters) use (
            $userController,
            $authenticationMiddleware,
            $authorizationMiddleware
        ): mixed {
            $request = new Request();

            $authenticated =
                $authenticationMiddleware->authenticate(
                    $request
                );

            if ($authenticated instanceof JsonResponse) {
                return $authenticated;
            }

            $userId = (int) $parameters['\''id'\''];

            $forbidden =
                $authorizationMiddleware->authorizeSelf(
                    $authenticated,
                    $userId
                );

            if ($forbidden instanceof JsonResponse) {
                return $forbidden;
            }

            return $userController->changePassword(
                $userId,
                $request
            );
        }
    );

    /*
     * Public post endpoints.
     */'

echo "   User Management routes added."
echo

echo "8. Running PHP syntax checks..."

FILES=(
    app/Services/AuthService.php
    app/Middleware/AuthenticationMiddleware.php
    app/Middleware/AuthorizationMiddleware.php
    app/Repositories/AuthTokenRepository.php
    app/Repositories/UserRepository.php
    app/Services/UserService.php
    app/Controllers/UserController.php
    app/Providers/ControllerProvider.php
    routes/api.php
)

for file in "${FILES[@]}"; do
    php -l "$file"
done

echo
echo "9. Refreshing Composer autoload through DDEV..."

ddev composer dump-autoload --no-interaction

echo
echo "10. Checking Composer autoload..."

ddev exec php -r 'require "vendor/autoload.php"; foreach (["Jaroa\\Services\\AuthService","Jaroa\\Repositories\\AuthTokenRepository","Jaroa\\Controllers\\UserController","Jaroa\\Services\\UserService"] as $class) { if (!class_exists($class)) { fwrite(STDERR, "Missing class: {$class}\n"); exit(1); } } echo "Composer autoload OK\n";'

echo
echo "========================================"
echo " CAMPAIGN 3 SURGICAL PATCH PASSED"
echo "========================================"
echo
echo "Backup: $BACKUP"
echo
