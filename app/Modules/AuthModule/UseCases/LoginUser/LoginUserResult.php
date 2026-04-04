<?php

namespace App\Modules\AuthModule\UseCases\LoginUser;

use App\Modules\AuthModule\Models\User;

class LoginUserResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?User $user,
        public readonly ?string $token,
    ) {}

    public static function success(User $user, ?string $token = null): self
    {
        return new self(true, null, $user, $token);
    }

    public static function invalidCredentials(): self
    {
        return new self(false, 'invalid_credentials', null, null);
    }
}
