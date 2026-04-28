# Multi-Tenant SaaS — Teachers Performance Evaluation

**Date:** 2026-04-18
**Status:** Approved (brainstorm phase). Implementation plan to follow.
**Branch context:** Builds on `feature/announcements-module`.

## Context & Goals

The current app is a single-tenant Laravel 13 + FastAPI ML system for one school's
faculty performance evaluations. We are extending it so the same codebase can host
multiple independent schools as a SaaS.

**Capstone goal (this iteration):** demonstrate multi-tenancy end-to-end —
isolated per-school data, subdomain routing, super-admin provisioning of new
schools, ML API tenant-awareness — without billing, public signup, or email
delivery.

**Future-proofing intent:** the architecture must let billing, plans, public
signup, and per-tenant customization plug in later without a rewrite. The
`tenants.data` JSON column and a clean central/tenant boundary are the
extension points.

## Out of Scope (deliberate cuts)

- Billing, plans, subscriptions, invoices.
- Public self-service signup, email verification, captcha.
- Email delivery — temp passwords are shown on-screen only.
- Custom domains per tenant (only subdomains of the platform domain).
- Cross-tenant analytics or platform-wide dashboards.
- Tenant deletion UI (left as a manual SQL operation).
- Per-tenant theme/logo customization UI (schema supports it; no UI built).
- Per-tenant data export/import.

## Architecture Overview

Two layers: **central** (the SaaS control plane) and **tenant** (one DB per
school). Subdomain identifies the tenant; `stancl/tenancy` v3 handles tenant
resolution and connection switching at request time.

```
admin.yourapp.com         → central DB        → super-admin dashboard
jcd.yourapp.com           → existing DB `teachers_performance` (registered as tenant id 1)
stmarys.yourapp.com       → tenant_2          → school instance
demo.yourapp.com          → tenant_3          → school instance
ml-api (single instance)  → reads X-Tenant-DB header per request
```

**Request lifecycle:**

1. Request hits `<subdomain>.yourapp.com`.
2. `InitializeTenancyBySubdomain` middleware extracts the subdomain, looks up
   the tenant in central DB, swaps `DB::connection('mysql')` to the tenant's
   DB, prefixes cache/queue/storage.
3. Controller code runs unchanged — `User::all()` scopes to the active school.
4. Outbound calls to the ML API include `X-Tenant-DB: tenant_<id>` so FastAPI
   queries the right DB and writes to the right model file.
5. After the response, tenancy is torn down. No cross-request leakage.

**Tech choices:**

- **`stancl/tenancy` v3** for tenant resolution + connection switching + queue
  / cache / storage bootstrap. The "custom-light hybrid" approach: package
  handles the gnarly parts; we write the visible super-admin UI ourselves.
- **Database-per-tenant** — strongest isolation. Each school's data lives in
  its own MySQL database.
- **Subdomain identification** — vanity URLs, future-friendly, low-friction
  with `*.localhost` for dev.
- **Single ML API instance** — tenant identified per-request via header. Model
  cache and on-disk files namespaced by `(tenant_id, semester, school_year)`.

## Central DB Schema

Three tables. No school-specific data ever lives in central.

### `tenants`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint pk | Used as the tenant DB suffix: `tenant_<id>`. |
| `name` | string | Display name, e.g. *"St. Mary's Academy"*. |
| `subdomain` | string, unique | URL slug. Validated `[a-z0-9-]+`. Reserved words blocked: `admin`, `www`, `api`, `app`. |
| `database` | string | Actual MySQL DB name. Decouples DB naming from tenant id (so existing `teachers_performance` DB can be tenant id 1 without rename). |
| `status` | enum | `provisioning`, `active`, `suspended`, `failed`. Login blocked unless `active`. |
| `data` | json, nullable | stancl convention — open bag for per-tenant settings (future: theme, logo, plan pointer). |
| `created_at`, `updated_at` | timestamps | |

### `super_admins`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint pk | |
| `name`, `email` (unique), `password`, `remember_token` | standard auth | |
| `is_active` | boolean | |
| `created_at`, `updated_at` | timestamps | |

Separate from tenant `users` on purpose. Different login route
(`admin.yourapp.com/login`), different guard (`super_admin`), different
middleware. Super-admins never appear in any school's user list.

### `tenant_provisioning_jobs`

| Column | Type | Notes |
|---|---|---|
| `id`, `tenant_id`, `status`, `error`, `started_at`, `finished_at` | | Audit trail of provisioning attempts. Powers the "Retry provisioning" affordance on the tenant detail page. |

## Tenant DB Schema & Data Migration

Tenant DB schema is the **current schema, unchanged**. No `school_id` columns
added anywhere — isolation comes from the connection switch, not row filtering.
Existing 50+ migration files move into `database/migrations/tenant/` and run
against each tenant DB at provisioning time. New central migrations live in
`database/migrations/central/`.

