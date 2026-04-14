# Hostinger Infrastructure Management Microservice — English Documentation

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Requirements](#requirements)
4. [Installation & Setup](#installation--setup)
5. [Environment Variables](#environment-variables)
6. [Authentication](#authentication)
7. [Roles & Permissions](#roles--permissions)
8. [API Reference](#api-reference)
9. [Web Interface](#web-interface)
10. [Scheduled Jobs](#scheduled-jobs)
11. [Governance & Compliance](#governance--compliance)
12. [Observability](#observability)
13. [Testing](#testing)
14. [Architecture Decision Records](#architecture-decision-records)

---

## Overview

This microservice acts as a **permission-aware management layer** on top of the Hostinger API. Instead of giving every team member direct API credentials, this service:

- Enforces role-based access control (RBAC) over every Hostinger resource
- Caches read-only Hostinger data to avoid burning API quota
- Records every mutation in an immutable audit log
- Automates governance tasks: access reviews, drift detection, stale grant expiry
- Exposes a full React/Inertia.js web interface for human operators
- Exposes a versioned JSON REST API for programmatic consumers

**Primary use case:** A company runs multiple VPS instances, domains, and DNS zones under one Hostinger account. Several teams need access — each with different levels of authority. This service mediates all access through a single controlled gateway.

---

## Architecture

### Modular Laravel 12

The application is divided into **11 modules**, each self-contained under `app/Modules/`:

| Module | Responsibility |
|--------|---------------|
| `AuthModule` | User registration (invitation-based), login, logout |
| `VpsModule` | VPS list, details, firewall, SSH keys, snapshots, lifecycle actions |
| `HostingerProxyModule` | Read-only cache layer wrapping Hostinger API responses |
| `GovernanceModule` | Access reviews, audit log export, permission approvals |
| `PermissionModule` | Role/permission assignment, Spatie integration |
| `PolicyModule` | Policy-driven enforcement (pre-action policy checks) |
| `ObservabilityModule` | Structured logging, slow-request detection, InstrumentedCache |
| `SecurityResourceModule` | Firewall rules, SSH keys, snapshots per VPS |
| `OpsModule` | Internal health checks, quota tracking, cache stats, DB row counts |
| `DriftModule` | Drift detection (Hostinger state vs. local records) |
| `FrontendModule` | Inertia.js page controllers + web routes |

### Request flow

```
Browser / API Client
       │
       ▼
  Laravel Router
       │
  ┌────┴────┐
  │  Web    │  (session auth via middleware 'auth')
  │  Routes │──► FrontendModule Controllers ──► Use Cases ──► HostingerProxy / DB
  └─────────┘
  ┌─────────┐
  │  API    │  (token auth via Sanctum 'auth:sanctum')
  │  Routes │──► Module Controllers ──► Use Cases ──► HostingerProxy / DB
  └─────────┘
```

### Use Case pattern

Every operation is encapsulated in a Use Case class that returns a typed **Result Object**:

```php
$result = $useCase->execute($input);

if ($result->success()) {
    return Inertia::render('Page', $result->data());
}
if ($result->forbidden()) {
    abort(403);
}
```

Result states: `success`, `forbidden`, `policyDenied`, `notFound`, `conflict`, `quotaExceeded`, `rateLimited`

---

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 20+ / npm 10+
- SQLite (default, zero-config) **or** MySQL/PostgreSQL
- A valid [Hostinger API token](https://developers.hostinger.com)

---

## Installation & Setup

### 1. Clone and install dependencies

```bash
git clone https://github.com/andmarruda/microservice-hostinger-api.git
cd microservice-hostinger-api

composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` — at minimum set:

```env
HOSTINGER_API_TOKEN=your_token_here
```

### 3. Run migrations and seed

```bash
php artisan migrate
php artisan db:seed          # Creates a root user and sample roles
```

### 4. Build frontend assets

```bash
npm run build
```

### 5. Start the development server

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite dev server (hot reload)
npm run dev
```

Open `http://localhost:8000/login` in your browser.

### 6. Run the queue worker (for scheduled jobs and async operations)

```bash
php artisan queue:work
```

### 7. Start the scheduler (optional, for automated tasks)

```bash
php artisan schedule:work
```

---

## Environment Variables

### Application

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Environment name (`local`, `production`) |
| `APP_DEBUG` | `true` | Enable debug mode (disable in production) |
| `APP_URL` | `http://localhost` | Public URL of the application |
| `APP_KEY` | — | Generated by `php artisan key:generate` |

### Database

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | `sqlite` | Database driver (`sqlite`, `mysql`, `pgsql`) |
| `DB_HOST` | `127.0.0.1` | Database host (MySQL/PostgreSQL) |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | — | Database name or SQLite file path |
| `DB_USERNAME` | — | Database username |
| `DB_PASSWORD` | — | Database password |

### Queue & Cache

| Variable | Default | Description |
|----------|---------|-------------|
| `QUEUE_CONNECTION` | `database` | Queue driver (`database`, `redis`, `sync`) |
| `CACHE_STORE` | `database` | Cache driver (`database`, `redis`, `memcached`) |
| `SESSION_DRIVER` | `database` | Session driver |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |

### Hostinger API

| Variable | Default | Description |
|----------|---------|-------------|
| `HOSTINGER_API_BASE_URL` | `https://developers.hostinger.com` | Hostinger API endpoint |
| `HOSTINGER_API_TOKEN` | — | **Required.** Your Hostinger API token |
| `HOSTINGER_API_TIMEOUT_SECONDS` | `10` | HTTP request timeout |

### Hostinger Cache TTLs

| Variable | Default | Description |
|----------|---------|-------------|
| `HOSTINGER_CACHE_TTL_VPS_LIST` | `86400` | VPS list cache lifetime (seconds) |
| `HOSTINGER_CACHE_TTL_OS_TEMPLATES` | `86400` | OS templates cache lifetime |
| `HOSTINGER_CACHE_TTL_DATACENTERS` | `86400` | Datacenters cache lifetime |
| `HOSTINGER_CACHE_TTL_DOMAIN_AVAILABILITY` | `3600` | Domain availability cache lifetime |

### API Quota Controls

| Variable | Default | Description |
|----------|---------|-------------|
| `HOSTINGER_API_QUOTA_WARN_AT` | `800` | Log a warning when daily API calls exceed this |
| `HOSTINGER_API_QUOTA_HARD_LIMIT` | — | Block requests with 503 when this limit is hit |

### Observability

| Variable | Default | Description |
|----------|---------|-------------|
| `LOG_CHANNEL` | `stack` | Log channel (`stack`, `json` for structured logging) |
| `SLOW_REQUEST_THRESHOLD_MS` | `2000` | Milliseconds threshold for slow-request warnings |

### Retention Policies

| Variable | Default | Description |
|----------|---------|-------------|
| `AUDIT_LOG_RETENTION_DAYS` | `365` | Days to keep infrastructure audit logs |
| `AUTH_LOG_RETENTION_DAYS` | `365` | Days to keep authentication audit logs |
| `DRIFT_REPORT_RETENTION_DAYS` | `90` | Days to keep archived drift reports |
| `ACCESS_REVIEW_RETENTION_DAYS` | `730` | Days to keep completed access reviews |
| `FAILED_JOB_RETENTION_DAYS` | `30` | Days to keep failed queue jobs |

### Authentication

| Variable | Default | Description |
|----------|---------|-------------|
| `SANCTUM_TOKEN_EXPIRY_MINUTES` | — | Token lifetime (leave empty for non-expiring) |

---

## Authentication

### Web interface (session)

The web interface uses Laravel session authentication:

1. Navigate to `GET /login`
2. Submit email + password via the form
3. Session cookie is issued on success
4. `POST /logout` ends the session

### API (Bearer token via Sanctum)

All API routes require a `Bearer` token in the `Authorization` header:

```http
Authorization: Bearer <your-sanctum-token>
```

Tokens are created through the auth API:

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret"
}
```

Response:
```json
{
  "token": "1|AbCdEfGhIjKlMnOp...",
  "user": { "id": 1, "name": "Alice", "email": "user@example.com" }
}
```

### Invitation-based registration

New users cannot self-register. An existing **root** or **manager** user must invite them:

```http
POST /api/v1/auth/invite
Authorization: Bearer <root-token>

{
  "email": "newuser@example.com",
  "role": "operator"
}
```

The invited user receives a link: `GET /register/{token}` (web) or:

```http
POST /api/v1/auth/register
{
  "token": "<invitation-token>",
  "name": "New User",
  "password": "secure-password",
  "password_confirmation": "secure-password"
}
```

---

## Roles & Permissions

### Built-in roles

| Role | Description |
|------|-------------|
| `root` | Full access to everything, including Ops pages |
| `manager` | Can manage users, review accesses, approve permissions |
| `operator` | Can execute VPS lifecycle actions on assigned VPS |
| `viewer` | Read-only access to assigned resources |

### Permissions

Permissions follow the `resource.action` pattern:

| Permission | Description |
|-----------|-------------|
| `vps.read` | List and view VPS details |
| `vps.write` | Start, stop, reboot VPS |
| `vps.firewall` | Manage firewall rules |
| `vps.ssh-keys` | Manage SSH keys |
| `vps.snapshots` | Manage snapshots |
| `domains.read` | View domain portfolio |
| `dns.read` | View DNS zones |
| `dns.write` | Modify DNS records |
| `billing.read` | View billing information |
| `governance.reviews` | Manage access reviews |
| `governance.audit` | Export audit logs |
| `governance.approvals` | Approve permission requests |

### Resource-scoped grants

Access to individual VPS is controlled via `VpsAccessGrant` records. A user with `vps.read` can only see VPS instances they have been explicitly granted access to.

---

## API Reference

Base URL: `/api/v1`

All endpoints return JSON. Error responses follow the format:
```json
{
  "message": "Descriptive error message",
  "errors": { "field": ["Validation error"] }
}
```

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Issue a Sanctum token |
| POST | `/auth/logout` | Revoke the current token |
| POST | `/auth/invite` | Send an invitation (manager+) |
| POST | `/auth/register` | Accept an invitation and create account |

### VPS

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/vps` | List VPS instances accessible to the current user |
| GET | `/vps/{id}` | Get VPS details |
| POST | `/vps/{id}/start` | Start a VPS |
| POST | `/vps/{id}/stop` | Stop a VPS |
| POST | `/vps/{id}/reboot` | Reboot a VPS |
| GET | `/vps/{id}/firewall` | List firewall rules |
| GET | `/vps/{id}/ssh-keys` | List SSH keys |
| GET | `/vps/{id}/snapshots` | List snapshots |
| GET | `/vps/{id}/metrics` | Get current resource metrics |
| GET | `/vps/{id}/actions` | Get action history |
| GET | `/vps/{id}/backups` | List backups |

### Domains & DNS

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/domains` | List domain portfolio |
| GET | `/domains/check?domain=example.com` | Check domain availability |
| GET | `/domains/{domain}/forwarding` | Get domain forwarding rules |
| GET | `/domains/{domain}/whois` | Get WHOIS data |
| GET | `/dns/{domain}` | Get DNS zone records |
| GET | `/dns/{domain}/snapshots` | List DNS snapshots |

### Billing

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/billing/catalog` | List available VPS plans |
| GET | `/billing/subscriptions` | List active subscriptions |
| GET | `/billing/payment-methods` | List saved payment methods |

### Governance

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/governance/reviews` | List access reviews |
| POST | `/governance/reviews` | Create a new access review |
| GET | `/governance/reviews/{id}` | Get review details |
| POST | `/governance/reviews/{id}/items/{itemId}` | Decide on a review item (approve/revoke) |
| GET | `/governance/audit` | Query audit logs |
| GET | `/governance/audit/export?format=csv` | Download audit log CSV |
| GET | `/governance/approvals` | List permission approval requests |
| POST | `/governance/approvals/{id}/approve` | Approve a permission request |

### Ops (root only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ops/health` | Service health status |
| GET | `/ops/quota` | Hostinger API quota usage |
| GET | `/ops/cache` | Cache hit/miss statistics |
| GET | `/ops/database` | Database table row counts |

---

## Web Interface

The web interface is built with **React 19 + Inertia.js v2 + Tailwind CSS 4**.

### Pages

| URL | Page | Description |
|-----|------|-------------|
| `/login` | Auth / Login | Email + password form |
| `/register/{token}` | Auth / Register | Accept invitation form |
| `/` | Dashboard | Summary cards: VPS count, open reviews, drift reports |
| `/vps` | VPS Index | Table of all accessible VPS with Start/Stop/Reboot |
| `/vps/{id}` | VPS Show | Tabs: Details, Metrics, Actions, Backups |
| `/vps/{id}/firewall` | VPS Firewall | Firewall rules table |
| `/vps/{id}/ssh-keys` | VPS SSH Keys | SSH key list |
| `/vps/{id}/snapshots` | VPS Snapshots | Snapshot list |
| `/domains` | Domain Portfolio | Domain list with status |
| `/domains/check` | Domain Availability | Domain availability checker |
| `/dns/{domain}` | DNS Zone | DNS records for a domain |
| `/billing` | Billing | Tabs: Subscriptions, Catalog, Payment Methods |
| `/governance/reviews` | Access Reviews | Review list with create button |
| `/governance/reviews/{id}` | Access Review Detail | Items with Approve / Revoke buttons |
| `/governance/audit` | Audit Log | Filterable audit log with CSV export |
| `/governance/approvals` | Approvals | Pending permission approval requests |
| `/ops/health` | Ops Health | Service health (root only) |
| `/ops/quota` | Ops Quota | API quota gauges (root only) |
| `/ops/cache` | Ops Cache | Cache statistics (root only) |
| `/ops/database` | Ops Database | DB row counts and retention (root only) |

### Navigation

The **AppLayout** sidebar groups navigation as follows:

- **VPS** → VPS list
- **Domains** → Portfolio / Availability
- **Billing** → Billing index
- **Governance** → Access Reviews / Audit Log / Approvals
- **Ops** → Health / Quota / Cache / Database *(visible to root only)*

The topbar displays the logged-in user's name and a **Logout** button.

Flash messages (success / error) appear automatically as a dismissible toast.

---

## Scheduled Jobs

Seven jobs run on a schedule via `php artisan schedule:work` (or cron):

| Job | Schedule | Description |
|-----|----------|-------------|
| `ExpireInvitations` | Hourly | Marks pending invitations as expired after deadline |
| `ExpireAccessGrants` | Hourly | Removes VPS access grants past their expiry date |
| `WarmHostingerCache` | Daily 03:00 | Pre-populates the Hostinger read cache |
| `PruneAuditLogs` | Daily 02:00 | Deletes audit logs older than retention window |
| `FlagStaleAccessGrants` | Daily 04:00 | Flags grants whose VPS no longer exists in Hostinger |
| `RunDriftScan` | Daily 04:30 | Detects drift between Hostinger state and local records |
| `ArchiveOldDriftReports` | Daily 03:30 | Archives resolved/dismissed drift reports |

All jobs use `withoutOverlapping(10)` to prevent concurrent runs.

---

## Governance & Compliance

### Access Reviews

An **Access Review** is a periodic audit of who has access to which VPS. Reviewers examine each grant and decide to `approve` (keep) or `revoke` (remove) it.

Lifecycle: `pending` → `completed` or `cancelled`

### Drift Detection

The `RunDriftScan` job compares the list of VPS instances returned by the Hostinger API against local `VpsAccessGrant` records. Any discrepancy (VPS deleted in Hostinger but still referenced locally) creates a **DriftReport** with status `open`. Operators can resolve or dismiss reports through the dashboard.

### Audit Log

Every state-changing operation creates an `InfraAuditLog` entry recording:
- `actor_id` / `actor_email` — who performed the action
- `action` — e.g. `vps.start`, `dns.write`
- `resource_type` / `resource_id` — what was affected
- `outcome` — `success` or `failure`
- `performed_at` — timestamp

Audit logs are retained for `AUDIT_LOG_RETENTION_DAYS` days (default: 365).

### Permission Approvals

Users can request elevated permissions (e.g., `vps.write`). The request appears in the Governance → Approvals queue. A manager or root user reviews and approves it. The requester cannot approve their own request.

---

## Observability

### Structured logging

Set `LOG_CHANNEL=json` in production to emit structured JSON log lines compatible with Datadog, CloudWatch, and similar aggregators.

### Slow request detection

Any HTTP request exceeding `SLOW_REQUEST_THRESHOLD_MS` (default: 2000ms) is logged at `warning` level with context (route, duration, user).

### InstrumentedCache

All Hostinger API responses are cached via `InstrumentedCache::remember()`, which tracks hit and miss counts per cache key. The `/ops/cache` page surfaces these statistics with hit rate percentages.

### Quota tracking

`HostingerQuotaTracker` counts outbound Hostinger API calls. When calls exceed `HOSTINGER_API_QUOTA_WARN_AT`, a warning is logged. When `HOSTINGER_API_QUOTA_HARD_LIMIT` is set and reached, the API returns HTTP 503 to protect the Hostinger account.

---

## Testing

### PHP tests (PHPUnit)

```bash
php artisan test          # Run all 194 tests
php artisan test --filter VpsModuleTest
```

### JavaScript tests (Vitest)

```bash
npm run test              # Run all tests once
npm run test:watch        # Watch mode
npm run test:coverage     # Run with coverage report
```

Coverage thresholds enforced:

| Metric | Threshold |
|--------|-----------|
| Statements | 90% |
| Lines | 90% |
| Functions | 90% |
| Branches | 85% |

Current coverage: **~95% statements / ~91% branches** across 306 tests in 31 test files.

### Test structure

```
resources/js/
├── test/
│   ├── setup.ts                      # Global setup (jest-dom, mock resets)
│   └── mocks/
│       └── inertia.tsx               # Global Inertia.js mock
├── components/ui/__tests__/          # UI component unit tests
├── hooks/__tests__/                  # Hook unit tests
├── layouts/__tests__/                # Layout tests
└── pages/
    ├── Auth/__tests__/
    ├── Billing/__tests__/
    ├── Dns/__tests__/
    ├── Domains/__tests__/
    ├── Governance/
    │   ├── AccessReviews/__tests__/
    │   ├── Approvals/__tests__/
    │   └── __tests__/
    ├── Ops/__tests__/
    └── Vps/__tests__/
```

---

## Architecture Decision Records

All major architectural decisions are documented in `docs/adrs/`:

| ADR | Title |
|-----|-------|
| ADR-001 | Invitation-Based User Registration |
| ADR-002 | VPS Lifecycle Write Operations via Controlled Proxy |
| ADR-003 | Management of Firewall Rules, SSH Keys, and Snapshots |
| ADR-004 | Expanded Audit Coverage for Infrastructure Mutations |
| ADR-005 | Role-Based Permission System with Spatie Laravel Permissions |
| ADR-006 | Read-Only Hostinger Resource Proxy |
| ADR-007 | JWT and Session Authentication |
| ADR-008 | API Versioning and Response Normalization |
| ADR-009 | Rate Limiting Strategy |
| ADR-010 | Policy-Driven Enforcement |
| ADR-011 | Scheduled and Automated Tasks |
| ADR-012 | Drift Detection |
| ADR-013 | Observability and Structured Logging |
| ADR-014 | Compliance and Governance Tooling |
| ADR-015 | Performance Optimization and Cost Controls |
| ADR-016 | Frontend Implementation Strategy |
