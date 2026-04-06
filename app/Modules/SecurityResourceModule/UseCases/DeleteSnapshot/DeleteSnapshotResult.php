<?php

namespace App\Modules\SecurityResourceModule\UseCases\DeleteSnapshot;

class DeleteSnapshotResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?string $correlationId,
        public readonly ?string $policyReason = null,
    ) {}

    public static function success(string $correlationId): self
    {
        return new self(true, null, $correlationId);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }

    public static function hostingerError(string $correlationId): self
    {
        return new self(false, 'hostinger_error', $correlationId);
    }

    public static function policyDenied(string $reason): self
    {
        return new self(false, 'policy_denied', null, $reason);
    }
}
