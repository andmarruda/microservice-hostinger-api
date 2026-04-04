<?php

namespace App\Modules\SecurityResourceModule\UseCases\CreateSnapshot;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class CreateSnapshot
{
    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $vpsId, string $label, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): CreateSnapshotResult
    {
        if (!$this->permissions->canManageSnapshots($userId, $vpsId)) {
            return CreateSnapshotResult::forbidden();
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->createSnapshot($vpsId, $label, $correlationId);

        $this->auditLogger->logAction(
            action: 'snapshot_create',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'snapshot',
            resourceId: null,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : ['label' => $label],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return CreateSnapshotResult::hostingerError($correlationId);
        }

        return CreateSnapshotResult::success($correlationId);
    }
}
