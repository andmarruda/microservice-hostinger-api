<?php

namespace App\Modules\HostingerProxyModule\UseCases\GetVpsMetrics;

use App\Modules\AuthModule\Models\User;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\HostingerProxyModule\Ports\Services\ProxyResult;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class GetVpsMetrics
{
    public function __construct(
        private HostingerProxyClientInterface $client,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function execute(User $user, string $vpsId): ProxyResult
    {
        if (! $user->can('VPS.VirtualMachine.Manage.metrics')) {
            return ProxyResult::forbidden();
        }

        if (! $user->can('Manage.Permissions.VPS.all') && ! $this->vpsRepository->userHasAccess($user->id, $vpsId)) {
            return ProxyResult::forbidden();
        }

        try {
            // Metrics are live data — never cached
            return ProxyResult::success($this->normalizeMetrics($this->client->getVpsMetrics($vpsId)));
        } catch (RequestException $e) {
            Log::warning('Failed to load Hostinger VPS metrics.', [
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
            Log::warning('Failed to load Hostinger VPS metrics.', [
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
            Log::warning('Failed to load Hostinger VPS metrics.', [
                'vps_id' => $vpsId,
                'status_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return ProxyResult::hostingerError();
        }
    }

    private function normalizeMetrics(array $payload): array
    {
        if (array_key_exists('cpu_usage', $payload) && is_numeric($payload['cpu_usage'])) {
            return $payload;
        }

        return [
            'cpu_usage' => $this->latestUsageValue($payload['cpu_usage'] ?? null),
            'memory_usage' => $this->bytesToMegabytes($this->latestUsageValue($payload['ram_usage'] ?? null)),
            'disk_usage' => $this->bytesToGigabytes($this->latestUsageValue($payload['disk_space'] ?? null)),
            'network_in' => $this->latestUsageValue($payload['incoming_traffic'] ?? null),
            'network_out' => $this->latestUsageValue($payload['outgoing_traffic'] ?? null),
            'uptime' => $this->latestUsageValue($payload['uptime'] ?? null),
            'raw' => $payload,
        ];
    }

    private function latestUsageValue(mixed $metric): float
    {
        if (is_numeric($metric)) {
            return (float) $metric;
        }

        if (! is_array($metric)) {
            return 0.0;
        }

        $usage = $metric['usage'] ?? null;

        if (! is_array($usage) || empty($usage)) {
            return 0.0;
        }

        ksort($usage, SORT_NUMERIC);

        return (float) end($usage);
    }

    private function bytesToMegabytes(float $bytes): float
    {
        return $bytes / 1024 / 1024;
    }

    private function bytesToGigabytes(float $bytes): float
    {
        return $bytes / 1024 / 1024 / 1024;
    }
}
