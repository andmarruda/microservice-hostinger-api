<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetReachSegments;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetReachSegments
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Reach.Segments.list')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = InstrumentedCache::remember('hostinger:reach:segments', 86400, fn () => $this->client->getReachSegments());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
