<?php

namespace App\Modules\PolicyModule\UseCases\CreatePolicy;

class CreatePolicyResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?int $policyId,
    ) {}

    public static function success(int $policyId): self
    {
        return new self(true, null, $policyId);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }

    public static function invalidAction(string $action): self
    {
        return new self(false, 'invalid_action', null);
    }
}
