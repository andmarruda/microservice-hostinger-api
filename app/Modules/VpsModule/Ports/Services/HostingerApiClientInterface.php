<?php

namespace App\Modules\VpsModule\Ports\Services;

interface HostingerApiClientInterface
{
    public function startVps(string $vpsId, string $correlationId): HostingerApiResult;

    public function stopVps(string $vpsId, string $correlationId): HostingerApiResult;

    public function rebootVps(string $vpsId, string $correlationId): HostingerApiResult;
}
