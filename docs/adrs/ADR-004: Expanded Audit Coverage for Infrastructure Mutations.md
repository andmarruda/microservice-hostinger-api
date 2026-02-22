# ADR-004: Expanded Audit Coverage for Infrastructure Mutations

## Status

Proposed

## Context

With the introduction of write operations, the audit system must expand to capture infrastructure mutations comprehensively.

Auditability is required for:

* Operational visibility
* Incident response
* Compliance
* Forensics

## Decision

All write operations will generate structured audit events recorded in the log database.

### Key decisions

* Capture who, what, when, and target resource
* Record request and outcome metadata
* Include correlation identifiers
* Ensure logs are append-only
* Preserve audit independence from application state

## Consequences

### Positive consequences

* Improved traceability
* Easier debugging
* Strong compliance posture

### Negative consequences

* Increased storage requirements
* Need for retention policies

### Risks and mitigations

| Risk                 | Mitigation                   |
| -------------------- | ---------------------------- |
| Missing audit events | Centralized logging pipeline |
| Log tampering        | Append-only storage          |
| High volume          | Retention strategy           |

## Alternatives Considered

### Minimal logging

Rejected due to operational risk.

## Implementation Notes

* Define audit event schema
* Emit events synchronously for mutations
* Ensure reliability under failure

## Related Artifacts

* Logging infrastructure
* Observability strategy