<?php

namespace App\Modules\HostingerProxyModule\Infrastructure\Services;

use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use Illuminate\Support\Facades\Http;

class HttpHostingerProxyClient implements HostingerProxyClientInterface
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hostinger.base_url', 'https://developers.hostinger.com'), '/');
        $this->apiToken = config('services.hostinger.api_token', '');
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
     * @throws \RuntimeException
     */
    private function get(string $path, array $query = []): array
    {
        $request = Http::withToken($this->apiToken)->retry(3, 200);

        $response = empty($query)
            ? $request->get($this->baseUrl . $path)
            : $request->get($this->baseUrl . $path, $query);

        if (!$response->successful()) {
            throw new \RuntimeException("Hostinger API error [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }
}
