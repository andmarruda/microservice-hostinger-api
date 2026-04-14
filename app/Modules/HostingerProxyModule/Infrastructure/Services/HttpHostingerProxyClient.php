<?php

namespace App\Modules\HostingerProxyModule\Infrastructure\Services;

use App\Exceptions\HostingerQuotaExceededException;
use App\Infrastructure\Quota\HostingerQuotaTracker;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpHostingerProxyClient implements HostingerProxyClientInterface
{
    private string $baseUrl;
    private string $apiToken;
    private int $timeout;

    public function __construct(private HostingerQuotaTracker $quota)
    {
        $this->baseUrl  = rtrim(config('services.hostinger.base_url', 'https://developers.hostinger.com'), '/');
        $this->apiToken = config('services.hostinger.api_token', '');
        $this->timeout  = (int) config('services.hostinger.timeout', 10);
    }

    public function getBillingCatalog(): array
    {
        return $this->get('/api/billing/v1/catalog');
    }

    public function getPaymentMethods(): array
    {
        return $this->get('/api/billing/v1/payment-methods');
    }

    public function getSubscriptions(): array
    {
        return $this->get('/api/billing/v1/subscriptions');
    }

    public function getDomainAvailability(string $domain): array
    {
        return $this->get('/api/domains/v1/availability', ['domain' => $domain]);
    }

    public function getDomainForwarding(): array
    {
        return $this->get('/api/domains/v1/forwarding');
    }

    public function getDomainPortfolio(): array
    {
        return $this->get('/api/domains/v1/portfolio');
    }

    public function getWhois(): array
    {
        return $this->get('/api/domains/v1/whois');
    }

    public function getDnsZone(string $domain): array
    {
        return $this->get("/api/dns/v1/zones/{$domain}");
    }

    public function getDnsSnapshots(string $domain): array
    {
        return $this->get("/api/dns/v1/zones/{$domain}/snapshots");
    }

    public function getHostingDatacenters(): array
    {
        return $this->get('/api/hosting/v1/datacenters');
    }

    public function getReachContacts(): array
    {
        return $this->get('/api/reach/v1/contacts');
    }

    public function getReachSegments(): array
    {
        return $this->get('/api/reach/v1/segments');
    }

    public function getVpsList(): array
    {
        return $this->get('/api/vps/v1/virtual-machines');
    }

    public function getVpsDetails(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}");
    }

    public function getVpsMetrics(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/metrics");
    }

    public function getVpsActions(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/actions");
    }

    public function getVpsBackups(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/backups");
    }

    public function getVpsFirewall(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/firewall");
    }

    public function getVpsOsTemplates(): array
    {
        return $this->get('/api/vps/v1/os-templates');
    }

    public function getVpsSshKeys(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/public-keys");
    }

    public function getVpsSnapshots(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/snapshots");
    }

    public function getVpsDatacenters(): array
    {
        return $this->get('/api/vps/v1/data-centers');
    }

    public function getVpsPostInstallScripts(string $vpsId): array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vpsId}/post-install-scripts");
    }

    /**
     * @throws HostingerQuotaExceededException
     * @throws \RuntimeException
     */
    private function get(string $path, array $query = []): array
    {
        // GAP 9: Hard quota enforcement before consuming the API call
        if ($this->quota->isHardLimitReached()) {
            throw new HostingerQuotaExceededException(
                'Hostinger API daily quota hard limit reached. Try again tomorrow.',
            );
        }

        // GAP 7: Track quota per resource type
        $resourceType = $this->extractResourceType($path);
        $this->quota->increment($resourceType);

        // GAP 1: Propagate X-Correlation-ID to Hostinger
        $correlationId = app()->bound('request.id') ? app('request.id') : null;

        $request = Http::withToken($this->apiToken)
            ->timeout($this->timeout)   // GAP 6
            ->retry(3, 200);

        if ($correlationId) {
            $request = $request->withHeader('X-Correlation-ID', $correlationId);
        }

        $response = empty($query)
            ? $request->get($this->baseUrl . $path)
            : $request->get($this->baseUrl . $path, $query);

        if (!$response->successful()) {
            // GAP 3: Tag errors with error_source: hostinger
            Log::warning('Hostinger API error.', [
                'error_source'   => 'hostinger',
                'status_code'    => $response->status(),
                'path'           => $path,
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException("Hostinger API error [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    private function extractResourceType(string $path): string
    {
        return match (true) {
            str_contains($path, '/api/vps/')     => 'vps',
            str_contains($path, '/api/billing/') => 'billing',
            str_contains($path, '/api/domains/') => 'domains',
            str_contains($path, '/api/dns/')     => 'dns',
            str_contains($path, '/api/hosting/') => 'hosting',
            str_contains($path, '/api/reach/')   => 'reach',
            str_contains($path, '/api/orders/')  => 'orders',
            default                              => 'other',
        };
    }
}
