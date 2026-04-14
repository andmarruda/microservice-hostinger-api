<?php

namespace App\Modules\DriftModule\UseCases\ListDriftReports;

use Illuminate\Database\Eloquent\Collection;

class ListDriftReportsResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?Collection $reports,
    ) {}

    public static function success(Collection $reports): self
    {
        return new self(true, null, $reports);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }
}
