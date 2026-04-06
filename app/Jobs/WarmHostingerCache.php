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

    private const TTL = 86400;

    public function handle(HostingerProxyClientInterface $client): void
    {
        $tasks = [
            'hostinger:vps:list:all'   => fn () => $client->getVpsList(),
            'hostinger:vps:os-templates' => fn () => $client->getVpsOsTemplates(),
            'hostinger:vps:datacenters'  => fn () => $client->getVpsDatacenters(),
        ];

        foreach ($tasks as $key => $fetcher) {
            try {
                Cache::put($key, $fetcher(), self::TTL);
            } catch (\Throwable $e) {
                Log::warning("WarmHostingerCache: failed to warm [{$key}]", ['error' => $e->getMessage()]);
            }
        }
    }
}
