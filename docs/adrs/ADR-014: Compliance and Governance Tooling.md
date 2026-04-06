# ADR-014: Compliance and Governance Tooling

## Status

Proposed

## Context

Phase 4 introduces compliance and governance tooling. As the platform matures and more teams onboard, operators and auditors need confidence that:

- Access grants are periodically reviewed and not silently accumulated
- Audit logs are tamper-evident and retained according to organizational policy
- Permission assignments follow a least-privilege model that can be demonstrated to auditors
- Sensitive operations (VPS recreate, snapshot delete, invitation management) have a clear chain of accountability
- The platform can produce compliance reports on demand without requiring direct database access

Currently:
- Audit logs exist (`infra_audit_logs`, `auth_audit_logs`) but have no export, no retention enforcement, and no review workflow
- Access grants accumulate indefinitely with no expiry or periodic review mechanism
- There is no way to produce a point-in-time view of who had access to what resource
- Permission assignments have no approval workflow — a root user can grant access without any secondary confirmation

The Foundation document specifies "compliance and governance tooling" as a Phase 4 capability.

## Decision

Governance will be introduced through four mechanisms: **access grant expiry**, **periodic access reviews**, **audit log export**, and **permission change approval workflows**.

### Key decisions

#### Access grant expiry

- `vps_access_grants` and `security_permissions` tables gain optional `expires_at` columns
- Expired grants are not auto-deleted — they are flagged as `expired` and excluded from access checks
- Expiry is enforced at the repository layer: `userHasAccess()` and `canManage*()` checks include `WHERE expires_at IS NULL OR expires_at > NOW()`
- Grants are created with a default expiry of `ACCESS_GRANT_DEFAULT_TTL_DAYS` (default: none, configurable)
- Root users can set, extend, or remove expiry on any grant

#### Periodic access reviews

- A scheduled job (via ADR-011) generates an **access review** every `ACCESS_REVIEW_INTERVAL_DAYS` (default: 90)
- An access review is a snapshot of all active grants at a point in time, stored in a `access_reviews` table
- Root users are notified (via log entry + optional webhook) when a review is due
- Reviews are surfaced via `GET /api/v1/governance/access-reviews` (root only)
- Each review item can be marked `confirmed` or `revoked` via `POST /api/v1/governance/access-reviews/{id}/items/{itemId}`

#### Audit log export

- `GET /api/v1/governance/audit-export` (root only) returns a paginated, filterable export of audit log entries
- Supported filters: `actor_id`, `vps_id`, `action`, `outcome`, `from`, `until`, `resource_type`
- Response format: JSON (default) or CSV via `Accept: text/csv`
- Exports are streamed for large result sets to avoid memory exhaustion
- Export actions are themselves recorded in `infra_audit_logs` with action `audit_export`

#### Permission change approval (future gate)

- When `PERMISSION_APPROVAL_REQUIRED=true`, root-initiated permission grants create a pending `permission_approval` record rather than applying immediately
- A second root user must approve via `POST /api/v1/governance/approvals/{id}/approve`
- Approvals expire after `PERMISSION_APPROVAL_TTL_HOURS` (default: 48)
- This is a **soft gate** — disabled by default, enabled per-organization via environment variable
- When disabled, permission grants apply immediately as today

### Governance table summary

| Table | Purpose |
|---|---|
| `access_reviews` | Periodic snapshots of all active grants |
| `access_review_items` | Individual grant entries within a review |
| `permission_approvals` | Pending two-party permission grant confirmations |

### Grant expiry additions to existing tables

- `vps_access_grants.expires_at` — nullable timestamp
- `security_permissions.expires_at` — nullable timestamp

## Consequences

### Positive consequences

- Access accumulation is bounded by expiry and periodic review
- Audit exports satisfy external auditor requests without granting database access
- Two-party approval for permission grants reduces the blast radius of a compromised root account
- Point-in-time access reviews provide a defensible record for compliance questionnaires

### Negative consequences

- Grant expiry requires all existing access check queries to be updated — high blast radius change
- Access review notifications require a notification channel (email or webhook) not yet defined
- Two-party approval adds friction to routine permission management when enabled

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| Expired grants silently break user access | Notify grant owner 7 days before expiry via audit log entry; surface expiring grants in `/api/v1/governance/access-reviews` |
| Audit export produces massive response | Always paginate; default page size 500; stream CSV |
| Approval workflow introduces a single point of failure if only one root exists | Approval is opt-in; disabled by default; document the risk |
| `expires_at` enforcement missed in a query path | Add integration test asserting expired grants are rejected; enforce at repository layer, not controller |

## Alternatives Considered

### Auto-delete expired grants

Rejected because silent deletion of access grants could cause operational incidents when a grant is accidentally left to expire. Flagging as expired and requiring explicit cleanup preserves the audit trail.

### External IAM / governance platform (OPA, HashiCorp Vault)

Rejected due to operational overhead and the limited scope of this service. The built-in governance mechanisms are sufficient for the current organizational scale.

### No governance tooling until required by an audit

Rejected because retrofitting tamper-evident audit exports and access review workflows after an audit finding is significantly more expensive than building them proactively.

## Implementation Notes

- Add `expires_at` to `vps_access_grants` and `security_permissions` via new migration files in each module
- Update `EloquentVpsRepository::userHasAccess()` and `EloquentSecurityPermissionService::canManage*()` to filter expired records
- `GovernanceModule` handles access reviews, approvals, and audit export — new module alongside existing ones
- `GovernanceModuleServiceProvider` loads migrations and routes
- Audit export endpoint uses Laravel's `StreamedResponse` with chunked DB reads
- All governance endpoints require `Manage.Permissions.read` at minimum; audit export requires `Manage.Permissions.all` equivalent (root only)

## Related Artifacts

- ADR-002: VPS Lifecycle Write Operations
- ADR-003: Security Resource Management
- ADR-004: Expanded Audit Coverage
- ADR-005: Role-Based Permission System
- ADR-011: Scheduled and Automated Tasks (access review scheduling)
- ADR-012: Drift Detection (complements access review)
- Foundation Document: Phase 4 roadmap — compliance and governance
