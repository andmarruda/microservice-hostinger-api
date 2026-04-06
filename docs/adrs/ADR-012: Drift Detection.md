# ADR-012: Drift Detection

## Status

Proposed

## Context

This microservice controls access to Hostinger resources via locally managed grants (`vps_access_grants`, `security_permissions`). Hostinger remains the source of truth for actual infrastructure state.

Over time, the local state and Hostinger's state can diverge — this is called **drift**:

- A VPS is deleted in Hostinger but its access grants remain locally
- A VPS is created in Hostinger but no grants exist, making it invisible to all users
- A user's access grants reference a VPS ID that was recreated with a different configuration in Hostinger
- SSH keys or firewall rules managed via this service differ from what Hostinger currently reports

Undetected drift causes:
- Users being denied access to VPS instances they should have
- Phantom grants that cannot be exercised (VPS no longer exists in Hostinger)
- Security configuration assumptions becoming invalid silently

The Foundation document specifies "drift detection between expected and actual infrastructure state" as a Phase 3 capability.

## Decision

Drift detection will be implemented as a **scheduled comparison job** that reconciles local grants against the live Hostinger state and surfaces discrepancies via the audit log and a dedicated drift report API.

### Key decisions

- Drift is detected, not auto-resolved — the service reports drift and lets a user with appropriate permissions resolve it
- Auto-resolution is explicitly excluded to prevent silent data loss (e.g., auto-deleting a grant because a VPS was temporarily unavailable)
- Drift detection runs as a scheduled job (dispatched by ADR-011's scheduler)
- Results are persisted to a `drift_reports` table with one row per detected discrepancy
- Open drift items are surfaced via a `GET /api/v1/drift` endpoint (root only)
- Drift items are resolved manually via `POST /api/v1/drift/{id}/resolve` or `POST /api/v1/drift/{id}/dismiss`

### Types of drift detected

| Drift type | Description | Severity |
|---|---|---|
| `orphaned_grant` | `vps_access_grants` references a VPS ID not in Hostinger | High |
| `undiscovered_vps` | Hostinger has a VPS with no local grants (invisible to all users) | Medium |
| `orphaned_security_permission` | `security_permissions` references a VPS ID not in Hostinger | High |
| `ssh_key_mismatch` | SSH keys managed via this service differ from Hostinger's reported keys | Medium |
| `firewall_mismatch` | Firewall rules differ between local security operations log and Hostinger state | Medium |

### Drift detection flow

1. Fetch live VPS list from Hostinger (uses cached data if fresh, otherwise fetches live)
2. Load all local `vps_access_grants` and `security_permissions`
3. Compare sets, identify orphaned and undiscovered items
4. For each VPS in grants: optionally fetch SSH keys and firewall from Hostinger and compare against last-known state from `infra_audit_logs`
5. Persist new drift items to `drift_reports`; skip items already open with no resolution
6. Log the drift detection run to `infra_audit_logs` with action `drift_scan`

### Drift report schema

`drift_reports` table:

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `drift_type` | string | One of the types above |
| `severity` | string | `high`, `medium`, `low` |
| `vps_id` | string | Affected VPS ID |
| `user_id` | bigint nullable | Affected user (for grant-level drift) |
| `details` | json | Context-specific data |
| `status` | string | `open`, `resolved`, `dismissed` |
| `detected_at` | timestamp | When drift was first detected |
| `resolved_at` | timestamp nullable | When it was resolved or dismissed |
| `resolved_by` | bigint nullable | User who resolved it |

## Consequences

### Positive consequences

- Operators gain visibility into stale or phantom grants before they cause user-facing issues
- Drift reports provide an audit trail of infrastructure divergence over time
- Complements ADR-011's `FlagStaleAccessGrants` task with deeper inspection

### Negative consequences

- Drift detection makes live API calls to Hostinger on each run (rate-limit pressure mitigated by caching)
- SSH key and firewall mismatch detection requires keeping enough audit log history to reconstruct expected state
- False positives are possible during VPS provisioning (VPS exists but is not yet fully visible)

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| Hostinger API unavailability causes false orphan detection | Skip drift report creation if Hostinger fetch fails; log scan error instead |
| VPS in provisioning state incorrectly flagged as undiscovered | Exclude VPS instances in non-active states from undiscovered detection |
| Drift scan runs while VPS is being created | Use `withoutOverlapping()` and buffer with a 15-minute grace period on new VPS IDs |
| Drift report table grows unboundedly | Auto-archive resolved/dismissed items older than 90 days |

## Alternatives Considered

### Auto-resolution (delete orphaned grants automatically)

Rejected because a VPS temporarily unavailable in Hostinger could cause legitimate grants to be silently deleted. Human review is required for all resolution.

### Webhook-based real-time reconciliation

Rejected because Hostinger's public API does not expose infrastructure lifecycle webhooks at the required granularity.

### Embedded checks in every use case

Rejected because it adds latency to every request and duplicates detection logic. A periodic background scan is more efficient and less intrusive.

## Implementation Notes

- Job class: `app/Jobs/RunDriftScan.php`
- Scheduled daily via ADR-011's scheduler at 04:00 UTC
- Requires `Manage.Permissions.VPS.all` permission to call drift API endpoints
- `GET /api/v1/drift` supports `?status=open|resolved|dismissed` and `?severity=high|medium|low` filters
- `POST /api/v1/drift/{id}/resolve` deletes the grant or permission row after confirmation
- `POST /api/v1/drift/{id}/dismiss` marks the item as acknowledged without acting on local data
- SSH/firewall mismatch detection is opt-in via `DRIFT_CHECK_SECURITY_RESOURCES=true` (adds significant API call volume)

## Related Artifacts

- ADR-002: VPS Lifecycle Write Operations
- ADR-003: Security Resource Management
- ADR-004: Expanded Audit Coverage
- ADR-006: Read-Only Hostinger Resource Proxy
- ADR-011: Scheduled and Automated Tasks
- Foundation Document: Phase 3 roadmap — drift detection
