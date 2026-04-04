# ADR-009: Rate Limiting Strategy

## Status

Proposed

## Context

The Foundation document requires:

- Global rate limiting across the service
- Heightened protection on sensitive and write-heavy operations
- Limits designed to protect both this service and the Hostinger API from abuse

Without rate limiting, a single authenticated user could:
- Overwhelm the Hostinger API and trigger upstream throttling for all users
- Abuse write endpoints (start/stop/reboot) in rapid succession
- Attempt brute-force login attacks

## Decision

Rate limiting will be applied at three levels using Laravel's built-in rate limiter (`RateLimiter` facade).

### Key decisions

- **Global limit**: 120 requests per minute per IP address (applies to all routes)
- **Authenticated limit**: 300 requests per minute per authenticated user (replaces IP limit after login)
- **Write limit**: 20 requests per minute per authenticated user per VPS ID (applies to lifecycle and security resource mutations)
- **Login limit**: 10 attempts per minute per IP address (extra protection against brute force)
- Rate limits are enforced via Laravel's `throttle` middleware registered in `bootstrap/app.php`
- Hostinger-bound requests use a shared global counter to avoid exceeding Hostinger's own rate limits (implemented in `HttpHostingerApiClient` via a Redis-backed token bucket or simple counter)
- When a limit is exceeded, responses return HTTP 429 with a `Retry-After` header

### Throttle groups

| Group | Limit | Applies to |
|---|---|---|
| `global` | 120/min per IP | All routes |
| `authenticated` | 300/min per user | All authenticated routes |
| `writes` | 20/min per user+vpsId | VPS lifecycle, firewall, SSH key, snapshot mutations |
| `login` | 10/min per IP | `POST /api/v1/auth/login` |

## Consequences

### Positive consequences

- Protection against abuse and accidental API exhaustion
- Predictable behavior under load
- Hostinger rate limits are respected, preventing upstream throttling

### Negative consequences

- Legitimate high-frequency automation tools may hit write limits
- Rate limit state (if Redis-backed) adds an infrastructure dependency

### Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Limits too low for automation | Make limits configurable via environment variables |
| Limits too high to protect Hostinger | Monitor Hostinger API latency and adjust |
| Distributed bypass (multiple IPs) | Authenticated user-based limit is harder to bypass |

## Alternatives Considered

### No rate limiting

Rejected because the Foundation document explicitly requires it.

### External API gateway

Rejected because Laravel's built-in throttling is sufficient for the current scale and avoids added infrastructure complexity.

## Implementation Notes

- Define rate limiter groups in `AppServiceProvider::boot()` using `RateLimiter::for()`
- Apply via `throttle:group-name` middleware on route groups
- Write throttle key: `writes:{userId}:{vpsId}` to scope per-VPS
- Login throttle key: `login:{ip}` 
- Configure limits via env vars: `RATE_LIMIT_GLOBAL`, `RATE_LIMIT_AUTHENTICATED`, `RATE_LIMIT_WRITES`, `RATE_LIMIT_LOGIN`
- Default values should be conservative enough to not affect normal usage

## Related Artifacts

- ADR-007: JWT and Session Authentication
- Foundation Document: Rate Limiting and Security
- Phase 2 roadmap: write operations
