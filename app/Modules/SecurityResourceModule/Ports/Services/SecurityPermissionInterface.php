<?php

namespace App\Modules\SecurityResourceModule\Ports\Services;

interface SecurityPermissionInterface
{
    public function canManageFirewall(int $userId, string $vpsId): bool;

    public function canManageSshKeys(int $userId, string $vpsId): bool;

    public function canManageSnapshots(int $userId, string $vpsId): bool;
}
