<?php

namespace App\Modules\GovernanceModule\UseCases\ApprovePermission;

use App\Modules\AuthModule\Models\User;
use App\Modules\GovernanceModule\Models\PermissionApproval;

class ApprovePermission
{
    public function execute(User $actor, int $approvalId): ApprovePermissionResult
    {
        if (!$actor->hasRole('root')) {
            return ApprovePermissionResult::forbidden();
        }

        $approval = PermissionApproval::find($approvalId);
        if (!$approval) {
            return ApprovePermissionResult::notFound();
        }

        // ADR-014: two-party control — approver must differ from requester
        if ($approval->requester_id === $actor->id) {
            return ApprovePermissionResult::selfApprove();
        }

        $approval->update([
            'status'      => 'approved',
            'approved_by' => $actor->id,
            'decided_at'  => now(),
        ]);

        return ApprovePermissionResult::success($approval->fresh());
    }
}
