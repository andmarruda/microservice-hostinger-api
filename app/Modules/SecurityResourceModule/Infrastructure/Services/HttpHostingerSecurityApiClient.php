<?php

namespace App\Modules\SecurityResourceModule\Infrastructure\Services;

use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiResult;
use Illuminate\Support\Facades\Http;

class HttpHostingerSecurityApiClient implements HostingerSecurityApiClientInterface
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hostinger.base_url', 'https://developers.hostinger.com'), '/');
        $this->apiToken = config('services.hostinger.api_token', '');
    }

    public function addFirewallRule(string $vpsId, array $rule, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('POST', "/api/vps/v1/firewall/{$vpsId}/rules", $correlationId, $rule);
    }

    public function removeFirewallRule(string $vpsId, string $ruleId, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('DELETE', "/api/vps/v1/firewall/{$vpsId}/rules/{$ruleId}", $correlationId);
    }

    public function addSshKey(string $vpsId, string $keyName, string $publicKey, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('POST', "/api/vps/v1/virtual-machines/{$vpsId}/public-keys", $correlationId, [
            'name' => $keyName,
            'key' => $publicKey,
        ]);
    }

    public function removeSshKey(string $vpsId, string $keyId, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('DELETE', "/api/vps/v1/virtual-machines/{$vpsId}/public-keys/{$keyId}", $correlationId);
    }

    public function createSnapshot(string $vpsId, string $label, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('POST', "/api/vps/v1/virtual-machines/{$vpsId}/snapshots", $correlationId, [
            'label' => $label,
        ]);
    }

    public function deleteSnapshot(string $vpsId, string $snapshotId, string $correlationId): HostingerSecurityApiResult
    {
        return $this->call('DELETE', "/api/vps/v1/virtual-machines/{$vpsId}/snapshots/{$snapshotId}", $correlationId);
    }

    private function call(string $method, string $path, string $correlationId, array $body = []): HostingerSecurityApiResult
    {
        try {
            $request = Http::withToken($this->apiToken)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->retry(3, 200);

            $response = empty($body)
                ? $request->send($method, $this->baseUrl . $path)
                : $request->send($method, $this->baseUrl . $path, ['json' => $body]);

            if ($response->successful()) {
                return HostingerSecurityApiResult::success($correlationId);
            }

            return HostingerSecurityApiResult::failure($correlationId, $response->body());
        } catch (\Throwable $e) {
            return HostingerSecurityApiResult::failure($correlationId, $e->getMessage());
        }
    }
}
