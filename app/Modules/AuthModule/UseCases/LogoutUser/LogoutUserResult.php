<?php

namespace App\Modules\AuthModule\UseCases\LogoutUser;

class LogoutUserResult
{
    private function __construct(
        public readonly bool $success,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }
}
