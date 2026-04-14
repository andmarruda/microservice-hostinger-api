<?php

namespace App\Modules\GovernanceModule\UseCases\ListAccessReviews;

use Illuminate\Support\Collection;

class ListAccessReviewsResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $error,
        public readonly ?Collection $reviews,
    ) {}

    public static function success(Collection $reviews): self
    {
        return new self(true, null, $reviews);
    }

    public static function forbidden(): self
    {
        return new self(false, 'forbidden', null);
    }
}
