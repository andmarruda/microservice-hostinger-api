<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsDetails;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetVpsDetails
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {
        if (!$user->can('VPS.VirtualMachine.Manage.details')) {
            return ProxyResult::forbidden();
        }

        if (!$user->can('Manage.Permissions.VPS.all') && !$this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = "hostinger:vps:{$vpsId}:details";
            $data = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getVpsDetails($vpsId));

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
