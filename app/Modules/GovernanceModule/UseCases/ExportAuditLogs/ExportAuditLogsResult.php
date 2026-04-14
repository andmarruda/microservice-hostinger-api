<?php

namespace App\Modules\GovernanceModule\UseCases\ExportAuditLogs;

class ExportAuditLogsResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?array $data,
        public readonly ?string $csv,
        public readonly string $format,
    ) {}

    public static function json(array $data): self
    {
        return new self(true, null, $data, null, 'json');
    }

    public static function csv(string $csv): self
    {
        return new self(true, null, null, $csv, 'csv');
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null, null, 'json');
    }
}
