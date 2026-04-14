<?php

namespace App\Jobs;

use App\Infrastructure\Audit\Models\InfraAuditLog;
use App\Modules\AuthModule\Models\AuthAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneAuditLogs implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // infra_audit_logs + auth_audit_logs share the same retention config key
        $retentionDays = (int) config('audit.retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        InfraAuditLog::where('created_at', '<', $cutoff)
            ->chunkById(500, fn ($chunk) => $chunk->toQuery()->delete());

        AuthAuditLog::where('created_at', '<', $cutoff)
            ->chunkById(500, fn ($chunk) => $chunk->toQuery()->delete());

        // drift_reports — ADR-015: 90-day default
        $driftRetention = (int) env('DRIFT_REPORT_RETENTION_DAYS', 90);
        $driftCutoff = now()->subDays($driftRetention);
        $driftDeleted = DB::table('drift_reports')->where('created_at', '<', $driftCutoff)->delete();
        if ($driftDeleted > 0) {
            Log::info('PruneAuditLogs: pruned drift_reports.', ['count' => $driftDeleted, 'retention_days' => $driftRetention]);
        }

        // access_reviews — ADR-015: 730-day default
        $reviewRetention = (int) env('ACCESS_REVIEW_RETENTION_DAYS', 730);
        $reviewCutoff = now()->subDays($reviewRetention);
        $reviewDeleted = DB::table('access_reviews')->where('created_at', '<', $reviewCutoff)->delete();
        if ($reviewDeleted > 0) {
            Log::info('PruneAuditLogs: pruned access_reviews.', ['count' => $reviewDeleted, 'retention_days' => $reviewRetention]);
        }

        // failed_jobs — ADR-015: 30-day default
        $failedRetention = (int) env('FAILED_JOB_RETENTION_DAYS', 30);
        $failedCutoff = now()->subDays($failedRetention);
        $failedDeleted = DB::table('failed_jobs')->where('failed_at', '<', $failedCutoff->timestamp)->delete();
        if ($failedDeleted > 0) {
            Log::info('PruneAuditLogs: pruned failed_jobs.', ['count' => $failedDeleted, 'retention_days' => $failedRetention]);
        }
    }
}
