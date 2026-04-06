<?php

namespace Tests\Feature\Jobs;

use App\Jobs\WarmHostingerCache;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WarmHostingerCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_populates_cache_keys_from_client(): void
    {
        $client = Mockery::mock(HostingerProxyClientInterface::class);
        $client->shouldReceive('getVpsList')->once()->andReturn([['id' => 'vps-1']]);
        $client->shouldReceive('getVpsOsTemplates')->once()->andReturn([['name' => 'Ubuntu']]);
        $client->shouldReceive('getVpsDatacenters')->once()->andReturn([['name' => 'EU-NL']]);

        (new WarmHostingerCache())->handle($client);

        $this->assertSame([['id' => 'vps-1']], Cache::get('hostinger:vps:list:all'));
        $this->assertSame([['name' => 'Ubuntu']], Cache::get('hostinger:vps:os-templates'));
        $this->assertSame([['name' => 'EU-NL']], Cache::get('hostinger:vps:datacenters'));
    }

    public function test_continues_warming_other_keys_when_one_fails(): void
    {
        $client = Mockery::mock(HostingerProxyClientInterface::class);
        $client->shouldReceive('getVpsList')->once()->andThrow(new \RuntimeException('timeout'));
        $client->shouldReceive('getVpsOsTemplates')->once()->andReturn([]);
        $client->shouldReceive('getVpsDatacenters')->once()->andReturn([]);

        (new WarmHostingerCache())->handle($client);

        $this->assertNull(Cache::get('hostinger:vps:list:all'));
        $this->assertSame([], Cache::get('hostinger:vps:os-templates'));
        $this->assertSame([], Cache::get('hostinger:vps:datacenters'));
    }
}
