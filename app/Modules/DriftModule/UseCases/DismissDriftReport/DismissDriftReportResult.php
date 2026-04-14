<?php

namespace App\Modules\DriftModule\UseCases\DismissDriftReport;

class DismissDriftReportResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?string $currentStatus = null,
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

    public static function alreadyClosed(string $status): self
    {
        return new self(false, 'already_closed', $status);
    }
}
