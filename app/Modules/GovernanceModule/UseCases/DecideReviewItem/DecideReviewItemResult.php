<?php

namespace App\Modules\GovernanceModule\UseCases\DecideReviewItem;

use App\Modules\GovernanceModule\Models\AccessReviewItem;

class DecideReviewItemResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $error = '',
        public readonly ?AccessReviewItem $item = null,
    ) {}

    public static function success(AccessReviewItem $item): self
    {
        return new self(success: true, item: $item);
    }

    public static function forbidden(): self
    {
        return new self(success: false, error: 'forbidden');
    }

    public static function notFound(): self
    {
        return new self(success: false, error: 'not_found');
    }

    public static function invalidDecision(): self
    {
        return new self(success: false, error: 'invalid_decision');
    }
}
