<?php

namespace App\Modules\GovernanceModule\UseCases\ExportAuditLogs;

use App\Infrastructure\Audit\Models\InfraAuditLog;
use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use App\Modules\AuthModule\Models\User;

class ExportAuditLogs
{
    public function __construct(private InfraAuditLoggerInterface $auditLogger) {}

    public function execute(User $actor, array $filters, string $format = 'json'): ExportAuditLogsResult
    {
        if (!$actor->hasRole('root')) {
            return ExportAuditLogsResult::forbidden();
        }

        $query = InfraAuditLog::query()->orderBy('created_at');

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['vps_id'])) {
            $query->where('vps_id', $filters['vps_id']);
        }

        $logs = $query->limit(10000)->get();

        // ADR-014: the export action itself is recorded in infra_audit_logs
        $this->auditLogger->logAction(
            action:       'audit_export',
            actorId:      $actor->id,
            actorEmail:   $actor->email,
            vpsId:        $filters['vps_id'] ?? '',
            resourceType: 'audit_log',
            resourceId:   null,
            correlationId: app()->bound('request.id') ? app('request.id') : '',
            outcome:      'success',
            metadata:     [
                'filters' => $filters,
                'format'  => $format,
                'rows'    => $logs->count(),
            ],
            ipAddress:  request()->ip(),
            userAgent:  request()->userAgent(),
        );

        if ($format === 'csv') {
            return ExportAuditLogsResult::csv($this->toCsv($logs));
        }

        return ExportAuditLogsResult::json($logs->toArray());
    }

    private function toCsv(\Illuminate\Support\Collection $logs): string
    {
        if ($logs->isEmpty()) {
            return "action,actor_id,actor_email,vps_id,resource_type,resource_id,correlation_id,outcome,ip_address,created_at\n";
        }

        $headers = ['action', 'actor_id', 'actor_email', 'vps_id', 'resource_type', 'resource_id', 'correlation_id', 'outcome', 'ip_address', 'created_at'];
        $lines   = [implode(',', $headers)];

        foreach ($logs as $log) {
            $lines[] = implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"',
                array_map(fn ($h) => $log[$h] ?? '', $headers),
            ));
        }

        return implode("\n", $lines);
    }
}
