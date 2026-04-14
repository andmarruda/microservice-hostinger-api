<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetWhois;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetWhois
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Domains.Whois.read') && !$user->can('Domains.Whois.list')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = InstrumentedCache::remember('hostinger:domains:whois', 86400, fn () => $this->client->getWhois());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
