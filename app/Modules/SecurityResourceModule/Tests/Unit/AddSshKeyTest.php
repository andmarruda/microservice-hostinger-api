<?php

namespace App\Modules\SecurityResourceModule\Tests\Unit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use App\Modules\SecurityResourceModule\UseCases\AddSshKey\AddSshKey;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AddSshKeyTest extends TestCase
{
    private MockInterface $permissions;
    private MockInterface $hostinger;
    private MockInterface $auditLogger;
    private AddSshKey $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissions = Mockery::mock(SecurityPermissionInterface::class);
        $this->hostinger = Mockery::mock(HostingerSecurityApiClientInterface::class);
        $this->auditLogger = Mockery::mock(InfraAuditLoggerInterface::class);

        $this->useCase = new AddSshKey($this->permissions, $this->hostinger, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_adds_ssh_key_successfully_with_valid_rsa_key(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('addSshKey')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'ssh-rsa AAAAB3NzaC1yc2E...');

        $this->assertTrue($result->success);
    }

    public function test_adds_ssh_key_successfully_with_valid_ed25519_key(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('addSshKey')->once()->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5...');

        $this->assertTrue($result->success);
    }

    public function test_returns_forbidden_when_user_lacks_ssh_key_permission(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(false);
        $this->hostinger->shouldNotReceive('addSshKey');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'ssh-rsa AAAAB3NzaC1yc2E...');

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_returns_invalid_key_when_key_format_is_invalid(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldNotReceive('addSshKey');
        $this->auditLogger->shouldNotReceive('logAction');

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'invalid-key-format');

        $this->assertFalse($result->success);
        $this->assertEquals('invalid_key', $result->error);
        $this->assertNotNull($result->validationMessage);
    }

    public function test_returns_hostinger_error_when_api_call_fails(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('addSshKey')->andReturn(HostingerSecurityApiResult::failure('corr-id', 'error'));
        $this->auditLogger->shouldReceive('logAction')->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'ssh-rsa AAAAB3NzaC1yc2E...');

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }

    public function test_logs_audit_with_ssh_key_resource_type(): void
    {
        $this->permissions->shouldReceive('canManageSshKeys')->andReturn(true);
        $this->hostinger->shouldReceive('addSshKey')->andReturn(HostingerSecurityApiResult::success('corr-id'));
        $this->auditLogger->shouldReceive('logAction')
            ->withArgs(function ($action, $actorId, $actorEmail, $vpsId, $resourceType, $resourceId, $correlationId, $outcome) {
                return $action === 'ssh_key_add' && $resourceType === 'ssh_key' && $outcome === 'success';
            })
            ->once();

        $result = $this->useCase->execute(1, 'vps-123', 'my-key', 'ssh-rsa AAAAB3NzaC1yc2E...');

        $this->assertTrue($result->success);
    }
}
