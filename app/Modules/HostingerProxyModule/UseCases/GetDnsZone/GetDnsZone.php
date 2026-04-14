<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetDnsZone;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetDnsZone
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user, string $domain): ProxyResult
    {
        if (!$user->can('DNS.Zone.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = 'hostinger:dns:zone:' . md5($domain);
            $data = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getDnsZone($domain));

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
