<?php

namespace App\Modules\VpsModule\Ports\Services;

class HostingerApiResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $correlationId,
        public readonly ?string $errorMessage,
    ) {}

    public static function success(string $correlationId): self
    {
        return new self(true, $correlationId, null);
    }

    public static function failure(string $correlationId, string $errorMessage): self
    {
        return new self(false, $correlationId, $errorMessage);
    }
}
