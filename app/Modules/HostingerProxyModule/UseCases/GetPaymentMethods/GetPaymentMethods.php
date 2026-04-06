<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetPaymentMethods;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetPaymentMethods
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Orders.PaymentMethods.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = Cache::remember('hostinger:orders:payment_methods', 86400, fn () => $this->client->getPaymentMethods());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
