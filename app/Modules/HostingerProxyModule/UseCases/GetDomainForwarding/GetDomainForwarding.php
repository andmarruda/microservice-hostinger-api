<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetDomainForwarding;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetDomainForwarding
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Domains.Forwarding.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = Cache::remember('hostinger:domains:forwarding', 86400, fn () => $this->client->getDomainForwarding());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
