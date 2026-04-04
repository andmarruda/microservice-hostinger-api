# ADR-007: JWT and Session Authentication

## Status

Proposed

## Context

The Foundation document specifies that the platform must support two authentication mechanisms:

- **Session-based authentication** for web clients (internal dashboards, admin panels)
- **JWT-based authentication** for API consumers (automation tools, CI/CD workflows)

Currently, the AuthModule implements invitation-based registration and account creation, but does not define an authentication flow. Controllers check `$request->user()` for basic presence, but there is no login, token issuance, or session management endpoint.

Both mechanisms must be secure, with all endpoints requiring authentication (no anonymous access beyond invitation acceptance and registration).

## Decision

The platform will support session-based and JWT-based authentication through a unified `AuthModule` login flow.

### Key decisions

- Session authentication will use Laravel's built-in session guard (cookie-based, suited for web clients)
- JWT authentication will use **Laravel Sanctum** in token mode (lightweight, no external JWT library required)
- A single `POST /auth/login` endpoint accepts credentials and issues either a session or a Sanctum API token depending on the `Accept` header or an explicit `mode` parameter
- Token expiry is configurable via environment variable (`SANCTUM_TOKEN_EXPIRY_MINUTES`)
- Logout invalidates the session or revokes the Sanctum token
- All endpoints except `/invitations/accept`, `/users/register`, and `/auth/login` require authentication
- Authentication state is determined by Laravel's `auth` middleware, not manual `$request->user()` checks in controllers — controllers will be refactored to use middleware

### Endpoints

| Method | Path | Description |
|---|---|---|
| POST | `/auth/login` | Accept credentials, return session or Sanctum token |
| POST | `/auth/logout` | Invalidate current session or revoke Sanctum token |
| GET | `/auth/me` | Return the currently authenticated user's profile |

### Token handling

- Sanctum tokens are stored hashed in the `personal_access_tokens` table
- Tokens are never logged or returned after initial issuance
- Expired tokens are automatically rejected by Sanctum

## Consequences

### Positive consequences

- Unified login flow supports both web and API consumers
- Sanctum is a first-party Laravel package with no additional dependencies
- Session cookies are HTTP-only and secure by default
- Token issuance is auditable (stored in database)

### Negative consequences

- Controllers must be refactored to remove manual `$request->user()` null checks (replaced by middleware)
- Token management UI may be needed for API consumers to rotate tokens

### Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Token theft | Short expiry + revocation endpoint |
| Session fixation | Laravel's session regeneration on login |
| Brute-force login | Rate limiting on `/auth/login` (see ADR-009) |
| Long-lived tokens | Configurable expiry; tokens scoped to abilities |

## Alternatives Considered

### Standalone JWT library (e.g., tymon/jwt-auth)

Rejected because Sanctum is first-party, lighter weight, and better integrated with Laravel's auth guards.

### API keys only (no session support)

Rejected because the Foundation document explicitly requires session support for web clients.

## Implementation Notes

- Add `Laravel\Sanctum\HasApiTokens` trait to `User` model
- Publish Sanctum migrations
- Register Sanctum middleware in `bootstrap/app.php` for API routes
- Add `LoginUser` use case to `AuthModule` with `LoginUserResult` DTO
- Add `LogoutUser` use case to `AuthModule`
- Refactor existing controllers to rely on `auth` middleware instead of checking `$request->user()` manually
- Add `auth` middleware to route groups in VpsModule and SecurityResourceModule
- Audit: log successful and failed login attempts to `auth_audit_logs`

## Audit and Compliance

The following actions must be audited:
- Successful login (actor, IP, user agent, method: session|token)
- Failed login attempt (email, IP, user agent)
- Logout (actor, token/session ID)

## Open Questions (Deferred)

- Should token abilities be scoped per permission (e.g., a token with only `VPS.Firewall.read`)?
- Should session-based auth require 2FA in production?
- What is the maximum token lifetime for API consumers?

## Related Artifacts

- ADR-001: Invitation-Based User Registration
- ADR-005: Role-Based Permission System
- Foundation Document: Security and Compliance Basics
- Phase 1 roadmap: authentication and authorization
