<?php

namespace App\Modules\HostingerProxyModule\Tests\Unit;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\UseCases\GetVpsList\GetVpsList;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetVpsListTest extends TestCase
{
    private MockInterface $client;
    private MockInterface $vpsRepository;
    private GetVpsList $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(HostingerProxyClientInterface::class);
        $this->vpsRepository = Mockery::mock(VpsRepositoryInterface::class);
        $this->useCase = new GetVpsList($this->client, $this->vpsRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_forbidden_when_user_lacks_permission(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('VPS.VirtualMachine.Manage.read')->andReturn(false);

        $this->client->shouldNotReceive('getVpsList');

        $result = $this->useCase->execute($user);

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
    }

    public function test_admin_with_vps_all_permission_gets_full_list(): void
    {
        $allVps = [['id' => 'vps-1'], ['id' => 'vps-2'], ['id' => 'vps-3']];

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('VPS.VirtualMachine.Manage.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($allVps);

        $this->vpsRepository->shouldNotReceive('findAllForUser');

        $result = $this->useCase->execute($user);

        $this->assertTrue($result->success);
        $this->assertCount(3, $result->data);
    }

    public function test_scoped_user_only_sees_granted_vps(): void
    {
        $allVps = [['id' => 'vps-1'], ['id' => 'vps-2'], ['id' => 'vps-3']];

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('can')->with('VPS.VirtualMachine.Manage.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($allVps);

        $this->vpsRepository->shouldReceive('findAllForUser')->with(42)->once()->andReturn(['vps-1', 'vps-3']);

        $result = $this->useCase->execute($user);

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->data);
        $this->assertEquals('vps-1', $result->data[0]['id']);
        $this->assertEquals('vps-3', $result->data[1]['id']);
    }

    public function test_scoped_user_with_no_grants_gets_empty_list(): void
    {
        $allVps = [['id' => 'vps-1'], ['id' => 'vps-2']];

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 99;
        $user->shouldReceive('can')->with('VPS.VirtualMachine.Manage.read')->andReturn(true);
        $user->shouldReceive('can')->with('Manage.Permissions.VPS.all')->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($allVps);

        $this->vpsRepository->shouldReceive('findAllForUser')->with(99)->once()->andReturn([]);

        $result = $this->useCase->execute($user);

        $this->assertTrue($result->success);
        $this->assertCount(0, $result->data);
    }

    public function test_returns_hostinger_error_when_client_throws(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('VPS.VirtualMachine.Manage.read')->andReturn(true);

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $result = $this->useCase->execute($user);

        $this->assertFalse($result->success);
        $this->assertEquals('hostinger_error', $result->error);
    }
}
