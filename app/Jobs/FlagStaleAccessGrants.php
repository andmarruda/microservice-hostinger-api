<?php

namespace App\Jobs;

use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FlagStaleAccessGrants implements ShouldQueue
{
    use Queueable;

    public function handle(HostingerProxyClientInterface $client): void
    {
        try {
            $liveVps = $client->getVpsList();
        } catch (\Throwable $e) {
            Log::warning('FlagStaleAccessGrants: could not fetch VPS list from Hostinger.', ['error' => $e->getMessage()]);
            return;
        }

        $liveIds = collect($liveVps)->pluck('id')->filter()->all();

        VpsAccessGrant::whereNotIn('vps_id', $liveIds)
            ->whereNull('stale_at')
            ->update(['stale_at' => now()]);
    }
}
