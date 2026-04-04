<?php

namespace App\Modules\VpsModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiResult;
use App\Modules\VpsModule\UseCases\StartVps\StartVps;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class StartVpsTest extends TestCase
{
    private MockInterface $vps;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private StartVps $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vps = Mockery::mock(VpsRepositoryInterface::class);
        $this->hostinger = Mockery::mock(HostingerApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new StartVps($this->vps, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_starts_vps_successfully_when_user_has_access(): void
    {
        $this->vps->shouldReceive('userHasAccess')->with(1, 'vps-123')->once()->andReturn(true);
        $this->hostinger->shouldReceive('startVps')->once()->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
        $this->assertNotNull($result->correlationId);
    }

    public function test_returns_forbidden_when_user_has_no_access(): void
    {
        $this->vps->shouldReceive('userHasAccess')->with(1, 'vps-123')->once()->andReturn(false);
        $this->hostinger->shouldNotReceive('startVps');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('startVps')->once()->andReturn(HostingerApiResult::failure('corr-id', 'API error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
        $this->assertNotNull($result->correlationId);
    }

    public function test_logs_audit_on_success(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('startVps')->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'vps_start' && $outcome === 'success' && $resourceType === 'vps';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
    }

    public function test_logs_audit_on_failure(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('startVps')->andReturn(HostingerApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'vps_start' && $outcome === 'failure';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
    }

    public function test_correlation_id_is_passed_to_hostinger(): void
    {
        $this->vps->shouldReceive('userHasAccess')->andReturn(true);
        $this->hostinger->shouldReceive('startVps')
            ->withArgs(function ($vpsId, $correlationId) {
                return $vpsId === 'vps-123' && strlen($correlationId) > 0;
            })
            ->once()
            ->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
    }
}
