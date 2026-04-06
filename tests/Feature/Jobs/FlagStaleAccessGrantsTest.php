<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FlagStaleAccessGrants;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\VpsModule\Factories\VpsAccessGrantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FlagStaleAccessGrantsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function client(array $liveVps): HostingerProxyClientInterface
    {
        $mock = Mockery::mock(HostingerProxyClientInterface::class);
        $mock->shouldReceive('getVpsList')->andReturn($liveVps);
        return $mock;
    }

    public function test_flags_grant_whose_vps_no_longer_exists(): void
    {
        $grant = VpsAccessGrantFactory::new()->forVps('vps-gone')->create();

        (new FlagStaleAccessGrants())->handle($this->client([['id' => 'vps-alive']]));

        $this->assertNotNull($grant->fresh()->stale_at);
    }

    public function test_does_not_flag_grant_for_existing_vps(): void
    {
        $grant = VpsAccessGrantFactory::new()->forVps('vps-alive')->create();

        (new FlagStaleAccessGrants())->handle($this->client([['id' => 'vps-alive']]));

        $this->assertNull($grant->fresh()->stale_at);
    }

    public function test_does_not_overwrite_existing_stale_at(): void
    {
        $grant = VpsAccessGrantFactory::new()->forVps('vps-gone')->create(['stale_at' => now()->subDay()]);

        (new FlagStaleAccessGrants())->handle($this->client([['id' => 'vps-alive']]));

        // The job must not have reset stale_at to now(); it should still be ~24 hours ago.
        $this->assertTrue($grant->fresh()->stale_at->lessThan(now()->subHours(12)));
    }

    public function test_does_nothing_when_hostinger_call_fails(): void
    {
        $grant = VpsAccessGrantFactory::new()->forVps('vps-1')->create();

        $client = Mockery::mock(HostingerProxyClientInterface::class);
        $client->shouldReceive('getVpsList')->andThrow(new \RuntimeException('timeout'));

        (new FlagStaleAccessGrants())->handle($client);

        $this->assertNull($grant->fresh()->stale_at);
    }
}
