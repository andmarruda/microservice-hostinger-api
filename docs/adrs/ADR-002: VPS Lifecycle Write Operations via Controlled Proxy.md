# ADR-002: VPS Lifecycle Write Operations via Controlled Proxy

## Status

Proposed

## Context

Phase 2 introduces write capabilities that allow the platform to perform lifecycle actions on VPS resources through the Hostinger API.

These operations include actions such as:

* Start / stop / reboot VPS
* Power management
* Configuration changes supported by Hostinger
* Lifecycle-triggering operations that mutate infrastructure state

Because these actions directly affect production infrastructure, they must be executed with strong guarantees around safety, authorization, traceability, and consistency.

The system must ensure:

* Only authorized users can trigger mutations
* Operations are executed through the microservice as a control plane
* Hostinger remains the system of record
* Failures are observable and recoverable

## Decision

All VPS lifecycle write operations will be executed exclusively through a controlled proxy layer implemented in this microservice.

### Key decisions

* Clients never call Hostinger directly
* All mutations require explicit permission checks
* Resource scope must be validated before execution
* Operations must be idempotent where possible
* Each request generates a correlation identifier
* Retries must be safe and bounded
* Hostinger responses are treated as authoritative

Operations will be implemented as explicit commands rather than generic passthrough calls.

## Consequences

### Positive consequences

* Strong control over infrastructure mutations
* Consistent enforcement of authorization rules
* Reduced risk of accidental destructive actions
* Clear operational boundaries
* Easier observability

### Negative consequences

* Slight increase in latency
* Additional implementation complexity
* Need for careful retry design

### Risks and mitigations

| Risk                  | Mitigation                         |
| --------------------- | ---------------------------------- |
| Duplicate operations  | Idempotency keys                   |
| Partial failures      | Retry with backoff                 |
| Unauthorized mutation | Strict scope validation            |
| Drift between systems | Treat Hostinger as source of truth |

## Alternatives Considered

### Direct client to Hostinger access

Rejected due to security risks and loss of audit control.

### Generic proxy endpoint

Rejected because explicit commands reduce misuse and ambiguity.

## Implementation Notes

* Use command-style endpoints
* Enforce permission and scope checks before API calls
* Capture full request context for logging
* Surface clear error semantics

## Related Artifacts

* Foundation Document
* Phase 2 roadmap