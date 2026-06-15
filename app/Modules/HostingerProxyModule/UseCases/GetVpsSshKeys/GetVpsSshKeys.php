<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsSshKeys;

use App\Infrastructure\Cache\InstrumentedCache;
use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;

class GetVpsSshKeys
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {
        if (! $user->can('VPS.PublicKeys.read') && ! $user->can('VPS.VirtualMachine.PublicKeys.read')) {
            return ProxyResult::forbidden();
        }

        if (! $user->can('Manage.Permissions.VPS.all') && ! $this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = "hostinger:vps:{$vpsId}:ssh-keys";
            $data = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getVpsSshKeys($vpsId));

            return ProxyResult::success($this->normalizeKeys($data));
        } catch (\Throwable) {
            return ProxyResult::hostingerError();
        }
    }

    private function normalizeKeys(array $payload): array
    {
        $keys = $payload['data']
            ?? $payload['items']
            ?? $payload['public_keys']
            ?? $payload['publicKeys']
            ?? $payload;

        if (! is_array($keys)) {
            return [];
        }

        if (! array_is_list($keys)) {
            $keys = [$keys];
        }

        return array_values(array_map(function (array $key): array {
            $fingerprint = (string) ($key['fingerprint'] ?? $key['finger_print'] ?? $key['sha256'] ?? '');
            $name = (string) ($key['name'] ?? $key['label'] ?? $key['title'] ?? 'SSH key');
            $id = $key['id'] ?? $key['uuid'] ?? ($fingerprint ?: $name);

            return array_merge($key, [
                'id' => (string) $id,
                'name' => $name,
                'fingerprint' => $fingerprint ?: (string) ($key['key'] ?? $key['public_key'] ?? ''),
                'created_at' => (string) ($key['created_at'] ?? $key['createdAt'] ?? $key['attached_at'] ?? ''),
            ]);
        }, array_filter($keys, 'is_array')));
    }
}
