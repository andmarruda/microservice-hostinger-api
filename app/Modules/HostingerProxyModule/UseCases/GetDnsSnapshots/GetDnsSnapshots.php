<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetDnsSnapshots;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetDnsSnapshots
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user, string $domain): ProxyResult
    {
        if (!$user->can('DNS.Snapshot.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = 'hostinger:dns:snapshots:' . md5($domain);
            $data = Cache::remember($cacheKey, 86400, fn () => $this->client->getDnsSnapshots($domain));

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
