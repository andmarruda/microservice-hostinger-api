# ADR-013: Observability and Structured Logging

## Status

Proposed

## Context

Phase 4 introduces enhanced observability. The Foundation document requires:

- Structured application logs
- Correlation or request IDs for tracing requests end-to-end
- Clear separation between application errors and upstream (Hostinger) errors

Currently the service:
- Logs correlation IDs per write operation in `infra_audit_logs`, but not on every request
- Has no structured log format (Laravel's default is unstructured text in development)
- Does not attach a request-level trace ID to all log entries
- Does not distinguish between internal application errors and Hostinger upstream errors in log output
- Has no health check endpoint for uptime monitoring

The Foundation document also requires sufficient visibility for operators including the ability to monitor scheduled task execution and queue worker health (which ADR-011 introduces). None of this currently exists.

## Decision

Observability will be improved across three areas: **structured logging**, **request tracing**, and **health and readiness endpoints**.

### Key decisions

#### Structured logging

- All application log output is formatted as JSON in non-local environments
- Log entries include: `timestamp`, `level`, `message`, `context`, `request_id`, `user_id` (if authenticated), `environment`
- Laravel's `LOG_CHANNEL=stack` with a custom JSON formatter in staging and production
- Application errors (exceptions, validation failures) and upstream errors (Hostinger API failures) are tagged distinctly:
  - Application errors: `"error_source": "application"`
  - Hostinger errors: `"error_source": "hostinger"`, including `status_code` and sanitized response body (no tokens)

#### Request tracing

- A unique `X-Request-ID` header is generated for every request (UUID v4) if not already provided by the caller
- The `X-Request-ID` is attached to:
  - All outbound Hostinger API requests as `X-Correlation-ID`
  - All log entries for the duration of the request
  - The response header `X-Request-ID` so callers can correlate responses to their requests
- Implementation: a dedicated `RequestIdMiddleware` added to the global middleware stack
- Write operations continue to generate their own operation-level correlation IDs (as today); the request ID is the outer envelope

#### Health and readiness endpoints

- `GET /api/health` — returns HTTP 200 if the application is reachable (no auth required, no rate limit)
- `GET /api/health/ready` — returns HTTP 200 if the database is reachable and migrations are up to date; 503 otherwise
- `GET /api/health/queue` — returns HTTP 200 with queue depth and last-processed-at timestamp (root auth required)

#### Scheduled task and queue observability

- Failed jobs are logged at `ERROR` level with structured context (job class, attempt count, exception)
- Drift scan results (ADR-012) are summarized and logged as a structured `INFO` entry after each run
- `WarmHostingerCache` logs cache hit/miss ratio per resource type

## Consequences

### Positive consequences

- Log aggregation tools (Datadog, CloudWatch, Loki) can parse structured JSON without custom parsers
- Request IDs enable end-to-end tracing across microservice boundaries
- Health endpoints enable load balancer and uptime monitoring integration
- Error source tagging makes Hostinger incidents immediately distinguishable from application bugs

### Negative consequences

- JSON log format is harder to read during local development (mitigated by keeping `LOG_CHANNEL=single` for `local` environment)
- Attaching `user_id` to logs requires care to avoid logging PII unnecessarily

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| `X-Request-ID` from caller contains malicious input | Validate format (UUID pattern) before trusting; generate a new one if invalid |
| Logs include sensitive data (tokens, passwords) | Sanitize all log context at the logger level; never log raw request bodies on write endpoints |
| Health endpoint exposes system info | `/api/health/ready` returns minimal info (up/down only); no stack traces |
| High log volume in production | Use `LOG_LEVEL=warning` in production; debug logs only in development |

## Alternatives Considered

### External APM (Datadog, New Relic, Sentry)

Not rejected — these are complementary. This ADR establishes the structured log foundation that makes APM integration effective. APM SDK integration is a follow-up task.

### OpenTelemetry

Not rejected for the future. The `X-Request-ID` approach is intentionally compatible with OpenTelemetry's trace ID propagation pattern. A future ADR may formalize this.

### Keeping Laravel's default logging

Rejected because unstructured text logs cannot be reliably queried or aggregated at scale.

## Implementation Notes

- Add `RequestIdMiddleware` to `bootstrap/app.php` global middleware
- Configure `config/logging.php` with a `json` channel using `Monolog\Formatter\JsonFormatter`
- Add `tap` callback to inject `request_id` and `user_id` into every log record via `Logger::withContext()`
- Health controller: `App\Http\Controllers\HealthController` (not inside any module — infrastructure concern)
- Routes: `GET /api/health`, `GET /api/health/ready`, `GET /api/health/queue` — no `api/v1` prefix (infra route, not versioned)
- `HttpHostingerProxyClient` and `HttpHostingerApiClient` log Hostinger errors with `"error_source": "hostinger"` tag

## Related Artifacts

- ADR-002: VPS Lifecycle Write Operations (correlation IDs)
- ADR-004: Expanded Audit Coverage
- ADR-009: Rate Limiting Strategy
- ADR-011: Scheduled and Automated Tasks (queue observability)
- Foundation Document: Phase 4 roadmap — observability
