# ADR-010: Policy-Driven Enforcement

## Status

Proposed

## Context

Phase 3 of the roadmap introduces policy-driven enforcement: the ability to restrict operations not just by permission, but by configurable rules that govern *when*, *where*, and *how* operations may be executed.

Current enforcement is binary — a user either has a permission or does not. This is insufficient for scenarios such as:

- Restricting VPS operations to allowed geographic regions (e.g., no instances in regions outside the approved list)
- Preventing destructive actions (recreate, password reset) during a defined maintenance freeze window
- Enforcing action-level constraints per team or per VPS (e.g., a team may start/stop but never recreate their VPS)
- Rejecting operations targeting resource types outside the organization's policy (e.g., no Windows OS templates)

The Foundation document explicitly calls for "policy-driven enforcement (for example, restricted regions or actions)" as a Phase 3 capability.

## Decision

A **PolicyModule** will be introduced to evaluate configurable enforcement rules before any write operation is forwarded to Hostinger.

### Key decisions

- Policies are stored locally and evaluated server-side before the request reaches the use case
- Policy rules are scoped to: action type, resource type, target VPS, user role, and optionally time window
- A policy check returns one of: `allowed`, `denied` (with reason), or `not_applicable`
- Policies do not replace permissions — they layer on top. A user must still have the permission AND pass policy evaluation
- Policies are managed via API by users with the `root` role
- Policies apply globally by default and can be narrowed to specific VPS IDs, roles, or user IDs
- Freeze windows are time-bounded policies that deny all write operations during a defined interval

### Policy rule structure

Each policy rule contains:

| Field | Description |
|---|---|
| `action` | The operation being evaluated (e.g., `vps.recreate`, `vps.start`) |
| `scope` | `global`, `vps:{id}`, `role:{name}`, or `user:{id}` |
| `effect` | `deny` (allow is the default when no rule matches) |
| `reason` | Human-readable explanation returned to the client |
| `active_from` | Optional start of enforcement window (ISO 8601) |
| `active_until` | Optional end of enforcement window (ISO 8601) |

### Policy evaluation order

1. If no policies match the action + scope: **allowed**
2. If any matching policy has effect `deny` and is within its time window: **denied**
3. Otherwise: **allowed**

### Integration point

Policy evaluation is injected into use cases as a `PolicyEnforcerInterface` port, called after permission check and before the Hostinger API call:

```
permission check → policy check → correlationId → Hostinger API → audit log
```

## Consequences

### Positive consequences

- Fine-grained operational control without modifying permissions
- Freeze windows enable safe deployment and maintenance workflows
- Region and OS restrictions reduce risk of non-compliant infrastructure
- Policy decisions are auditable (stored in infra_audit_logs with `policy_denied` outcome)

### Negative consequences

- Policy management requires a separate API and UI surface
- Complex policy interactions (overlapping scopes) can produce surprising behavior
- Time-window policies depend on server clock accuracy

### Risks and mitigations

| Risk | Mitigation |
|---|---|
| Policies block legitimate operations silently | Always return `reason` in 403 response body |
| Overlapping policies produce inconsistent behavior | Document evaluation order clearly; most-specific scope wins |
| Clock skew causes incorrect freeze window enforcement | Use UTC exclusively; document timezone behavior |
| Root user locked out by policy | Root role is exempt from all policy rules |

## Alternatives Considered

### Embedding policy logic in each use case

Rejected because it scatters policy rules across the codebase, making them impossible to manage centrally or audit.

### External policy engine (OPA, Casbin)

Rejected due to operational overhead. Local database-backed policies are sufficient for the current scale and avoid adding infrastructure dependencies.

### Expanding the permission model instead

Rejected because permissions answer "can this user do this?" while policies answer "is this operation allowed right now in this context?" — they are orthogonal concerns.

## Implementation Notes

- Module: `PolicyModule`
- Table: `enforcement_policies` (id, action, scope_type, scope_id, effect, reason, active_from, active_until, created_by, timestamps)
- Port: `PolicyEnforcerInterface::evaluate(string $action, int $userId, ?string $vpsId): PolicyDecision`
- `PolicyDecision`: readonly DTO — `allowed: bool`, `reason: ?string`
- Root role is exempt from all policy evaluation
- Policy violations are logged to `infra_audit_logs` with `outcome: policy_denied`
- CRUD endpoints for policies: `POST/GET/DELETE /api/v1/policies` (root only)

## Related Artifacts

- ADR-005: Role-Based Permission System
- ADR-002: VPS Lifecycle Write Operations
- ADR-003: Security Resource Management
- ADR-004: Expanded Audit Coverage
- Foundation Document: Phase 3 roadmap
