<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Audit Log Retention
     |--------------------------------------------------------------------------
     |
     | Number of days to keep audit log entries before they are pruned by the
     | PruneAuditLogs scheduled job. Set via AUDIT_LOG_RETENTION_DAYS.
     |
     */
    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 90),
];
