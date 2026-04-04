<?php

namespace App\Modules\VpsModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiResult;
use App\Modules\VpsModule\UseCases\RebootVps\RebootVps;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RebootVpsTest extends TestCase
{
    private MockInterface $vps;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private RebootVps $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vps = Mockery::mock(VpsRepositoryInterface::class);
        $this->hostinger = Mockery::mock(HostingerApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new RebootVps($this->vps, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_reboots_vps_successfully_when_user_has_access(): void
    {
        $this->vps->shouldReceive('userHasAccess')->with(1, 'vps-123')->once()->andReturn(true);
        $this->hostinger->shouldReceive('rebootVps')->once()->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
        $this->assertNotNull($result->correlationId);
    }

    public function test_returns_forbidden_when_user_has_no_access(): void
    {
        $this->vps->shouldReceive('userHasAccess')->with(1, 'vps-123')->once()->andReturn(false);
        $this->hostinger->shouldNotReceive('rebootVps');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('rebootVps')->once()->andReturn(HostingerApiResult::failure('corr-id', 'API error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_on_success(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('rebootVps')->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'vps_reboot' && $outcome === 'success' && $resourceType === 'vps';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
    }

    public function test_logs_audit_on_failure(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('rebootVps')->andReturn(HostingerApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'vps_reboot' && $outcome === 'failure';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
    }
}
