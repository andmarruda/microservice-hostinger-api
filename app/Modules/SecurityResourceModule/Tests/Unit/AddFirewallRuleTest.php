<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\AddFirewallRule\AddFirewallRule;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AddFirewallRuleTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private AddFirewallRule $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new AddFirewallRule($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_adds_firewall_rule_successfully_when_user_has_permission(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('addFirewallRule')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp', 'port' => 80]);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->correlationId);
    }

    public function test_returns_forbidden_when_user_lacks_firewall_permission(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(false);
        $this->hostinger->shouldNotReceive('addFirewallRule');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp', 'port' => 80]);

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_invalid_rule_when_rule_missing_protocol(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldNotReceive('addFirewallRule');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', ['port' => 80]);

        $this->assertFalse($result->success);
        $this->assertEquals('invalid_rule', $result->error);
        $this->assertNotNull($result->validationMessage);
    }

    public function test_returns_invalid_rule_when_rule_missing_port(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldNotReceive('addFirewallRule');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp']);

        $this->assertFalse($result->success);
        $this->assertEquals('invalid_rule', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('addFirewallRule')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp', 'port' => 80]);

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_with_firewall_resource_type_on_success(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('addFirewallRule')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'firewall_rule_add' && $resourceType === 'firewall' && $outcome === 'success';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp', 'port' => 80]);

        $this->assertTrue($result->success);
    }

    public function test_logs_audit_on_failure(): void
    {
        $this->permissions->shouldReceive('canManageFirewall')->andReturn(true);
        $this->hostinger->shouldReceive('addFirewallRule')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'firewall_rule_add' && $outcome === 'failure';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', ['protocol' => 'tcp', 'port' => 80]);

        $this->assertFalse($result->success);
    }
}
