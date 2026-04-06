<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\Ports\Services\PolicyDecision;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\CreateSnapshot\CreateSnapshot;
use Mockery;
use Tests\TestCase;

class CreateSnapshotTest extends TestCase
{
    private SecurityPermissionInterface $permissions;
    private HostingerSecurityApiClientInterface $hostinger;
    private InfraAuditLoggerInterface $auditLogger;
    private PolicyEnforcerInterface $policyEnforcer;
    private CreateSnapshot $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions    = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger      = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger    = Mockery::mock(InfraAuditLoggerInterface::class);
        $this->policyEnforcer = Mockery::mock(PolicyEnforcerInterface::class);

        $this->useCase = new CreateSnapshot(
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
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(false);

        $result = $this->useCase->execute(1, 'vps-123', 'my-snap');

        $this->assertFalse($result->success);
        $this->assertSame('forbidden', $result->error);
    }

    public function test_returns_policy_denied_when_policy_blocks(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::deny('Snapshots disabled.'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-snap');

        $this->assertFalse($result->success);
        $this->assertSame('policy_denied', $result->error);
        $this->assertSame('Snapshots disabled.', $result->policyReason);
    }

    public function test_returns_success_on_happy_path(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::allow());
        $this->hostinger->shouldReceive('createSnapshot')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-snap');

        $this->assertTrue($result->success);
    }
}
