# ADR-003: Management of Firewall Rules, SSH Keys, and Snapshots

## Status

Proposed

## Context

Phase 2 introduces management of security-sensitive VPS resources including:

* Firewall rules
* SSH keys
* Snapshots

These resources directly influence access control, recovery posture, and operational safety.

They require stricter governance compared to standard lifecycle operations.

## Decision

Security resources will be managed through dedicated workflows with explicit permission boundaries and validation rules.

### Key decisions

* Separate permissions from general VPS operations
* Validate changes before execution
* Prevent destructive changes without explicit intent
* Maintain clear mapping between users and actions
* Avoid implicit inheritance of elevated privileges

## Consequences

### Positive consequences

* Reduced risk of security misconfiguration
* Clear separation of responsibilities
* Better operational safety

### Negative consequences

* More granular permission management
* Additional validation complexity

### Risks and mitigations

| Risk             | Mitigation             |
| ---------------- | ---------------------- |
| Firewall lockout | Validation safeguards  |
| SSH key misuse   | Explicit authorization |
| Snapshot sprawl  | Governance policies    |

## Alternatives Considered

### Treat as standard operations

Rejected because security impact is higher.

## Implementation Notes

* Introduce dedicated permission scopes
* Require confirmation for destructive actions
* Log all changes

## Related Artifacts

* Security model
* Authorization layer