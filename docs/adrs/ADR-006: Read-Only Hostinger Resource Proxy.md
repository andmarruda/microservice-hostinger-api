# ADR-006: Read-Only Hostinger Resource Proxy

## Status

Proposed

## Context

Phase 1 of the roadmap includes read-only access to all Hostinger resources supported by the platform: billing, orders, domains, DNS, hosting, reach, and VPS metadata. These endpoints must be accessible to authorized users through this microservice, never directly from Hostinger.

This service acts as a controlled access layer. All read operations must:

- Validate the user's permissions before forwarding to Hostinger
- Normalize Hostinger responses into a consistent service-owned envelope
- Cache non-critical, low-volatility data to reduce upstream API pressure
- Never expose the Hostinger API token to clients

The Foundation document specifies read permissions for each resource area (e.g., `VPS.VirtualMachine.Manage.read`, `Billing.getCatalog`, `Domains.Portfolio.Details`).

## Decision

Read-only Hostinger resource access will be implemented as a **permission-gated proxy** within a dedicated `HostingerProxyModule`.

### Key decisions

- Each resource area has a dedicated controller that checks the required permission before forwarding the request
- Responses from Hostinger are normalized into a consistent `{ data: ... }` envelope
- Hostinger errors are translated into standard error responses (never leaked raw)
- Read results for low-volatility resources (VPS list, datacenters, OS templates) are cached for up to 24 hours using Laravel's cache layer
- Billing data is never cached — always fetched live from Hostinger
- VPS resource visibility is scoped: users without `Manage.Permissions.VPS.all` only see VPS instances in their `vps_access_grants`

### Resource areas and their required permissions

| Resource | Permission required |
|---|---|
| Billing catalog | `Billing.getCatalog` |
| Payment methods | `Orders.PaymentMethods.read` |
| Subscriptions | `Orders.Subscriptions.read` |
| Domain availability | `Domains.Availability.validate` |
| Domain forwarding | `Domains.Forwarding.read` |
| Domain portfolio | `Domains.Portfolio.Details` |
| Domain portfolio manage | `Domains.Portfolio.Manage.read` |
| Whois | `Domains.Whois.read` or `Domains.Whois.list` |
| DNS zone | `DNS.Zone.read` |
| DNS snapshot | `DNS.Snapshot.read` |
| Hosting datacenters | `Hosting.Datacenters.list` |
| Reach contacts | `Reach.Contacts.read` |
| Reach segments | `Reach.Segments.list` |
| VPS list | `VPS.VirtualMachine.Manage.read` |
| VPS details | `VPS.VirtualMachine.Manage.details` |
| VPS metrics | `VPS.VirtualMachine.Manage.metrics` |
| VPS actions | `VPS.Actions.read` |
| VPS backups | `VPS.Backups.read` |
| VPS firewall | `VPS.Firewall.read` |
| VPS OS templates | `VPS.OSTemplates.read` |
| VPS SSH keys | `VPS.PublicKeys.read` |
| VPS snapshots | `VPS.Snapshots.read` |
| VPS datacenters | `VPS.DataCenters.list` |
| VPS post-install scripts | `VPS.PostInstallScripts.read` |

## Consequences

### Positive consequences

- Clients are insulated from upstream Hostinger API changes
- Permissions are enforced centrally before any request is forwarded
- Caching reduces Hostinger API rate-limit pressure
- VPS visibility scoping is enforced transparently

### Negative consequences

- Significant surface area to implement across all resource areas
- Cache invalidation must be managed carefully for time-sensitive data
- Any Hostinger API change requires a coordinated update to the normalization layer

### Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Stale cached data | Short TTL (24h max) and explicit cache invalidation on write operations |
| Leaked Hostinger errors | Always normalize errors before returning to client |
| VPS visibility bypass | Resource scoping enforced at repository level, not controller |
| Rate limit exceeded | Cache read-heavy resources aggressively; monitor upstream latency |

## Alternatives Considered

### Per-area microservices

Rejected due to operational complexity and deployment overhead for an internal service.

### Direct client access with token scoping

Rejected because it exposes the Hostinger token surface and bypasses our permission model.

## Implementation Notes

- Module name: `HostingerProxyModule`
- One controller per logical Hostinger resource area
- Shared `HostingerProxyClient` (or reuse `HttpHostingerApiClient` from VpsModule) for HTTP calls
- Cache key pattern: `hostinger:{resource_type}:{identifier}`
- VPS list endpoint must filter by `vps_access_grants` when user lacks `Manage.Permissions.VPS.all`
- Response normalization: strip Hostinger internal fields, wrap in `{ data: ... }`
- Error normalization: convert Hostinger 4xx/5xx into `{ message: '...', code: '...' }`

## Related Artifacts

- ADR-002: VPS Lifecycle Write Operations
- ADR-005: Role-Based Permission System
- Foundation Document: Data Ownership and Storage Strategy
- Phase 1 roadmap: read-only access to Hostinger resources