### Migration of existing data ("School #1" path)

1. Create central DB and run central migrations.
2. Insert `tenants` row: `{id: 1, name: "JCD", subdomain: "jcd", database: "teachers_performance", status: "active"}`. Points at the existing DB by name — zero data movement.
3. Existing `teachers_performance` DB stays exactly as it is. Seeded data, users, departments, announcements all keep working.
4. `jcd.localhost:8081` immediately works.

**No rename of `teachers_performance` to `tenant_1`.** The `tenants.database`
column abstracts away naming. Renaming would touch `docker-compose.yml`,
`.env`, the FastAPI `_db_config()` default, and Railway config for zero
functional benefit. Future tenants get clean `tenant_2`, `tenant_3` names.

### Blank-school template ("School #2 onward" path)

A new `database/seeders/tenant/TenantTemplateSeeder.php` runs on every newly
provisioned tenant. It runs the existing seeders that produce *system
primitives* (criteria, questions, permissions, lexicons, intervention
catalog), and explicitly skips per-school data.

**Seeded** (the app does not function without these):

- `RolePermissionSeeder` + `AnnouncementPermissionsSeeder`
- `CriteriaSeeder` + `QuestionSeeder`
- `DeanRecommendationCriterionSeeder` + `AcademicAdministratorsCriteriaSeeder`
- `InterventionSeeder`
- `SentimentLexiconSeeder`
- The wizard-collected first **school admin user** (replaces `DefaultUserSeeder`). Generated random temporary password, `must_change_password = true`.

**Not seeded** (per-school, the school admin populates after first login):

- `DepartmentSeeder`, `FacultySeeder`, `StudentSeeder`
- `CourseSeeder`, `SubjectSeeder`, `SubjectAssignmentSeeder`

**Not seeded** (school chooses when ready):

- Welcome announcement.
- Sample evaluation period.

## Provisioning Flow & Super-Admin Dashboard

Super-admin dashboard at `admin.yourapp.com`. Separate route group, separate
`super_admin` auth guard, separate Blade layout (or stripped-down current admin
layout — implementation choice).

### Pages (capstone scope)

- `/login` — super-admin login.
- `/tenants` — table of all schools (name, subdomain, status, created date, "Open dashboard" link, suspend/resume action).
- `/tenants/create` — provisioning wizard (single page form):
  - School name (e.g., "St. Mary's Academy")
  - Subdomain (live-validated — allowed chars, uniqueness, reserved-word blocklist)
  - First admin name + email
  - Submit.
- `/tenants/{id}` — read-only detail (status, provisioning log, "Open dashboard", suspend, retry-on-failure).

### Provisioning flow (queued `ProvisionTenantJob`)

Form submit returns immediately with a "provisioning…" page so the wizard isn't
blocked on a 30-second migrate.

1. Validate input. Insert `tenants` row with `status='provisioning'`.
2. `CREATE DATABASE tenant_<id> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`. (stancl ships a `CreateDatabase` action.)
3. Run all `database/migrations/tenant/` migrations against the new DB.
4. Run `TenantTemplateSeeder` (curated subset above).
5. Create the first school admin user with the wizard-collected name + email and a generated temp password.
6. Set `tenants.status='active'`. Redirect wizard to success page showing the subdomain URL, admin email, and temp password (shown once, no email sent).

### Failure handling

If any step throws: catch in the job, set `status='failed'`, write error to
`tenant_provisioning_jobs.error`, surface on the tenant detail page. "Retry
provisioning" re-runs from step 2 — idempotent (`CREATE DATABASE IF NOT EXISTS`,
migrations are no-ops if already applied).

### Suspend / delete

- **Suspend**: `status='suspended'`. Tenancy middleware refuses logins, shows a "school suspended" page. Data untouched.
- **Delete**: NOT implemented in UI for the capstone. Manual SQL only — fewer ways to accidentally nuke School #1 during a demo.

### Hard guarantee

Super-admin UI cannot read or write tenant data. To inspect a school they log
into that school as the school's own admin. Keeps the isolation guarantee
honest.

## Tenant Resolution & Connection Switching

### Route groups

- `routes/web.php` — central marketing / redirect routes (minimal).
- `routes/admin.php` — super-admin dashboard, domain-restricted to `admin.yourapp.com`.
- `routes/tenant.php` — every existing app route (login, dashboard, evaluations, announcements, …) wrapped in stancl middleware.

`routes/tenant.php` middleware stack:

```
PreventAccessFromCentralDomains
InitializeTenancyBySubdomain
```

### What stancl does for us per-request

