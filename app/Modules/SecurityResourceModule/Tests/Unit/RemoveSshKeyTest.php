<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\RemoveSshKey\RemoveSshKey;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RemoveSshKeyTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private RemoveSshKey $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new RemoveSshKey($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_removes_ssh_key_when_user_has_permission(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('removeSshKey')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'key-789');

        $this->assertTrue($result->success);
    }

    public function test_returns_forbidden_when_user_lacks_ssh_key_permission(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(false);
        $this->hostinger->shouldNotReceive('removeSshKey');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'key-789');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('removeSshKey')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'key-789');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }
}
