<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\RemoveFirewallRule\RemoveFirewallRule;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RemoveFirewallRuleTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private RemoveFirewallRule $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new RemoveFirewallRule($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_removes_firewall_rule_when_user_has_permission(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('removeFirewallRule')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'rule-456');

        $this->assertTrue($result->success);
    }

    public function test_returns_forbidden_when_user_lacks_firewall_permission(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(false);
        $this->hostinger->shouldNotReceive('removeFirewallRule');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'rule-456');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('removeFirewallRule')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'rule-456');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_on_successful_removal(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('removeFirewallRule')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'firewall_rule_remove' && $resourceType === 'firewall' && $outcome === 'success';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', 'rule-456');

        $this->assertTrue($result->success);
    }
}
