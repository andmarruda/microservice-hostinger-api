<?php

namespace App\Modules\DriftModule\Jobs;

use App\Modules\DriftModule\Models\DriftReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ArchiveOldDriftReports implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $retentionDays = (int) config('audit.retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        $count = DriftReport::whereIn('status', ['resolved', 'dismissed'])
            ->where(function ($query) use ($cutoff) {
                $query->where('resolved_at', '<', $cutoff)
                      ->orWhere(function ($q) use ($cutoff) {
                          $q->whereNull('resolved_at')
                            ->where('detected_at', '<', $cutoff);
                      });
            })
            ->update(['status' => 'archived']);

        if ($count > 0) {
            Log::info('ArchiveOldDriftReports: archived reports.', ['count' => $count]);
        }
    }
}
