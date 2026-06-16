<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsActions;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Cache\InstrumentedCache;

class GetVpsActions
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {

        if (!$user->can('Manage.Permissions.VPS.all') && !$this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = "hostinger:vps:{$vpsId}:actions";
            $raw = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getVpsActions($vpsId));
            $data = isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : (array_is_list($raw) ? $raw : []);

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
