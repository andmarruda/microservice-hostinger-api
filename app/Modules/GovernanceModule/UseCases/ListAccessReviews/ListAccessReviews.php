<?php

namespace App\Modules\GovernanceModule\UseCases\ListAccessReviews;

use App\Modules\AuthModule\Models\User;
use App\Modules\GovernanceModule\Models\AccessReview;

class ListAccessReviews
{
    public function execute(User $actor, array $filters = []): ListAccessReviewsResult
    {
        if (!$actor->hasRole('root')) {
            return ListAccessReviewsResult::forbidden();
        }

        $query = AccessReview::with('reviewer')->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return ListAccessReviewsResult::success($query->get());
    }
}
