<?php

namespace App\Modules\PolicyModule\UseCases\DeletePolicy;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Models\EnforcementPolicy;

class DeletePolicy
{
    public function execute(User $actor, int $policyId): DeletePolicyResult
    {
        if (!$actor->hasRole('root')) {
            return DeletePolicyResult::forbidden();
        }

        $policy = EnforcementPolicy::find($policyId);

        if (!$policy) {
            return DeletePolicyResult::notFound();
        }

        $policy->delete();

        return DeletePolicyResult::success();
    }
}
