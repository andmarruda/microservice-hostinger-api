<?php

namespace App\Modules\HostingerProxyModule\Ports\Services;

interface HostingerProxyClientInterface
{
    /** @throws \Throwable */
    public function getBillingCatalog(): array;

    /** @throws \Throwable */
    public function getPaymentMethods(): array;

    /** @throws \Throwable */
    public function getSubscriptions(): array;

    /** @throws \Throwable */
    public function getDomainAvailability(string $domain): array;

    /** @throws \Throwable */
    public function getDomainForwarding(): array;

    /** @throws \Throwable */
    public function getDomainPortfolio(): array;

    /** @throws \Throwable */
    public function getWhois(): array;

    /** @throws \Throwable */
    public function getDnsZone(string $domain): array;

    /** @throws \Throwable */
    public function getDnsSnapshots(string $domain): array;

    /** @throws \Throwable */
    public function getHostingDatacenters(): array;

    /** @throws \Throwable */
    public function getReachContacts(): array;

    /** @throws \Throwable */
    public function getReachSegments(): array;

    /** @throws \Throwable */
    public function getVpsList(): array;

    /** @throws \Throwable */
    public function getVpsDetails(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsMetrics(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsActions(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsBackups(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsFirewall(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsOsTemplates(): array;

    /** @throws \Throwable */
    public function getVpsSshKeys(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsSnapshots(string $vpsId): array;

    /** @throws \Throwable */
    public function getVpsDatacenters(): array;

    /** @throws \Throwable */
    public function getVpsPostInstallScripts(string $vpsId): array;
}
