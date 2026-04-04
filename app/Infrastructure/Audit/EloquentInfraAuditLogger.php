<?php

namespace App\Infrastructure\Audit;

use App\Infrastructure\Audit\Models\InfraAuditLog;
use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;

class EloquentInfraAuditLogger implements InfraAuditLoggerInterface
{
    public function logAction(
        string $action,
        ?int $actorId,
        ?string $actorEmail,
        string $vpsId,
        string $resourceType,
        ?string $resourceId,
        string $correlationId,
        string $outcome,
        array $metadata,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        InfraAuditLog::create([
            'action' => $action,
            'actor_id' => $actorId,
            'actor_email' => $actorEmail,
            'vps_id' => $vpsId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'correlation_id' => $correlationId,
            'outcome' => $outcome,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
