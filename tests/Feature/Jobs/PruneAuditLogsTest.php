<?php

namespace Tests\Feature\Jobs;

use App\Infrastructure\Audit\Models\InfraAuditLog;
use App\Jobs\PruneAuditLogs;
use App\Modules\AuthModule\Models\AuthAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_infra_audit_logs_older_than_retention(): void
    {
        InfraAuditLog::create([
            'action'         => 'vps_start',
            'actor_id'       => null,
            'actor_email'    => 'test@example.com',
            'vps_id'         => 'vps-1',
            'resource_type'  => 'vps',
            'resource_id'    => null,
            'correlation_id' => 'corr-old',
            'outcome'        => 'success',
            'metadata'       => null,
            'ip_address'     => null,
            'user_agent'     => null,
            'created_at'     => now()->subDays(91),
        ]);

        (new PruneAuditLogs())->handle();

        $this->assertDatabaseCount('infra_audit_logs', 0);
    }

    public function test_keeps_infra_audit_logs_within_retention(): void
    {
        InfraAuditLog::create([
            'action'         => 'vps_start',
            'actor_id'       => null,
            'actor_email'    => 'test@example.com',
            'vps_id'         => 'vps-1',
            'resource_type'  => 'vps',
            'resource_id'    => null,
            'correlation_id' => 'corr-recent',
            'outcome'        => 'success',
            'metadata'       => null,
            'ip_address'     => null,
            'user_agent'     => null,
            'created_at'     => now()->subDays(30),
        ]);

        (new PruneAuditLogs())->handle();

        $this->assertDatabaseCount('infra_audit_logs', 1);
    }
}
