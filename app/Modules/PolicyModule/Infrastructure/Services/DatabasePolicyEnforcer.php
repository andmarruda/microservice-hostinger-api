<?php

namespace App\Modules\PolicyModule\Infrastructure\Services;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Models\EnforcementPolicy;
use App\Modules\PolicyModule\Ports\Services\PolicyDecision;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;

class DatabasePolicyEnforcer implements PolicyEnforcerInterface
{
    public function evaluate(string $action, int $userId, ?string $vpsId = null): PolicyDecision
    {
        $user = User::find($userId);

        if (!$user) {
            return PolicyDecision::deny('User not found.');
        }

        // Root role is exempt from all policy rules
        if ($user->hasRole('root')) {
            return PolicyDecision::allow();
        }

        $roleNames = $user->getRoleNames()->toArray();

        $now = now();

        $denied = EnforcementPolicy::where('action', $action)
            ->where('effect', 'deny')
            ->where(function ($q) use ($userId, $vpsId, $roleNames) {
                $q->where('scope_type', 'global')
                    ->orWhere(function ($q2) use ($userId) {
                        $q2->where('scope_type', 'user')
                            ->where('scope_id', (string) $userId);
                    })
                    ->orWhere(function ($q2) use ($vpsId) {
                        if ($vpsId !== null) {
                            $q2->where('scope_type', 'vps')
                                ->where('scope_id', $vpsId);
                        }
                    })
                    ->orWhere(function ($q2) use ($roleNames) {
                        if (!empty($roleNames)) {
                            $q2->where('scope_type', 'role')
                                ->whereIn('scope_id', $roleNames);
                        }
                    });
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', $now);
            })
            ->first();

        if ($denied) {
            return PolicyDecision::deny($denied->reason ?? 'Operation denied by policy.');
        }

        return PolicyDecision::allow();
    }
}
