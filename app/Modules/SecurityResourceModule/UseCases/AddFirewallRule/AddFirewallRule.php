<?php

namespace App\Modules\SecurityResourceModule\UseCases\AddFirewallRule;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\PolicyActions;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\Str;

class AddFirewallRule
{
    public function __construct(
        private SecurityPermissionInterface $permissions,
        private HostingerSecurityApiClientInterface $hostinger,
        private InfraAuditLoggerInterface $auditLogger,
        private PolicyEnforcerInterface $policyEnforcer,
    ) {}

    public function execute(int $userId, string $vpsId, array $rule, ?string $actorEmail = null, ?string $ipAddress = null, ?string $userAgent = null): AddFirewallRuleResult
    {
        if (!$this->permissions->canManageFirewall($userId, $vpsId)) {
            return AddFirewallRuleResult::forbidden();
        }

        if (empty($rule['protocol']) || !isset($rule['port'])) {
            return AddFirewallRuleResult::invalidRule('Rule must contain protocol and port.');
        }

        $policy = $this->policyEnforcer->evaluate(PolicyActions::FIREWALL_ADD, $userId, $vpsId);

        if (!$policy->allowed) {
            $this->auditLogger->logAction(
                action: 'firewall_rule_add',
                actorId: $userId,
                actorEmail: $actorEmail,
                vpsId: $vpsId,
                resourceType: 'firewall',
                resourceId: null,
                correlationId: (string) Str::uuid(),
                outcome: 'policy_denied',
                metadata: ['reason' => $policy->reason],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return AddFirewallRuleResult::policyDenied($policy->reason);
        }

        $correlationId = (string) Str::uuid();

        $apiResult = $this->hostinger->addFirewallRule($vpsId, $rule, $correlationId);

        $this->auditLogger->logAction(
            action: 'firewall_rule_add',
            actorId: $userId,
            actorEmail: $actorEmail,
            vpsId: $vpsId,
            resourceType: 'firewall',
            resourceId: null,
            correlationId: $correlationId,
            outcome: $apiResult->success ? 'success' : 'failure',
            metadata: $apiResult->errorMessage ? ['error' => $apiResult->errorMessage] : ['rule' => $rule],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        if (!$apiResult->success) {
            return AddFirewallRuleResult::hostingerError($correlationId);
        }

        return AddFirewallRuleResult::success($correlationId);
    }
}
