# ADR-005: Role-Based Permission System with Spatie Laravel Permissions

## Status

Proposed

## Context

The Foundation document defines a rich permission model aligned to the Hostinger API surface. Currently, the AuthModule implements a simple `is_manager` boolean flag on users, which is insufficient for the granular access control described in the platform vision.

The Foundation specifies:

- A root role with full access
- Custom roles (e.g., manager, operator, read-only) defined by root
- Per-resource, per-operation permissions (e.g., `VPS.Firewall.read`, `VPS.VirtualMachine.Manage.start`)
- Resource-level scoping: users may have access to all VPS instances or only explicitly assigned ones
- `Manage.Permissions.*` permissions for granting and revoking access

The existing `is_manager` boolean cannot represent this level of granularity and must be superseded.

## Decision

The platform will adopt **Spatie Laravel Permissions** as the authoritative role and permission management layer.

### Key decisions

- All permissions are defined as named strings matching the Hostinger API surface (e.g., `VPS.VirtualMachine.Manage.start`)
- Roles are created and managed exclusively by users with the `root` role
- The `root` role has all permissions implicitly (Spatie super-admin gate)
- The `is_manager` boolean on the `users` table is deprecated and will be removed in a follow-up migration
- Permission checks in use cases and controllers will use `$user->can('permission.name')` via Spatie
- Resource-level scoping (which VPS instances a user can see) remains enforced via `vps_access_grants` (ADR-002)
- A seeder will create the initial permission set and the `root` role

### Defined permissions

The following permission strings are introduced (aligned to the Foundation document):

**Billing**
- `Billing.getCatalog`

**Orders**
- `Orders.PaymentMethods.create`, `Orders.PaymentMethods.read`, `Orders.PaymentMethods.delete`
- `Orders.Subscriptions.read`, `Orders.Subscriptions.update`, `Orders.Subscriptions.delete`

**Domains**
- `Domains.Availability.validate`
- `Domains.Forwarding.read`, `Domains.Forwarding.create`, `Domains.Forwarding.delete`
- `Domains.Portfolio.DomainLock.update`, `Domains.Portfolio.DomainLock.delete`
- `Domains.Portfolio.Details`
- `Domains.Portfolio.Manage.create`, `Domains.Portfolio.Manage.read`
- `Domains.Portfolio.Privacy.update`, `Domains.Portfolio.Privacy.delete`
- `Domains.Portfolio.Nameservers.update`
- `Domains.Whois.read`, `Domains.Whois.list`, `Domains.Whois.create`, `Domains.Whois.delete`, `Domains.Whois.usage`
- `Domains.AccessVerifier.read`

**DNS**
- `DNS.Snapshot.read`, `DNS.Snapshot.list`, `DNS.Snapshot.restore`
- `DNS.Zone.read`, `DNS.Zone.update`, `DNS.Zone.delete`, `DNS.Zone.reset`, `DNS.Zone.validate`

**Hosting**
- `Hosting.Datacenters.list`
- `Hosting.Domains.Subdomain.create`, `Hosting.Domains.Subdomain.verify`

**Reach**
- `Reach.Contacts.read`, `Reach.Contacts.create`, `Reach.Contacts.delete`
- `Reach.Segments.list`, `Reach.Segments.create`, `Reach.Segments.details`

**VPS**
- `VPS.Actions.read`, `VPS.Actions.details`
- `VPS.Backups.read`, `VPS.Backups.restore`
- `VPS.DataCenters.list`
- `VPS.Firewall.read`, `VPS.Firewall.create`, `VPS.Firewall.update`, `VPS.Firewall.delete`
- `VPS.OSTemplates.read`, `VPS.OSTemplates.details`
- `VPS.PostInstallScripts.read`, `VPS.PostInstallScripts.create`, `VPS.PostInstallScripts.update`, `VPS.PostInstallScripts.delete`
- `VPS.PublicKeys.read`, `VPS.PublicKeys.create`, `VPS.PublicKeys.attach`, `VPS.PublicKeys.delete`
- `VPS.Recovery.start`, `VPS.Recovery.stop`
- `VPS.Snapshots.read`, `VPS.Snapshots.create`, `VPS.Snapshots.delete`, `VPS.Snapshots.restore`
- `VPS.VirtualMachine.PublicKeys.read`
- `VPS.VirtualMachine.Hostname.update`, `VPS.VirtualMachine.Hostname.delete`
- `VPS.VirtualMachine.Manage.read`, `VPS.VirtualMachine.Manage.details`, `VPS.VirtualMachine.Manage.metrics`, `VPS.VirtualMachine.Manage.nameservers`, `VPS.VirtualMachine.Manage.recreate`, `VPS.VirtualMachine.Manage.restart`, `VPS.VirtualMachine.Manage.password`, `VPS.VirtualMachine.Manage.start`, `VPS.VirtualMachine.Manage.stop`
- `VPS.VirtualMachine.Purchase.create`, `VPS.VirtualMachine.Purchase.setup`

**Management**
- `Manage.Invite.user`, `Manage.Invite.list`, `Manage.Invite.update`, `Manage.Invite.delete`
- `Manage.Permissions.create`, `Manage.Permissions.update`, `Manage.Permissions.delete`, `Manage.Permissions.list`, `Manage.Permissions.read`
- `Manage.Permissions.VPS.attach`, `Manage.Permissions.VPS.detach`, `Manage.Permissions.VPS.all`

## Consequences

### Positive consequences

- Granular, Hostinger-aligned permission model
- Role assignment and revocation handled entirely by Spatie
- Gate integration means `$user->can()` works across middleware and policies
- Auditable: Spatie records permission changes in the database

### Negative consequences

- Replaces the existing `is_manager` boolean — requires migration and refactor of AuthModule checks
- Adds complexity to the permission seeding and management workflow

### Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Broken existing manager checks | Refactor `is_manager` checks to use `$user->can('Manage.Invite.user')` after seeding |
| Missing permission strings | Permission seeder is the source of truth; use validation tests |
| Role escalation | Only root role can create or assign other roles |

## Alternatives Considered

### Keep `is_manager` boolean

Rejected because it cannot represent the full permission surface defined in the Foundation.

### Custom permission table

Rejected because Spatie provides a battle-tested, well-maintained implementation with Gate integration.

## Implementation Notes

- Run `php artisan vendor:publish --tag="permission-migrations"` to publish Spatie migrations
- Create a `PermissionSeeder` that creates all named permissions and the `root` role
- Update `AuthModuleServiceProvider` or add a dedicated `PermissionModuleServiceProvider`
- Replace `$inviter->isManager()` in `InviteUser` use case with `$inviter->can('Manage.Invite.user')`
- Super-admin gate must be registered to give `root` role all permissions implicitly

## Audit and Compliance

Permission grants and revocations must be audited:
- Actor (who made the change)
- Target (which user was affected)
- Permission or role assigned/revoked
- Timestamp

## Open Questions (Deferred)

- What is the default role assigned at invitation acceptance?
- Can a manager role be created by root with a subset of `Manage.*` permissions?
- Should permission changes appear in `infra_audit_logs` or a separate table?

## Related Artifacts

- ADR-001: Invitation-Based User Registration
- Foundation Document
- Phase 1 roadmap: role and permission management
