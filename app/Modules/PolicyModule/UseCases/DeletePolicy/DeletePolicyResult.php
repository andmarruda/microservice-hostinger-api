<?php

namespace App\Modules\PolicyModule\UseCases\DeletePolicy;

class DeletePolicyResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden');
    }

    public static function notFound(): self
    {
        return new self(false, 'not_found');
    }
}
