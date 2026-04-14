<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetDomainPortfolio;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetDomainPortfolio
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Domains.Portfolio.Details') && !$user->can('Domains.Portfolio.Manage.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = InstrumentedCache::remember('hostinger:domains:portfolio', 86400, fn () => $this->client->getDomainPortfolio());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
