# ADR-016: Frontend Implementation Strategy

## Status

Proposed

## Context

The backend microservice exposes a complete REST API covering:
- Authentication (invitation, registration, login, token management)
- VPS lifecycle (list, details, start/stop/reboot, metrics, actions, backups)
- VPS security (firewall rules, SSH keys, snapshots, post-install scripts)
- Domains (portfolio, availability, forwarding, whois)
- DNS (zone records, snapshots)
- Billing (catalog, payment methods, subscriptions)
- Hosting & Reach (datacenters, contacts, segments)
- Governance (access reviews, audit export, permission approvals)
- Observability (health, queue health, quota stats, cache stats, DB stats)

No frontend layer exists. Operators currently interact with the API directly. A dedicated UI is required to expose all features to authenticated users according to their roles and permissions.

## Decision

The frontend will be implemented as a **server-driven SPA using Inertia.js + Vue 3**, hosted inside the same Laravel repository under `resources/js/`. Inertia eliminates the API client layer: Laravel controllers render Vue pages directly by passing typed props, with no fetch/axios calls for page navigation.

Authentication uses **Sanctum web sessions** (cookie-based) instead of Bearer tokens — removing the need to manage token storage on the client.

---

## Architecture

### Stack

| Layer | Choice | Reason |
|---|---|---|
| Page rendering | Inertia.js v2 | Zero API client layer, Laravel handles routing |
| UI framework | Vue 3 + Composition API | Reactive, Vite-native, small bundle |
| Styling | Tailwind CSS v4 | Utility-first, zero runtime CSS overhead |
| Component library | shadcn-vue (headless) | Accessible, composable, no style lock-in |
| State (local) | Vue `ref` / `reactive` | Sufficient for page-scoped state |
| State (global) | Pinia | Auth user, notifications, role/permission cache |
| Forms | Inertia `useForm()` | Handles validation errors, loading, CSRF automatically |
| Tables / pagination | Inertia links + server-side | No client-side pagination logic needed |
| Icons | Lucide Vue Next | Tree-shakeable, consistent with shadcn |
| Date formatting | Day.js | Lightweight, locale-aware |
| Build | Vite (already in project) | Already configured in `package.json` |

### Authentication flow

```
Browser → GET /login (Inertia page)
        → POST /auth/login (Inertia form)
        → Redirect to /dashboard (Sanctum session cookie set)
        → All subsequent Inertia requests carry session cookie automatically
        → POST /auth/logout → redirect to /login
```

Invitation-based registration:
```
Email → GET /register/{token} → Inertia RegisterPage (pre-filled email)
      → POST /auth/register → redirect /dashboard
```

### Directory structure

