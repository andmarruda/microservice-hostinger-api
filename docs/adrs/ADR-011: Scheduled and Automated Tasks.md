# ADR-011: Scheduled and Automated Tasks

## Status

Proposed

## Context

Phase 3 introduces scheduled and automated tasks: background operations that run on a defined schedule without requiring a human-initiated HTTP request.

Current use cases requiring automation include:

- **Invitation expiration**: Invitations that are never accepted must be marked expired so they cannot be used. ADR-001 deferred this.
- **Cache warming**: Pre-populating caches for low-volatility Hostinger resources (VPS list, OS templates, datacenters) at a defined interval to prevent cache misses from adding latency to the first request.
- **Audit log retention**: Pruning `infra_audit_logs` and `auth_audit_logs` entries older than a configurable retention period.
- **Stale grant cleanup**: Detecting and flagging `vps_access_grants` that reference VPS IDs no longer present in Hostinger (a precursor to drift detection in ADR-012).

The Foundation document specifies "scheduled or automated tasks" as a Phase 3 capability. Laravel's built-in scheduler and queue system provide the required infrastructure without adding external dependencies.

## Decision

Scheduled and automated tasks will be implemented using **Laravel's task scheduler** (`app/Console/Kernel.php` or `routes/console.php`) backed by **Laravel queues** for tasks that must run asynchronously.

### Key decisions

- All scheduled tasks are defined in a single `SchedulerServiceProvider` loaded by the application
- Tasks that interact with external systems (Hostinger API) or perform bulk database writes run as queued jobs, not synchronously in the scheduler
- The scheduler triggers the queue dispatch; the queue worker executes the actual work
- Failed jobs are retried up to 3 times with exponential backoff and logged to the `failed_jobs` table
- Tasks are individually togglable via environment variables (e.g., `TASK_EXPIRE_INVITATIONS_ENABLED=true`)

### Scheduled tasks

| Task | Schedule | Mechanism | Description |
|---|---|---|---|
| `ExpireInvitations` | Every hour | Sync (DB only) | Mark invitations past their expiry as `expired` |
| `WarmHostingerCache` | Every 12 hours | Queued job | Fetch and cache VPS list, OS templates, datacenters |
| `PruneAuditLogs` | Daily at 02:00 UTC | Queued job | Delete audit log entries older than `AUDIT_LOG_RETENTION_DAYS` (default: 365) |
| `FlagStaleAccessGrants` | Daily at 03:00 UTC | Queued job | Compare `vps_access_grants` against live Hostinger VPS list; flag unresolved grants |

### Queue configuration

- Default queue driver: `database` (zero additional infrastructure)
- Production recommendation: `redis` for throughput and reliability
- Failed jobs: `failed_jobs` table, retried 3× with 60s backoff
- Queue name: `default` for non-critical tasks; `critical` reserved for future use

## Consequences

### Positive consequences

- Invitation expiration is handled automatically, closing the gap from ADR-001
- Cache warming reduces first-request latency after cache expiry
- Audit log pruning prevents unbounded database growth
- Stale grant flagging provides early signal for drift (feeds ADR-012)

### Negative consequences

- Requires a queue worker process running alongside the web server in production
- Scheduled task failures are silent without proper monitoring (feeds ADR-013)
- Database-backed queues add write load during bulk operations

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| Queue worker dies silently | Supervisor or systemd process monitor; alert on queue depth |
| `WarmHostingerCache` fails and cache goes stale | Cache miss falls back to live fetch; task failure is logged |
| `PruneAuditLogs` deletes too aggressively | Configurable retention period; default is conservative (365 days) |
| Scheduler clock drift causes task overlap | Use `withoutOverlapping()` on all scheduled commands |

## Alternatives Considered

### Cron jobs at the OS level

Rejected because it splits operational configuration between the application and the infrastructure layer. Laravel's scheduler keeps all task definitions in code, making them version-controlled and testable.

### External task runner (Celery, Sidekiq, AWS Lambda)

Rejected due to operational overhead and additional infrastructure dependencies. Laravel's queue system is sufficient for the current workload.

### Running tasks synchronously in the scheduler

Rejected for tasks that make external HTTP calls (Hostinger API). Synchronous execution in the scheduler blocks the scheduler loop and cannot be retried independently on failure.

## Implementation Notes

- Define scheduled tasks in `routes/console.php` using `Schedule::job()` or `Schedule::command()`
- Each job class lives in `app/Jobs/` (not inside a module) since they are cross-cutting operational concerns
- Use `withoutOverlapping(10)` (10-minute lock) on all scheduled dispatches
- Add `TASK_*_ENABLED` environment variable checks at the top of each job's `handle()` method
- `FlagStaleAccessGrants` writes a `stale_at` timestamp to the `vps_access_grants` table — requires a migration adding this nullable column
- `PruneAuditLogs` uses chunked deletes (`chunkById`) to avoid long table locks

## Related Artifacts

- ADR-001: Invitation-Based User Registration (expiration deferred)
- ADR-004: Expanded Audit Coverage (log retention)
- ADR-006: Read-Only Hostinger Resource Proxy (cache warming)
- ADR-012: Drift Detection (stale grant flagging feeds this)
- Foundation Document: Phase 3 roadmap
