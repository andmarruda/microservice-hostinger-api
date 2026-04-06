<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\Ports\Services\PolicyDecision;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\AddFirewallRule\AddFirewallRule;
use Mockery;
use Tests\TestCase;

class AddFirewallRuleTest extends TestCase
{
    private SecurityPermissionInterface $permissions;
    private HostingerSecurityApiClientInterface $hostinger;
    private InfraAuditLoggerInterface $auditLogger;
    private PolicyEnforcerInterface $policyEnforcer;
    private AddFirewallRule $useCase;

    private array $validRule = ['protocol' => 'tcp', 'port' => 80, 'source' => null];

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions    = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger      = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger    = Mockery::mock(InfraAuditLoggerInterface::class);
        $this->policyEnforcer = Mockery::mock(PolicyEnforcerInterface::class);

        $this->useCase = new AddFirewallRule(
            $this->permissions,
            $this->hostinger,
            $this->auditLogger,
            $this->policyEnforcer,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_forbidden_when_no_permission(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(false);

        $result = $this->useCase->execute(1, 'vps-123', $this->validRule);

        $this->assertFalse($result->success);
        $this->assertSame('forbidden', $result->error);
    }

    public function test_returns_invalid_rule_when_rule_is_malformed(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);

        $result = $this->useCase->execute(1, 'vps-123', ['source' => 'all']);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_rule', $result->error);
    }

    public function test_returns_policy_denied_when_policy_blocks(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::deny('No new rules allowed.'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', $this->validRule);

        $this->assertFalse($result->success);
        $this->assertSame('policy_denied', $result->error);
        $this->assertSame('No new rules allowed.', $result->policyReason);
    }

    public function test_returns_success_on_happy_path(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::allow());
        $this->hostinger->shouldReceive('addFirewallRule')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', $this->validRule);

        $this->assertTrue($result->success);
    }
}
