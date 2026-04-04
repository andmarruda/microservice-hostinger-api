<?php

namespace App\Modules\SecurityResourceModule\UseCases\RemoveFirewallRule;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class RemoveFirewallRule
{
    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
    ) {}

    public function execute(int $userId, string $vpsId, string $ruleId, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): RemoveFirewallRuleResult
    {
        if (!$this->permissions->canManageFirewall($userId, $vpsId)) {
            return RemoveFirewallRuleResult::forbidden();
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->removeFirewallRule($vpsId, $ruleId, $correlationId);

        $this->auditLogger->logAction(
            action: 'firewall_rule_remove',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'firewall',
            resourceId: $ruleId,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : [],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return RemoveFirewallRuleResult::hostingerError($correlationId);
        }

        return RemoveFirewallRuleResult::success($correlationId);
    }
}
