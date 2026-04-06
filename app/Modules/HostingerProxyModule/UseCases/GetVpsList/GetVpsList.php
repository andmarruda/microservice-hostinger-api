<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsList;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class GetVpsList
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('VPS.VirtualMachine.Manage.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $allVps = Cache::remember('hostinger:vps:list:all', 86400, fn () => $this->client->getVpsList());

            if ($user->can('Manage.Permissions.VPS.all')) {
                return ProxyResult::success($allVps);
            }

            $allowedIds = $this->vpsRepository->findAllForUser($user->id);
            $filtered = array_values(
                array_filter($allVps, fn ($vps) => in_array($vps['id'] ?? null, $allowedIds, true))
            );

            return ProxyResult::success($filtered);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
