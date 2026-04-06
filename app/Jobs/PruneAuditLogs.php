<?php

namespace App\Jobs;

use App\Infrastructure\Audit\Models\InfraAuditLog;
use App\Modules\AuthModule\Models\AuthAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneAuditLogs implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $retentionDays = (int) config('audit.retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        InfraAuditLog::where('created_at', '<', $cutoff)
            ->chunkById(500, fn ($chunk) => $chunk->toQuery()->delete());

        AuthAuditLog::where('created_at', '<', $cutoff)
            ->chunkById(500, fn ($chunk) => $chunk->toQuery()->delete());
    }
}
