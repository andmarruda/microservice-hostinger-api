<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsDatacenters;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetVpsDatacenters
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('VPS.DataCenters.list')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = InstrumentedCache::remember('hostinger:vps:datacenters', 86400, fn () => $this->client->getVpsDatacenters());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
