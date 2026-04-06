<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetDomainAvailability;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetDomainAvailability
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user, string $domain): ProxyResult
    {
        if (!$user->can('Domains.Availability.validate')) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = 'hostinger:domains:availability:' . md5($domain);
            $data = Cache::remember($cacheKey, 3600, fn () => $this->client->getDomainAvailability($domain));

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