- Default DB connection rebound to the tenant's DB. Existing Eloquent calls scope automatically.
- Cache prefix becomes `tenant_<id>_`.
- Queue payloads carry tenant id; jobs re-initialize tenancy on dequeue.
- Storage disk paths prefixed: `storage/app/tenant<id>/...`.
- Session storage namespaced per tenant.

### Implementation grep targets

Any code that does `DB::connection('mysql')` or
`config('database.connections.mysql.database')` explicitly will NOT swap. A
grep pass through `app/` and `routes/` is required during implementation.
Likely few hits.

Custom jobs in `app/Jobs/` need tenancy bootstrap — either extend the
tenant-aware base, or explicitly call `tenancy()->initialize($tenantId)` at
the top of `handle()`.

## ML API Tenant-Awareness

Single FastAPI container, unchanged deployment. Three changes to `ml_api/app.py`:

### 1. Tenant DB from request header

`_db_config()` reads the DB name from the `X-Tenant-DB` header instead of
`os.getenv("DB_NAME")`. New FastAPI dependency that all data-touching
endpoints (`/train-current-term`, `/predict`) pull in. Missing header → 400.

### 2. Cache key namespaced by tenant

```python
MODEL_CACHE: dict[tuple[str, str | None, str | None], RandomForestClassifier]
# key = (tenant_db, semester, school_year)
```

### 3. Model files namespaced by tenant on disk

```python
def _model_path_for(tenant_db, semester, school_year) -> Path:
    return MODEL_DIR / tenant_db / f"faculty_performance_rf__{tag}.joblib"
```

### Auth model

`X-ML-Token` shared secret stays — the only auth between Laravel and FastAPI.
The `X-Tenant-DB` header is a routing hint, not a security boundary. Both
services live in the same trusted network; if Laravel is compromised, tenant
isolation at the ML layer was never the line of defense.

### Laravel side

The existing `MlApiClient` service (or equivalent) gains one line — read the
current tenant via `tenant()->id`, attach the header. All call sites unchanged.

## Local Development

**`*.localhost` resolution.** Modern Chrome / Firefox / Safari resolve any
`*.localhost` hostname to `127.0.0.1` automatically. No `/etc/hosts` edits.

URLs become:

- `admin.localhost:8081` — super-admin dashboard
- `jcd.localhost:8081` — School #1 (existing data)
- `<sub>.localhost:8081` — any newly provisioned school

Nginx config gets one tweak: `server_name *.localhost localhost;`.

For Railway / production: wildcard subdomain (`*.yourapp.up.railway.app` or
custom domain). Standard DNS, nothing exotic.

## Testing Strategy

- **Central tests** — in-memory SQLite (or dedicated test central DB). Cover tenant CRUD, subdomain validation, super-admin auth, `ProvisionTenantJob` idempotency.
- **Tenant tests** — run inside a tenancy-initialized context using stancl's `RefreshDatabase` alternatives. Cover that critical flows (login, announcement CRUD, evaluation submission) still work after the connection swap.
- **One cross-tenant leakage test** — the highest-leverage test. Create two tenants, log into the first, assert the second's data is inaccessible by ID. Demonstrates the isolation guarantee.

Existing test coverage runs inside a tenant context unchanged.

## Risks & Verification Notes

1. **Hardcoded DB connection references.** Grep for `DB::connection('mysql')` and `config('database.connections.mysql.database')` in `app/`, `routes/`, and any custom commands before merging. They will not swap automatically.
2. **Queue workers need tenancy bootstrap.** Audit `app/Jobs/` during implementation.
3. **In-process `MODEL_CACHE` grows unbounded with tenant count.** Fine for the capstone (2–5 schools). LRU wrapper later if it climbs.
4. **First-tenant migration verification.** No data movement, but the FastAPI service must be reconfigured to require the header instead of falling back to its `os.getenv("DB_NAME")` default. The old `.env` `DB_NAME` value should be removed (or the default changed to `None`) so a missing header fails loudly instead of silently reading from JCD's DB. Implementation plan must call this out.
5. **Session cookies on shared parent domain.** `SESSION_DOMAIN` and `SESSION_COOKIE` config must be double-checked. stancl namespaces session storage; cookie names must not collide across subdomains.

## Success Criteria

- Existing JCD data is live at `jcd.localhost:8081` with no data loss.
- Super-admin logs into `admin.localhost:8081`, creates a second school "Demo University" with subdomain `demo`. Within ~30s the wizard shows a success page with subdomain URL, admin email, and temp password.
- `demo.localhost:8081/login` works with that temp password and forces a password change.
- Demo school has empty departments / faculty / students, but default criteria / questions / interventions are pre-populated.
- Logged in as Demo's admin, JCD data is invisible — no user IDs, no announcements, nothing leaks.
- ML API `/predict` from Demo returns a Demo-specific model (or trains one on the fly from Demo's data); does not return JCD's cached model.
- A cross-tenant leakage test passes in the test suite.
