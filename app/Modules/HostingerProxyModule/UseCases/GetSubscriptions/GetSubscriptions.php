<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetSubscriptions;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetSubscriptions
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Orders.Subscriptions.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = Cache::remember('hostinger:orders:subscriptions', 86400, fn () => $this->client->getSubscriptions());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
