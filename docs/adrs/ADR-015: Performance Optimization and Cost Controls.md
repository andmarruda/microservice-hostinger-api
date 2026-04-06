# ADR-015: Performance Optimization and Cost Controls

## Status

Proposed

## Context

Phase 4 introduces performance optimization and cost controls. As the platform scales and the number of teams and VPS instances grows, several performance and cost risks emerge:

- **Hostinger API quota exhaustion**: Every cache miss, every drift scan, every scheduled cache warming run consumes Hostinger API quota. Without visibility into consumption, the platform can unexpectedly exhaust quota and cause service degradation for all users.
- **Unbounded database growth**: Audit logs, drift reports, and access reviews accumulate indefinitely. Without size controls, database costs and query performance degrade over time.
- **Slow proxy responses under load**: The read-only proxy (ADR-006) makes synchronous Hostinger API calls on cache misses. Under sustained load, these calls become a latency bottleneck.
- **No visibility into cache effectiveness**: There is currently no way to know if the 24-hour cache TTL is actually reducing Hostinger API calls, or if cache misses are frequent enough to warrant tuning.
- **No budget guardrails**: A user with broad permissions could inadvertently trigger high-cost Hostinger operations (e.g., repeated VPS recreate) without any operational visibility.

The Foundation document specifies "performance and cost optimization" as a Phase 4 capability.

## Decision

Performance and cost controls will be introduced across four areas: **Hostinger API quota tracking**, **cache observability and tuning**, **database size management**, and **response time budgets**.

### Key decisions

#### Hostinger API quota tracking

- Every outbound request to Hostinger increments a Redis counter keyed by `hostinger:api:calls:{date}` and `hostinger:api:calls:{date}:{resource_type}`
- Counter TTL: 48 hours (covers rolling daily window)
- `GET /api/v1/ops/quota` (root only) returns daily API call counts by resource type and total
- A configurable warning threshold (`HOSTINGER_API_QUOTA_WARN_AT`, default: 800 calls/day) triggers a structured `WARNING` log entry when crossed
- If `HOSTINGER_API_QUOTA_HARD_LIMIT` is set, requests are rejected with HTTP 503 once the daily limit is reached, rather than risk upstream quota exhaustion

#### Cache observability and tuning

- Each cache `remember()` call is wrapped in a thin instrumentation layer that logs a structured `DEBUG` entry: `cache_hit` or `cache_miss` with `resource_type` and `cache_key`
- `GET /api/v1/ops/cache-stats` (root only) returns hit/miss counts per resource type for the current day (sourced from Redis counters)
- Cache TTLs are promoted to environment variables per resource type:
  - `CACHE_TTL_VPS_LIST` (default: 86400)
  - `CACHE_TTL_OS_TEMPLATES` (default: 86400)
  - `CACHE_TTL_DATACENTERS` (default: 86400)
  - `CACHE_TTL_DOMAIN_AVAILABILITY` (default: 3600)
- Operators can tune TTLs without a code deploy

#### Database size management

- Each table with unbounded growth has a configurable retention policy enforced by ADR-011's scheduler:

| Table | Env var | Default |
|---|---|---|
| `infra_audit_logs` | `AUDIT_LOG_RETENTION_DAYS` | 365 |
| `auth_audit_logs` | `AUTH_LOG_RETENTION_DAYS` | 365 |
| `drift_reports` | `DRIFT_REPORT_RETENTION_DAYS` | 90 |
| `access_reviews` | `ACCESS_REVIEW_RETENTION_DAYS` | 730 |
| `failed_jobs` | `FAILED_JOB_RETENTION_DAYS` | 30 |

- Pruning uses chunked deletes (`chunkById(500)`) to avoid long table locks
- `GET /api/v1/ops/db-stats` (root only) returns row counts and estimated sizes for all managed tables

#### Response time budgets

- All outbound Hostinger API calls enforce a per-request timeout: `HOSTINGER_API_TIMEOUT_SECONDS` (default: 10)
- Requests exceeding the timeout are aborted and treated as `hostinger_error` — never hang indefinitely
- A `X-Response-Time` header is added to all API responses (milliseconds) via response middleware
- Slow requests (above `SLOW_REQUEST_THRESHOLD_MS`, default: 2000) are logged as `WARNING` with structured context including the route and user ID

### Ops endpoint summary

| Endpoint | Auth | Description |
|---|---|---|
| `GET /api/v1/ops/quota` | root | Daily Hostinger API call counts by resource type |
| `GET /api/v1/ops/cache-stats` | root | Cache hit/miss counts per resource type |
| `GET /api/v1/ops/db-stats` | root | Row counts and retention config for managed tables |

## Consequences

### Positive consequences

- Operators can detect Hostinger quota pressure before it causes user-facing failures
- Cache effectiveness is measurable — TTLs can be tuned with data rather than guesswork
- Unbounded database growth is controlled, reducing infrastructure costs over time
- Response time visibility enables SLA monitoring without external APM tools

### Negative consequences

- Redis is now a hard dependency for quota tracking and cache stats (previously optional)
- Instrumentation adds a small overhead to every cache read/write and Hostinger API call
- Timeout enforcement may surface latency issues with Hostinger that were previously hidden by long hangs

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| Redis unavailable causes quota counter failures | Fail open — log a warning, do not block the request |
| Aggressive pruning deletes records needed for audit | Retention defaults are conservative; operators must explicitly shorten them |
| Hard quota limit causes legitimate requests to be rejected | Hard limit is opt-in (`HOSTINGER_API_QUOTA_HARD_LIMIT` unset by default); warn-only is the default behavior |
| Response time header leaks internal timing details | `X-Response-Time` is a standard header; does not expose stack traces or internal structure |

## Alternatives Considered

### External observability platform for quota and performance tracking

Not rejected for the future — these ops endpoints are designed to be lightweight internal tools. When the platform reaches a scale where a full observability platform is justified, the structured log output from ADR-013 is the integration point.

### Always-on hard quota limit

Rejected as the default because operational teams need time to baseline actual call volumes before setting a limit. Warning-only is the safe default.

### Partitioned tables instead of pruning

Rejected for now due to added complexity. Chunked pruning is sufficient at the current scale. Partitioning can be introduced in a future ADR if table sizes warrant it.

## Implementation Notes

- Redis counter increment: `Redis::incr("hostinger:api:calls:{date}")` in `HttpHostingerProxyClient` and `HttpHostingerApiClient` base call methods
- Cache instrumentation: wrap `Cache::remember()` with a helper that increments `hostinger:cache:{resource}:hit` or `hostinger:cache:{resource}:miss` Redis counters
- `OpsController` serves all three ops endpoints; registered in a new `OpsModule` or as part of `GovernanceModule` (implementation decision at time of development)
- `X-Response-Time` middleware measures from `LARAVEL_START` constant to response send time
- Timeout: `Http::timeout(config('services.hostinger.timeout', 10))` added to all Hostinger HTTP client calls
- Environment variables documented in `.env.example` with safe defaults

## Related Artifacts

- ADR-006: Read-Only Hostinger Resource Proxy (cache TTLs)
- ADR-009: Rate Limiting Strategy (Hostinger-side rate limit awareness)
- ADR-011: Scheduled and Automated Tasks (retention pruning)
- ADR-013: Observability and Structured Logging (slow request logging)
- ADR-014: Compliance and Governance Tooling (audit log retention)
- Foundation Document: Phase 4 roadmap — performance and cost optimization
