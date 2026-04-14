<?php

namespace App\Modules\GovernanceModule\UseCases\CreateAccessReview;

use App\Modules\GovernanceModule\Models\AccessReview;

class CreateAccessReviewResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?AccessReview $review,
    ) {}

    public static function success(AccessReview $review): self
    {
        return new self(true, null, $review);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }
}
