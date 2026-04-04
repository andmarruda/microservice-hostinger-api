<?php

namespace App\Modules\VpsModule\Infrastructure\Services;

use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiResult;
use Illuminate\Support\Facades\Http;

class HttpHostingerApiClient implements HostingerApiClientInterface
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hostinger.base_url', 'https://developers.hostinger.com'), '/');
        $this->apiToken = config('services.hostinger.api_token', '');
    }

    public function startVps(string $vpsId, string $correlationId): HostingerApiResult
    {
        return $this->call('POST', "/api/vps/v1/virtual-machines/{$vpsId}/start", $correlationId);
    }

    public function stopVps(string $vpsId, string $correlationId): HostingerApiResult
    {
        return $this->call('POST', "/api/vps/v1/virtual-machines/{$vpsId}/stop", $correlationId);
    }

    public function rebootVps(string $vpsId, string $correlationId): HostingerApiResult
    {
        return $this->call('POST', "/api/vps/v1/virtual-machines/{$vpsId}/restart", $correlationId);
    }

    private function call(string $method, string $path, string $correlationId): HostingerApiResult
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->retry(3, 200)
                ->send($method, $this->baseUrl . $path);

            if ($response->successful()) {
                return HostingerApiResult::success($correlationId);
            }

            return HostingerApiResult::failure($correlationId, $response->body());
        } catch (\Throwable $e) {
            return HostingerApiResult::failure($correlationId, $e->getMessage());
        }
    }
}
