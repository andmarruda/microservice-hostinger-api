<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\DeleteSnapshot\DeleteSnapshot;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteSnapshotTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private DeleteSnapshot $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new DeleteSnapshot($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deletes_snapshot_when_user_has_permission(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('deleteSnapshot')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'snap-999');

        $this->assertTrue($result->success);
    }

    public function test_returns_forbidden_when_user_lacks_snapshot_permission(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(false);
        $this->hostinger->shouldNotReceive('deleteSnapshot');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'snap-999');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('deleteSnapshot')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'snap-999');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_on_deletion(): void
    {
        $this->permissions->shouldReceive('canManageSnapshots')->andReturn(true);
        $this->hostinger->shouldReceive('deleteSnapshot')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'snapshot_delete' && $resourceType === 'snapshot' && $outcome === 'success';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', 'snap-999');

        $this->assertTrue($result->success);
    }
}
