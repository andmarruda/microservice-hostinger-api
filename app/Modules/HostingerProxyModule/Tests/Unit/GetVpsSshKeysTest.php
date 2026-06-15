<?php

namespace App\Modules\HostingerProxyModule\Tests\Unit;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\UseCases\GetVpsSshKeys\GetVpsSshKeys;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetVpsSshKeysTest extends TestCase
{
    private MockInterface $client;

    private MockInterface $vpsRepository;

    private GetVpsSshKeys $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(HostingerProxyClientInterface::class);
        $this->vpsRepository = Mockery::mock(VpsRepositoryInterface::class);
        $this->useCase = new GetVpsSshKeys($this->client, $this->vpsRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_accepts_virtual_machine_public_keys_permission_and_normalizes_wrapped_payload(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('can')->with('VPS.PublicKeys.read')->andReturn(false);
        $user->shouldReceive('can')->with('VPS.VirtualMachine.PublicKeys.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(false);

        $this->vpsRepository->shouldReceive('userHasAccess')->with(42, 'vps-1')->once()->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'data' => [
                    [
                        'uuid' => 'key-1',
                        'label' => 'Anderson laptop',
                        'finger_print' => 'SHA256:abc123',
                        'createdAt' => '2026-06-15T12:00:00Z',
                    ],
                ],
            ]);
        Cache::shouldReceive('increment')->zeroOrMoreTimes();

        $result = $this->useCase->execute($user, 'vps-1');

        $this->assertTrue($result->success);
        $this->assertSame('key-1', $result->data[0]['id']);
        $this->assertSame('Anderson laptop', $result->data[0]['name']);
        $this->assertSame('SHA256:abc123', $result->data[0]['fingerprint']);
        $this->assertSame('2026-06-15T12:00:00Z', $result->data[0]['created_at']);
    }

    public function test_returns_forbidden_when_user_lacks_both_public_key_read_permissions(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('VPS.PublicKeys.read')->andReturn(false);
        $user->shouldReceive('can')->with('VPS.VirtualMachine.PublicKeys.read')->andReturn(false);

        $this->vpsRepository->shouldNotReceive('userHasAccess');
        $this->client->shouldNotReceive('getVpsSshKeys');

        $result = $this->useCase->execute($user, 'vps-1');

        $this->assertFalse($result->success);
        $this->assertSame('forbidden', $result->error);
    }

    public function test_returns_hostinger_forbidden_when_hostinger_denies_public_keys(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('can')->with('VPS.PublicKeys.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(false);

        $this->vpsRepository->shouldReceive('userHasAccess')->with(42, 'vps-1')->once()->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \RuntimeException('Hostinger API error [403]: Forbidden', 403));
        Cache::shouldReceive('increment')->zeroOrMoreTimes();

        $result = $this->useCase->execute($user, 'vps-1');

        $this->assertFalse($result->success);
        $this->assertSame('hostinger_forbidden', $result->error);
    }

    public function test_returns_hostinger_unauthorized_when_hostinger_rejects_token(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('can')->with('VPS.PublicKeys.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(false);

        $this->vpsRepository->shouldReceive('userHasAccess')->with(42, 'vps-1')->once()->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \RuntimeException('Hostinger API error [401]: Unauthorized', 401));
        Cache::shouldReceive('increment')->zeroOrMoreTimes();

        $result = $this->useCase->execute($user, 'vps-1');

        $this->assertFalse($result->success);
        $this->assertSame('hostinger_unauthorized', $result->error);
    }
}
