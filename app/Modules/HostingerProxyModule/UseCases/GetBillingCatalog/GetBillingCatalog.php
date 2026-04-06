<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetBillingCatalog;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;

class GetBillingCatalog
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Billing.getCatalog')) {
            return ProxyResult::forbidden();
        }

        try {
            // Billing is never cached — always fetch live
            return ProxyResult::success($this->client->getBillingCatalog());
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
