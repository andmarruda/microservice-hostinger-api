<?php

namespace App\Infrastructure\Audit\Ports;

interface InfraAuditLoggerInterface
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
    ): void;
}
