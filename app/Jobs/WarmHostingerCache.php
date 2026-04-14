<?php

namespace App\Jobs;

use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WarmHostingerCache implements ShouldQueue
{
    use Queueable;

    public function handle(HostingerProxyClientInterface $client): void
    {
        $tasks = [
            'hostinger:vps:list:all'     => [fn () => $client->getVpsList(),         config('hostinger.cache_ttl.vps_list', 86400)],
            'hostinger:vps:os-templates' => [fn () => $client->getVpsOsTemplates(),   config('hostinger.cache_ttl.os_templates', 86400)],
            'hostinger:vps:datacenters'  => [fn () => $client->getVpsDatacenters(),   config('hostinger.cache_ttl.datacenters', 86400)],
        ];

        foreach ($tasks as $key => [$fetcher, $ttl]) {
            try {
                Cache::put($key, $fetcher(), (int) $ttl);
            } catch (\Throwable $e) {
                Log::warning("WarmHostingerCache: failed to warm [{$key}]", ['error' => $e->getMessage()]);
            }
        }
    }
}