```
resources/js/
├── app.ts                         # Inertia bootstrap
├── ssr.ts                         # SSR entry (optional, Phase 2)
├── types/
│   ├── index.d.ts                 # Global type declarations
│   ├── models.ts                  # User, VpsAccessGrant, AccessReview, etc.
│   └── pages.ts                   # Per-page prop types
├── composables/
│   ├── useAuth.ts                 # Pinia auth store wrapper
│   ├── usePermission.ts           # can(permission), hasRole(role)
│   ├── useFlash.ts                # Flash message handling
│   └── useConfirm.ts              # Confirmation dialog composable
├── components/
│   ├── layout/
│   │   ├── AppLayout.vue          # Sidebar + topbar shell
│   │   ├── AppSidebar.vue         # Nav items grouped by module
│   │   ├── AppTopbar.vue          # User menu, notifications
│   │   └── PageHeader.vue         # Title + breadcrumbs + actions slot
│   ├── ui/                        # shadcn-vue primitives
│   │   ├── Button.vue
│   │   ├── Badge.vue
│   │   ├── Card.vue
│   │   ├── DataTable.vue          # Generic sortable/paginated table
│   │   ├── Dialog.vue
│   │   ├── Dropdown.vue
│   │   ├── Form.vue               # Wraps Inertia useForm with error display
│   │   ├── Input.vue
│   │   ├── Select.vue
│   │   ├── StatusBadge.vue        # ok/warning/exceeded/degraded colors
│   │   └── Toast.vue              # Flash notifications
│   └── vps/
│       ├── VpsStatusBadge.vue
│       └── VpsActionButton.vue
└── pages/
    ├── Auth/
    │   ├── Login.vue
    │   ├── Register.vue            # Invitation token pre-filled
    │   └── Tokens.vue              # Personal access tokens (list + revoke + create)
    ├── Dashboard.vue               # Summary widgets: quota, VPS count, open reviews
    ├── Vps/
    │   ├── Index.vue               # VPS list (scoped or full depending on role)
    │   ├── Show.vue                # VPS detail tabs: Overview, Metrics, Actions, Backups
    │   ├── Firewall.vue            # Firewall rules CRUD
    │   ├── SshKeys.vue             # SSH key CRUD
    │   └── Snapshots.vue           # Snapshot list + create + delete
    ├── Domains/
    │   ├── Portfolio.vue           # Domain list
    │   ├── Availability.vue        # Domain availability checker
    │   ├── Forwarding.vue          # Domain forwarding rules
    │   └── Whois.vue               # Whois lookup
    ├── Dns/
    │   ├── Zone.vue                # DNS zone records editor
    │   └── Snapshots.vue           # DNS zone snapshots
    ├── Billing/
    │   ├── Catalog.vue             # Product catalog
    │   ├── PaymentMethods.vue      # Payment methods list
    │   └── Subscriptions.vue       # Subscription list
    ├── Hosting/
    │   ├── Datacenters.vue
    │   └── Reach.vue               # Contacts + segments
    ├── Governance/
    │   ├── AuditExport.vue         # Filter form + download JSON/CSV
    │   ├── AccessReviews/
    │   │   ├── Index.vue           # Review list with status badges
    │   │   ├── Create.vue          # Period picker
    │   │   └── Show.vue            # Item list + approve/revoke buttons
    │   └── Approvals/
    │       └── Index.vue           # Pending approval requests + approve button
    └── Ops/
        ├── Health.vue              # /health/ready + /health/queue status
        ├── Quota.vue               # Quota gauge + by_resource breakdown chart
        ├── Cache.vue               # Hit/miss rates per cache key
        └── Database.vue            # Table row counts + retention info
```

---

## Inertia controller integration

Each Vue page receives **typed props** from its paired controller. No client-side API calls for initial data.

### Example: VPS list page

```php
// New: app/Modules/VpsModule/Http/Controllers/VpsPageController.php
public function index(Request $request): Response
{
    $result = $this->getVpsList->execute($request->user());
    return Inertia::render('Vps/Index', [
        'vps'         => $result->data,
        'can_see_all' => $request->user()->can('Manage.Permissions.VPS.all'),
    ]);
}
```

```vue
<!-- resources/js/pages/Vps/Index.vue -->
<script setup lang="ts">
defineProps<{ vps: Vps[]; can_see_all: boolean }>()
</script>
```

### Example: Inertia form (VPS action)

```vue
<script setup lang="ts">
const form = useForm({ action: 'reboot' })
const submit = () => form.post(`/vps/${props.vps.id}/reboot`)
</script>
```

---

## Routing

New route file: `app/Modules/FrontendModule/Http/Routes/web.php`

```php
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/',                   [DashboardController::class,   'index']);
    Route::get('/vps',                [VpsPageController::class,     'index']);
    Route::get('/vps/{id}',           [VpsPageController::class,     'show']);
    Route::get('/vps/{id}/firewall',  [VpsPageController::class,     'firewall']);
    Route::get('/vps/{id}/ssh-keys',  [VpsPageController::class,     'sshKeys']);
    Route::get('/vps/{id}/snapshots', [VpsPageController::class,     'snapshots']);
    Route::get('/domains',            [DomainPageController::class,  'portfolio']);
    Route::get('/domains/check',      [DomainPageController::class,  'availability']);
    Route::get('/dns/{domain}',       [DnsPageController::class,     'zone']);
    Route::get('/billing',            [BillingPageController::class, 'catalog']);
    Route::get('/governance/reviews', [GovernancePageController::class, 'reviews']);
    Route::get('/governance/reviews/{id}', [GovernancePageController::class, 'reviewShow']);
    Route::get('/governance/audit',   [GovernancePageController::class, 'audit']);
    Route::get('/governance/approvals', [GovernancePageController::class, 'approvals']);
    Route::get('/ops/health',         [OpsPageController::class,     'health'])->middleware('role:root');
    Route::get('/ops/quota',          [OpsPageController::class,     'quota'])->middleware('role:root');
    Route::get('/ops/cache',          [OpsPageController::class,     'cache'])->middleware('role:root');
    Route::get('/ops/database',       [OpsPageController::class,     'database'])->middleware('role:root');
});

Route::middleware('web')->group(function () {
    Route::get('/login',              [AuthPageController::class,    'loginForm']);
    Route::get('/register/{token}',   [AuthPageController::class,    'registerForm']);
});
```

