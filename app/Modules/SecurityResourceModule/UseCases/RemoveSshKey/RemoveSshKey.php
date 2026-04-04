<?php

namespace App\Modules\SecurityResourceModule\UseCases\RemoveSshKey;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class RemoveSshKey
{
    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $vpsId, string $keyId, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): RemoveSshKeyResult
    {
        if (!$this->permissions->canManageSshKeys($userId, $vpsId)) {
            return RemoveSshKeyResult::forbidden();
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->removeSshKey($vpsId, $keyId, $correlationId);

        $this->auditLogger->logAction(
            action: 'ssh_key_remove',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'ssh_key',
            resourceId: $keyId,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : [],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return RemoveSshKeyResult::hostingerError($correlationId);
        }

        return RemoveSshKeyResult::success($correlationId);
    }
}
