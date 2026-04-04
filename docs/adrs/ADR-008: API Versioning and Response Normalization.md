# ADR-008: API Versioning and Response Normalization

## Status

Proposed

## Context

The Foundation document specifies:

- REST-style JSON API with URL-based versioning (e.g., `/api/v1`)
- Responses normalized into a service-owned format that insulates consumers from upstream Hostinger API changes
- Consistent error envelope across all endpoints
- Stable, explicit contracts across versions

Currently, routes are registered without an `/api/v1` prefix and without consistent response normalization. Error responses and success responses vary between controllers.

As the platform grows to cover more Hostinger resource areas, inconsistent contracts will create integration friction for consumers.

## Decision

All routes will be versioned under `/api/v1` and all responses will follow a normalized envelope.

### Key decisions

- All routes are prefixed with `/api/v1`
- Success responses always use `{ "data": ... }` at the top level
- Paginated responses use `{ "data": [...], "meta": { "current_page": ..., "total": ... } }`
- Error responses always use `{ "message": "...", "errors": { ... } }` (consistent with Laravel's default validation format)
- Infrastructure errors (502 from Hostinger) include a `correlation_id` field for traceability
- Service providers load routes with the `/api/v1` prefix applied at the application level, not per-module
- A dedicated `ResponseNormalizer` service or base controller method handles envelope wrapping

### Response envelope

**Success (single resource):**
```json
{
  "data": {
    "vps_id": "...",
    "correlation_id": "..."
  }
}
```

**Success (collection):**
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 100
  }
}
```

**Validation error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Error message."]
  }
}
```

**Permission error (403):**
```json
{
  "message": "Forbidden."
}
```

**Upstream error (502):**
```json
{
  "message": "Failed to communicate with Hostinger.",
  "correlation_id": "uuid"
}
```

## Consequences

### Positive consequences

- Clients can rely on a stable, predictable response shape
- Version prefix allows non-breaking parallel versions during transitions
- Consistent error format simplifies client-side error handling
- `correlation_id` in upstream errors enables cross-system tracing

### Negative consequences

- Requires refactoring all existing routes to add the `/api/v1` prefix
- Requires updating all feature tests to use the new route paths
- May require a versioned client compatibility layer if the platform is already in use

### Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Breaking existing consumers | Coordinate with consumers before adding prefix |
| Inconsistent adoption | Enforce via base controller or shared response helpers |
| Version proliferation | Only create new versions when breaking changes are unavoidable |

## Alternatives Considered

### Header-based versioning

Rejected because URL-based versioning is simpler to debug, log, and communicate in documentation.

### No versioning

Rejected because the Foundation document explicitly requires URL-based versioning.

## Implementation Notes

- Update `AuthModuleServiceProvider`, `VpsModuleServiceProvider`, and `SecurityResourceModuleServiceProvider` to load routes with the `api/v1` prefix
- Update all feature tests to use `/api/v1/...` paths
- Create a `app/Http/Controllers/ApiController.php` base class with a `success()` and `error()` helper method
- Alternatively, use a `JsonResource` base for collection and single-resource normalization
- The `/api/v1` prefix is the only change to route paths at this stage; route names remain unchanged

## Related Artifacts

- ADR-002: VPS Lifecycle Write Operations
- ADR-003: Security Resource Management
- ADR-006: Read-Only Hostinger Resource Proxy
- Foundation Document: API Contract Philosophy
