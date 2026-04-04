<?php

namespace App\Modules\SecurityResourceModule\Ports\Services;

interface HostingerSecurityApiClientInterface
{
    public function addFirewallRule(string $vpsId, array $rule, string $correlationId): HostingerSecurityApiResult;

    public function removeFirewallRule(string $vpsId, string $ruleId, string $correlationId): HostingerSecurityApiResult;

    public function addSshKey(string $vpsId, string $keyName, string $publicKey, string $correlationId): HostingerSecurityApiResult;

    public function removeSshKey(string $vpsId, string $keyId, string $correlationId): HostingerSecurityApiResult;

    public function createSnapshot(string $vpsId, string $label, string $correlationId): HostingerSecurityApiResult;

    public function deleteSnapshot(string $vpsId, string $snapshotId, string $correlationId): HostingerSecurityApiResult;
}
