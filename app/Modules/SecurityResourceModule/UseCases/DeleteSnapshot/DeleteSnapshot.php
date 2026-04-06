<?php

namespace App\Modules\SecurityResourceModule\UseCases\DeleteSnapshot;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\PolicyActions;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class DeleteSnapshot
{
    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
        private PolicyEnforcerInterface $policyEnforcer,
    ) {}

    public function execute(int $userId, string $vpsId, string $snapshotId, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): DeleteSnapshotResult
    {
        if (!$this->permissions->canManageSnapshots($userId, $vpsId)) {
            return DeleteSnapshotResult::forbidden();
        }

        $policy = $this->policyEnforcer->evaluate(PolicyActions::SNAPSHOT_DELETE, $userId, $vpsId);

        if (!$policy->allowed) {
            $this->auditLogger->logAction(
                action: 'snapshot_delete',
                actorId: $userId,
                actorEmail: $actorEmail,
                vpsId: $vpsId,
                resourceType: 'snapshot',
                resourceId: $snapshotId,
                correlationId: (string) Str::uuid(),
                outcome: 'policy_denied',
                metadata: ['reason' => $policy->reason],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return DeleteSnapshotResult::policyDenied($policy->reason);
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->deleteSnapshot($vpsId, $snapshotId, $correlationId);

        $this->auditLogger->logAction(
            action: 'snapshot_delete',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'snapshot',
            resourceId: $snapshotId,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : [],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return DeleteSnapshotResult::hostingerError($correlationId);
        }

        return DeleteSnapshotResult::success($correlationId);
    }
}
