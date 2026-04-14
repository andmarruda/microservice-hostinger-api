<?php

namespace App\Modules\DriftModule\Jobs;

use App\Modules\DriftModule\Models\DriftReport;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunDriftScan implements ShouldQueue
{
    use Queueable;

    public function handle(HostingerProxyClientInterface $client): void
    {
        try {
            $liveVps = $client->getVpsList();
        } catch (\Throwable $e) {
            Log::warning('RunDriftScan: could not fetch VPS list from Hostinger.', ['error' => $e->getMessage()]);
            return;
        }

        $liveIds = collect($liveVps)->pluck('id')->filter()->flip()->all();

        // Detect grants for VPS IDs that no longer exist in Hostinger.
        VpsAccessGrant::with('user')
            ->chunkById(200, function ($grants) use ($liveIds) {
                foreach ($grants as $grant) {
                    if (!array_key_exists($grant->vps_id, $liveIds)) {
                        $this->createReportIfMissing('orphan_grant', 'high', $grant->vps_id, $grant->user_id, [
                            'vps_id'  => $grant->vps_id,
                            'user_id' => $grant->user_id,
                            'reason'  => 'Access grant exists for a VPS that no longer exists in Hostinger.',
                        ]);
                    }
                }
            });

        // Detect VPS IDs that exist in Hostinger but have no access grants locally.
        $knownVpsIds = VpsAccessGrant::distinct()->pluck('vps_id')->flip()->all();

        foreach (array_keys($liveIds) as $vpsId) {
            if (!array_key_exists($vpsId, $knownVpsIds)) {
                $this->createReportIfMissing('undiscovered_vps', 'medium', $vpsId, null, [
                    'vps_id' => $vpsId,
                    'reason' => 'VPS exists in Hostinger but has no local access grants.',
                ]);
            }
        }
    }

    private function createReportIfMissing(
        string $driftType,
        string $severity,
        ?string $vpsId,
        ?int $userId,
        array $details,
    ): void {
        $exists = DriftReport::where('drift_type', $driftType)
            ->where('vps_id', $vpsId)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->exists();

        if (!$exists) {
            DriftReport::create([
                'drift_type'  => $driftType,
                'severity'    => $severity,
                'vps_id'      => $vpsId,
                'user_id'     => $userId,
                'details'     => $details,
                'status'      => 'open',
                'detected_at' => now(),
            ]);
        }
    }
}
