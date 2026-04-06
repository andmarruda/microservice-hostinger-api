<?php

namespace App\Modules\VpsModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\PolicyModule\Ports\Services\PolicyDecision;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiResult;
use App\Modules\VpsModule\UseCases\StopVps\StopVps;
use Mockery;
use Tests\TestCase;

class StopVpsTest extends TestCase
{
    private VpsRepositoryInterface $vpsRepo;
    private HostingerApiClientInterface $hostinger;
    private InfraAuditLoggerInterface $auditLogger;
    private PolicyEnforcerInterface $policyEnforcer;
    private StopVps $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vpsRepo        = Mockery::mock(VpsRepositoryInterface::class);
        $this->hostinger      = Mockery::mock(HostingerApiClientInterface::class);
        $this->auditLogger    = Mockery::mock(InfraAuditLoggerInterface::class);
        $this->policyEnforcer = Mockery::mock(PolicyEnforcerInterface::class);

        $this->useCase = new StopVps(
            $this->vpsRepo,
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

    public function test_returns_forbidden_when_user_has_no_access(): void
    {
        $this->vpsRepo->shouldReceive('userHasAccess')->andReturn(false);

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertSame('forbidden', $result->error);
    }

    public function test_returns_policy_denied_when_policy_blocks(): void
    {
        $this->vpsRepo->shouldReceive('userHasAccess')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::deny('Denied by policy.'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertSame('policy_denied', $result->error);
        $this->assertSame('Denied by policy.', $result->policyReason);
    }

    public function test_returns_success_when_hostinger_call_succeeds(): void
    {
        $this->vpsRepo->shouldReceive('userHasAccess')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::allow());
        $this->hostinger->shouldReceive('stopVps')->andReturn(HostingerApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertTrue($result->success);
    }

    public function test_returns_hostinger_error_when_api_fails(): void
    {
        $this->vpsRepo->shouldReceive('userHasAccess')->andReturn(true);
        $this->policyEnforcer->shouldReceive('evaluate')->andReturn(PolicyDecision::allow());
        $this->hostinger->shouldReceive('stopVps')->andReturn(HostingerApiResult::failure('corr-id', 'Error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123');

        $this->assertFalse($result->success);
        $this->assertSame('hostinger_error', $result->error);
    }
}
