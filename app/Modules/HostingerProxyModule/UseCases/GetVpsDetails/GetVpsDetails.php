<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsDetails;

use App\Infrastructure\Cache\InstrumentedCache;
use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class GetVpsDetails
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
            $cacheKey = "hostinger:vps:{$vpsId}:details";
            $data = InstrumentedCache::remember($cacheKey, 86400, fn () => $this->client->getVpsDetails($vpsId));

            return ProxyResult::success($data);
        } catch (RequestException $e) {
            Log::warning('Failed to load Hostinger VPS details.', [
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
            Log::warning('Failed to load Hostinger VPS details.', [
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
            Log::warning('Failed to load Hostinger VPS details.', [
                'vps_id' => $vpsId,
                'status_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return ProxyResult::hostingerError();
        }
    }
}
