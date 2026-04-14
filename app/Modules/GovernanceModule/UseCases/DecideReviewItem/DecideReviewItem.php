<?php

namespace App\Modules\GovernanceModule\UseCases\DecideReviewItem;

use App\Modules\AuthModule\Models\User;
use App\Modules\GovernanceModule\Models\AccessReview;
use App\Modules\GovernanceModule\Models\AccessReviewItem;
use App\Modules\VpsModule\Models\VpsAccessGrant;

class DecideReviewItem
{
    private const VALID_DECISIONS = ['approved', 'revoked'];

    public function execute(User $actor, int $reviewId, int $itemId, string $decision): DecideReviewItemResult
    {
        if (!$actor->hasRole('root')) {
            return DecideReviewItemResult::forbidden();
        }

        if (!in_array($decision, self::VALID_DECISIONS, true)) {
            return DecideReviewItemResult::invalidDecision();
        }

        $review = AccessReview::find($reviewId);
        if (!$review) {
            return DecideReviewItemResult::notFound();
        }

        $item = AccessReviewItem::where('id', $itemId)
            ->where('review_id', $reviewId)
            ->first();

        if (!$item) {
            return DecideReviewItemResult::notFound();
        }

        $item->update([
            'decision'   => $decision,
            'decided_at' => now(),
            'decided_by' => $actor->id,
        ]);

        // ADR-014: if revoked, delete the corresponding VPS access grant
        if ($decision === 'revoked') {
            VpsAccessGrant::where('user_id', $item->user_id)
                ->where('vps_id', $item->vps_id)
                ->delete();
        }

        return DecideReviewItemResult::success($item->fresh());
    }
}
