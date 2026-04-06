<?php

namespace App\Modules\VpsModule\UseCases\RebootVps;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\PolicyActions;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use Illuminate\Support\Str;

class RebootVps
{
    public function __construct(
        private VpsRepositoryInterface $vps,
        private HostingerApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
        private PolicyEnforcerInterface $policyEnforcer,
    ) {}

    public function execute(int $userId, string $vpsId, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): RebootVpsResult
    {
        if (!$this->vps->userHasAccess($userId, $vpsId)) {
            return RebootVpsResult::forbidden();
        }

        $policy = $this->policyEnforcer->evaluate(PolicyActions::VPS_REBOOT, $userId, $vpsId);

        if (!$policy->allowed) {
            $this->auditLogger->logAction(
                action: 'vps_reboot',
                actorId: $userId,
                actorEmail: $actorEmail,
                vpsId: $vpsId,
                resourceType: 'vps',
                resourceId: null,
                correlationId: (string) Str::uuid(),
                outcome: 'policy_denied',
                metadata: ['reason' => $policy->reason],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return RebootVpsResult::policyDenied($policy->reason);
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->rebootVps($vpsId, $correlationId);

        $this->auditLogger->logAction(
            action: 'vps_reboot',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'vps',
            resourceId: null,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : [],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return RebootVpsResult::hostingerError($correlationId);
        }

        return RebootVpsResult::success($correlationId);
    }
}
