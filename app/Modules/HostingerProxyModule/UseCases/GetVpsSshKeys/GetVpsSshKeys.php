<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsSshKeys;

use App\Infrastructure\Cache\InstrumentedCache;
use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class GetVpsSshKeys
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {
        if (! $user->can('Manage.Permissions.VPS.all') && ! $this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            $cacheKey = "hostinger:vps:{$vpsId}:ssh-keys:v2";
            $data = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getVpsSshKeys($vpsId));

            return ProxyResult::success($this->normalizeKeys($data));
        } catch (RequestException $e) {
            Log::warning('Failed to load Hostinger VPS SSH keys.', [
                'vps_id' => $vpsId,
                'status_code' => $e->response->status(),
                'message' => $e->getMessage(),
            ]);

            if ($e->response->status() === 401) {
                return ProxyResult::hostingerUnauthorized();
            }

            if ($e->response->status() === 403) {
                return ProxyResult::hostingerForbidden();
            }

            return ProxyResult::hostingerError();
        } catch (\RuntimeException $e) {
            Log::warning('Failed to load Hostinger VPS SSH keys.', [
                'vps_id' => $vpsId,
                'status_code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            if ($e->getCode() === 401) {
                return ProxyResult::hostingerUnauthorized();
            }

            if ($e->getCode() === 403) {
                return ProxyResult::hostingerForbidden();
            }

            return ProxyResult::hostingerError();
        } catch (\Throwable $e) {
            Log::warning('Failed to load Hostinger VPS SSH keys.', [
                'vps_id' => $vpsId,
                'status_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

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