---

## Permission-aware UI

The Inertia shared data (via `HandleInertiaRequests` middleware) exposes the authenticated user's roles and permissions to every page:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        'auth' => [
            'user'        => $request->user(),
            'roles'       => $request->user()?->getRoleNames(),
            'permissions' => $request->user()?->getAllPermissions()->pluck('name'),
        ],
        'flash' => [
            'success' => session('success'),
            'error'   => session('error'),
        ],
    ];
}
```

```ts
// composables/usePermission.ts
export function usePermission() {
    const page = usePage<SharedProps>()
    const can  = (p: string) => page.props.auth.permissions.includes(p)
    const is   = (r: string) => page.props.auth.roles.includes(r)
    return { can, is }
}
```

---

## Dashboard widgets

The `/` page renders summary data from multiple backend endpoints in a single Inertia render call:

| Widget | Backend data source |
|---|---|
| API quota gauge | `HostingerQuotaTracker::getToday()` + `warn_at` |
| Quota by resource | `getTodayByResource()` (bar chart) |
| VPS count | `count($result->data)` from `GetVpsList` |
| Open access reviews | `AccessReview::where('status', 'pending')->count()` |
| Pending approvals | `PermissionApproval::where('status', 'pending')->count()` |
| Queue health | `jobs` + `failed_jobs` counts |
| Open drift reports | `DriftReport::where('status', 'open')->count()` |

---

## New files to create

### Phase 1 — Bootstrap + Auth + Layout (unblocks everything else)

| File | Purpose |
|---|---|
| `package.json` updates | Add `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `tailwindcss`, `pinia`, `lucide-vue-next`, `dayjs` |
| `resources/js/app.ts` | Inertia + Vue 3 + Pinia bootstrap |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share auth, roles, permissions, flash |
| `app/Modules/FrontendModule/` | ServiceProvider, routes (web.php), all page controllers |
| `resources/js/pages/Auth/Login.vue` | Login form |
| `resources/js/pages/Auth/Register.vue` | Registration from invitation token |
| `resources/js/components/layout/AppLayout.vue` | Shell with sidebar |

### Phase 2 — VPS + Security pages

VPS Index, Show (with tabs), Firewall, SSH Keys, Snapshots pages + their controllers.

### Phase 3 — Domains, DNS, Billing, Hosting pages

### Phase 4 — Governance pages

Access reviews list + show (with decide buttons), audit export form, approvals.

### Phase 5 — Ops pages (root-only)

Health dashboard, quota chart, cache stats table, DB stats table.

---

## Consequences

**Positive**
- Zero API client code — Inertia props replace fetch/axios entirely
- Sanctum session auth — no token storage on client, CSRF handled by Laravel
- Shared validation — Laravel validation errors surface automatically in `useForm()`
- Single repository — frontend and backend evolve together, same deploy pipeline
- Permission checks in controllers + in UI composable — no security gap

**Negative**
- Couples frontend release to backend release (monorepo trade-off)
- SSR requires additional setup if needed (opt-in via `ssr.ts`)
- Not suitable as a public-facing API for third-party consumers (API routes remain for that)

---

## Implementation order

1. `HandleInertiaRequests` middleware + `FrontendModule` ServiceProvider + Inertia bootstrap
2. Auth pages (Login, Register) — unblocks manual testing of all other pages
3. AppLayout + Dashboard
4. VPS pages (highest usage)
5. Governance pages (compliance requirement)
6. Ops pages (root-only, last since most restricted)
7. Domains / DNS / Billing / Hosting (read-heavy, straightforward)
