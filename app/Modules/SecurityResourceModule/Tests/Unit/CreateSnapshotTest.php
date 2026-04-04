<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\CreateSnapshot\CreateSnapshot;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateSnapshotTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private CreateSnapshot $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new CreateSnapshot($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_snapshot_successfully_when_user_has_permission(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('createSnapshot')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'pre-deploy-backup');

        $this->assertTrue($result->success);
    }

    public function test_returns_forbidden_when_user_lacks_snapshot_permission(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(false);
        $this->hostinger->shouldNotReceive('createSnapshot');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'pre-deploy-backup');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('createSnapshot')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'pre-deploy-backup');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_with_snapshot_resource_type(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('createSnapshot')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'snapshot_create' && $resourceType === 'snapshot' && $outcome === 'success';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', 'pre-deploy-backup');

        $this->assertTrue($result->success);
    }
}
