<?php

namespace App\Modules\SecurityResourceModule\Infrastructure\Services;

use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;

class EloquentSecurityPermissionService implements SecurityPermissionInterface
{
    public function canManageFirewall(int $userId, string $vpsId): bool
    {
        $record = SecurityPermission::where('user_id', $userId)
            ->where('vps_id', $vpsId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return (bool) $record?->can_manage_firewall;
    }

    public function canManageSshKeys(int $userId, string $vpsId): bool
    {
        $record = SecurityPermission::where('user_id', $userId)
            ->where('vps_id', $vpsId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return (bool) $record?->can_manage_ssh_keys;
    }

    public function canManageSnapshots(int $userId, string $vpsId): bool
    {
        $record = SecurityPermission::where('user_id', $userId)
            ->where('vps_id', $vpsId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return (bool) $record?->can_manage_snapshots;
    }
}
