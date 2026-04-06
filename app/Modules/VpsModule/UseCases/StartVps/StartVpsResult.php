<?php

namespace App\Modules\VpsModule\UseCases\StartVps;

class StartVpsResult
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

    public static function vpsNotFound(): self
    {
        return new self(false, 'vps_not_found', null);
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
