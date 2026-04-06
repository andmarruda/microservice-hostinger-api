<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetReachContacts;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use Illuminate\Support\Facades\Cache;

class GetReachContacts
{
    public function __construct(
        private HostingerProxyClientInterface $client,
    ) {}

    public function execute(User $user): ProxyResult
    {
        if (!$user->can('Reach.Contacts.read')) {
            return ProxyResult::forbidden();
        }

        try {
            $data = Cache::remember('hostinger:reach:contacts', 86400, fn () => $this->client->getReachContacts());

            return ProxyResult::success($data);
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }
}
