<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsMetrics;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;

class GetVpsMetrics
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {
        if (!$user->can('VPS.VirtualMachine.Manage.metrics')) {
            return ProxyResult::forbidden();
        }

        if (!$user->can('Manage.Permissions.VPS.all') && !$this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            // Metrics are live data — never cached
            return ProxyResult::success($this->client->getVpsMetrics($vpsId));
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
