# ADR-001: Invitation-Based User Registration

## Status
Accepted

## Context

The platform must support onboarding new team members in a secure, controlled, and auditable manner.

Because this service manages access to production infrastructure and Hostinger resources, unrestricted or self-service user registration is not acceptable.

Access must be explicitly granted by authorized managers, with clear ownership over:
- Who invited the user
- When the invitation was issued
- What resources the user is allowed to access

This decision is driven by **User Story 001 – User Registration**, which requires invitation-based account creation.

## Decision

User registration will be implemented exclusively through an **invitation-based onboarding flow**.

### Key decisions

- Users cannot self-register or create accounts without an invitation
- Only users with managerial-level permissions can create invitations
- Invitations are delivered via email
- Each invitation:
  - Is single-use
  - Has a defined expiration time
  - Is linked to a predefined resource scope
- Accepting an invitation allows account creation only
- No infrastructure or elevated permissions are granted implicitly during registration

Resource visibility and permissions are enforced immediately after account creation, based on the scope defined at invitation time.

## Consequences

### Positive consequences

- Strong control over who can access the platform
- Clear audit trail for onboarding actions
- Reduced risk of unauthorized infrastructure access
- Predictable and repeatable onboarding process
- Permissions and resource scope are explicit from the first login

### Negative consequences

- Slightly higher onboarding friction compared to open registration
- Requires managing invitation lifecycle (creation, expiration, acceptance)
- Dependence on email delivery for onboarding

### Risks and mitigations

| Risk | Mitigation |
|----|----|
| Invitation misuse | Single-use tokens and expiration |
| Over-permissioning | Explicit resource scoping at invitation time |
| Email delivery failure | Clear resend and error handling |
| Stale invitations | Automatic expiration |

## Alternatives Considered

### Open self-registration
Rejected due to lack of control, high security risk, and inability to scope resources safely.

### Manual account creation by administrators
Rejected due to poor scalability, higher operational overhead, and reduced traceability.

### Invitation without predefined resource scope
Rejected because it introduces ambiguity and increases the risk of accidental over-permissioning.

## Implementation Notes

- Invitations must generate a cryptographically secure token
- Tokens must be validated for:
  - Existence
  - Expiration
  - Single-use status
- Invitation acceptance must be idempotent
- Failed or reused tokens must return explicit errors
- All invitation-related actions must be logged

## Audit and Compliance

The following actions must be audited:
- Invitation creation
- Invitation acceptance
- Invitation expiration or revocation (future)

Audit logs must include:
- Inviter identity
- Invitee email
- Timestamp
- Assigned resource scope

## Open Questions (Deferred)

The following questions are intentionally deferred and may result in future ADRs:

- What is the default invitation expiration duration?
- Can invitations be revoked before acceptance?
- Should re-inviting an email invalidate previous invitations?
- Should invitation acceptance trigger additional verification steps?
- Are channels other than email required in the future?

## Related Artifacts

- User Story: User Story 001 – User Registration
- Foundation Document: FOUNDATION.md
