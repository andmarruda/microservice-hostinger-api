<?php

namespace App\Modules\PolicyModule\UseCases\CreatePolicy;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Models\EnforcementPolicy;
use App\Modules\PolicyModule\PolicyActions;

class CreatePolicy
{
    public function execute(User $actor, array $data): CreatePolicyResult
    {
        if (!$actor->hasRole('root')) {
            return CreatePolicyResult::forbidden();
        }

        if (!in_array($data['action'], PolicyActions::ALL, true)) {
            return CreatePolicyResult::invalidAction($data['action']);
        }

        $policy = EnforcementPolicy::create([
            'action'       => $data['action'],
            'scope_type'   => $data['scope_type'] ?? 'global',
            'scope_id'     => $data['scope_id'] ?? null,
            'effect'       => 'deny',
            'reason'       => $data['reason'] ?? null,
            'active_from'  => $data['active_from'] ?? null,
            'active_until' => $data['active_until'] ?? null,
            'created_by'   => $actor->id,
        ]);

        return CreatePolicyResult::success($policy->id);
    }
}
