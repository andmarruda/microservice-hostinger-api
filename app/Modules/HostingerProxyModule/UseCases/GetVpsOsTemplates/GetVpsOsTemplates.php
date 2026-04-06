<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsOsTemplates;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetVpsOsTemplates
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('VPS.OSTemplates.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = Cache::remember('hostinger:vps:os-templates', 86400, fn () => $this->client->getVpsOsTemplates());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
