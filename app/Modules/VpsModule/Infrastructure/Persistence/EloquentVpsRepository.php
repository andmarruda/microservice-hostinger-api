<?php

namespace App\Modules\VpsModule\Infrastructure\Persistence;

use App\Modules\VpsModule\Models\VpsAccessGrant;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;

class EloquentVpsRepository implements VpsRepositoryInterface
{
    public function userHasAccess(int $userId, string $vpsId): bool
    {
        return VpsAccessGrant::where('user_id', $userId)
            ->where('vps_id', $vpsId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function findById(string $vpsId): ?object
    {
        $exists = VpsAccessGrant::where('vps_id', $vpsId)->exists();

        return $exists ? (object) ['vpsId' => $vpsId] : null;
    }

    public function findAllForUser(int $userId): array
    {
        return VpsAccessGrant::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('vps_id')
            ->all();
    }
}
