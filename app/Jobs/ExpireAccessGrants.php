<?php

namespace App\Jobs;

use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireAccessGrants implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $now = now();

        // ADR-014: Expired grants are NOT deleted — they are kept for audit purposes.
        // Access checks filter them out via expires_at > NOW() at query time.
        // This job only reports the current count of expired records for observability.

        $grants = VpsAccessGrant::whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->count();

        if ($grants > 0) {
            Log::info('ExpireAccessGrants: expired VPS access grants present (retained for audit).', [
                'count' => $grants,
            ]);
        }

        $permissions = SecurityPermission::whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->count();

        if ($permissions > 0) {
            Log::info('ExpireAccessGrants: expired security permissions present (retained for audit).', [
                'count' => $permissions,
            ]);
        }
    }
}
