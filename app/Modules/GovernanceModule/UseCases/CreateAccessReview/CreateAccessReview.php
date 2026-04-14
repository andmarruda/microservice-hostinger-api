<?php

namespace App\Modules\GovernanceModule\UseCases\CreateAccessReview;

use App\Modules\AuthModule\Models\User;
use App\Modules\GovernanceModule\Models\AccessReview;
use App\Modules\GovernanceModule\Models\AccessReviewItem;
use App\Modules\VpsModule\Models\VpsAccessGrant;

class CreateAccessReview
{
    public function execute(User $actor, string $periodStart, string $periodEnd): CreateAccessReviewResult
    {
        if (!$actor->hasRole('root')) {
            return CreateAccessReviewResult::forbidden();
        }

        $review = AccessReview::create([
            'reviewer_id'  => $actor->id,
            'status'       => 'pending',
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
        ]);

        // Populate items from all active access grants
        VpsAccessGrant::whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->chunkById(200, function ($grants) use ($review) {
                foreach ($grants as $grant) {
                    AccessReviewItem::create([
                        'review_id' => $review->id,
                        'user_id'   => $grant->user_id,
                        'vps_id'    => $grant->vps_id,
                    ]);
                }
            });

        return CreateAccessReviewResult::success($review);
    }
}
