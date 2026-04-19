# Activation Codes + Subscription Plans

**Date:** 2026-04-19
**Status:** Approved (brainstorm phase). Implementation plan to follow.
**Branch context:** Builds on `feature/multi-tenant-saas` (Phases 1–3 of multi-tenancy already merged on this branch).

## Context & Goals

Multi-tenancy works — super-admin can create a new school, the wizard provisions
a tenant DB, and a temp password is shown once for the school admin. This
iteration replaces the temp-password handoff with a sturdier **activation-code**
flow and adds a **subscription plan** concept so the platform looks (and reads)
like a real SaaS product.

**Goals:**

- School admins set their **own** password on first sign-in instead of receiving
  a temp password to change later.
- Each school carries a **plan tier** (Free / Pro / Enterprise) — visible in the
  super-admin dashboard and on a public marketing landing page.
- A super-admin can **revoke + regenerate** an activation code if the original
  is lost or the school missed the 30-day window.
- Public-facing pricing on `localhost:8081/` doubles as the entry point for
  schools redeeming a code (`/activate`).

**Non-goals (deliberately out of scope):**

- Real billing / Stripe / payments — plans are marketing-only labels, no enforcement.
- Plan-based feature gating (every school gets every feature regardless of plan).
- Public self-signup — schools are still created by super-admin.
- Email delivery of activation codes — super-admin copies the code on screen and
  shares out-of-band.
- Plan upgrade/downgrade UI — out of scope; manual DB edit if needed.

## Architecture Overview

Two-step handshake replacing today's one-step "create + temp password":

```
SUPER-ADMIN (admin.localhost)             SCHOOL ADMIN (out-of-band)
  1. Wizard form:
     - school name, subdomain
     - intended admin name + email
     - plan tier
  2. Submit                                 4. Visit localhost:8081/activate?code=XXXX-YYYY-ZZZZ
     → tenant inserted (status=provisioning)   5. Pick a password (twice)
     → DB created, migrated, seeded            6. Submit
       (ProvisionTenantJob unchanged)              → user inserted in tenant DB
     → tenant moves to status=pending_activation     with chosen password
     → activation code generated                  → code marked redeemed
  3. Success page (shown once):                  → tenant moves to status=active
     - the 12-char code                          → redirect to <sub>.localhost/login
     - the activation URL
     - plan, expiration, intended admin
```

**Tenant status lifecycle:**

```
provisioning  →  pending_activation  →  active  →  suspended
                       ↑                    ↓
                  regenerate      (super-admin toggle)
                       ↑
                expired / revoked
```

`provisioning` and `failed` keep their Phase-2 meanings. `pending_activation` is
new — the DB is ready, but no admin user exists yet and logins are blocked.

**Why provision DB *before* the school redeems the code:**

- Activation becomes a 1–2 second operation (just `User::create`), not a
  15-second migration wait.
- Provisioning failures surface in the super-admin dashboard *before* the school
  is told the code, so they don't see a broken page.

**Trade-off acknowledged:** if a school never redeems, an empty DB sits around.
Listed on the Plans page so super-admin can choose to clean up; no automated GC.

## Data Model

### `config/plans.php` (new — three fixed plan tiers)

Plans are config, not a DB table. Three tiers that change rarely; config keeps
the change surface tiny.

```php
return [
    'free' => [
        'slug'    => 'free',
        'name'    => 'Free',
        'price'   => 0,
        'period'  => 'forever',
        'tagline' => 'Try the platform with limited evaluations.',
        'features' => [
            'Up to 50 students',
            'Manual evaluations only',
            'Basic announcements',
            'Email support',
        ],
        'highlight' => false,
    ],
    'pro' => [
        'slug'    => 'pro',
        'name'    => 'Pro',
        'price'   => 99,
        'period'  => 'per month',
        'tagline' => 'Full evaluation toolkit for growing schools.',
        'features' => [
            'Unlimited students',
            'AI-powered performance predictions',
            'Sentiment analysis on feedback',
            'All evaluation types (peer, dean, self)',
            'Priority email support',
        ],
        'highlight' => true,
    ],
    'enterprise' => [
        'slug'    => 'enterprise',
        'name'    => 'Enterprise',
        'price'   => 'Custom',
        'period'  => '',
        'tagline' => 'For multi-campus institutions.',
        'features' => [
            'Everything in Pro',
            'Custom branding',
            'Dedicated success manager',
            'On-premise deployment option',
            'SLA-backed uptime',
        ],
        'highlight' => false,
    ],
];
```

Plans are referenced by **slug**. Adding/editing a tier later is a config edit.

### Central migration: add `plan` to `tenants`

```php
Schema::connection('central')->table('tenants', function (Blueprint $table) {
    $table->string('plan', 32)->default('free')->after('status');
});
```

`default('free')` grandfathers the existing JCD tenant in as Free. If you want
JCD shown as Pro for the demo, run `Tenant::find(1)->update(['plan' => 'pro'])`
after the migration.

### Central migration: extend `tenants.status` enum

```php
Schema::connection('central')->table('tenants', function (Blueprint $table) {
    $table->enum('status', ['provisioning', 'pending_activation', 'active', 'suspended', 'failed'])
        ->default('provisioning')
        ->change();
});
```

`pending_activation` joins the existing four values.

### Central migration: `activation_codes` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `tenant_id` | unsignedBigInteger, FK to `tenants.id` cascade | One tenant has a *history* of codes. Only one row with `status='unredeemed'` per tenant at any time, enforced in app code. |
| `code` | string(20), unique | The literal `XXXX-YYYY-ZZZZ` (12 chars + 2 hyphens). Unique across all tenants. |
| `plan` | string(32) | The plan slug being activated (`free` / `pro` / `enterprise`). |
| `intended_admin_name` | string | Pre-filled on the activation form, locked (read-only). |
| `intended_admin_email` | string | Same — locked. |
| `status` | enum (`unredeemed`, `redeemed`, `revoked`, `expired`) | Lifecycle. |
| `expires_at` | timestamp | `now() + 30 days` at generation. |
| `redeemed_at` | timestamp, nullable | Set on successful redemption. |
| `revoked_at` | timestamp, nullable | Set when super-admin clicks Revoke + Regenerate. |
| `created_at`, `updated_at` | timestamps | |

Indexes:
- Unique on `code` (redemption lookup).
- Composite `(tenant_id, status)` for "find this tenant's active code".

### `App\Models\ActivationCode`

Standard Eloquent on the `central` connection.

- `static generate(): string` — produces `XXXX-YYYY-ZZZZ` from the alphabet
  `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (excludes `0/O/1/I` for readability).
  Retries on the rare uniqueness collision.
- `isRedeemable(): bool` — `status === 'unredeemed' && expires_at > now()`.
- `BelongsTo tenant()`.

## Public Landing (`localhost:8081/`)

Replaces the current minimal central landing. One Blade view at
`resources/views/central/landing.blade.php` (path unchanged; content rewritten).
Tailwind via CDN. No JS, no auth.

**Sections:**

1. **Hero** — platform name + tagline + primary CTA "Got an activation code? →
   Activate" linking to `/activate`. Secondary `mailto:` "Contact sales".
2. **Pricing** — 3 columns rendered from `config('plans')`. Each card: name,
   price + period, tagline, features `<ul>`, CTA. Pro card gets a "Most
   popular" ribbon (`'highlight' => true`).
3. **How it works** — three short steps: *(1) We provision your school* →
   *(2) You receive your activation code* → *(3) Sign in with your chosen
   password*. Reinforces sales-led model.
4. **Footer** — copyright + a link back to `/activate`.

The route in `routes/web.php` stays the same — already domain-constrained per
central domain. Just the view changes.

## Activation Page

Two routes on the central domain:

| Method | Path | Name |
|---|---|---|
| GET | `/activate` | `central.activate.show` |
| POST | `/activate` | `central.activate.submit` |

Lives in `routes/web.php` alongside the central landing; loop-registered with
the same `Route::domain($centralDomain)` pattern as the landing route.

### `ActivationController@show`

Accepts `?code=XXXX-YYYY-ZZZZ` query param.

| Case | Response |
|---|---|
| Param missing | Empty form with just a code input |
| Code not found | "We couldn't find that code." |
| Code revoked | "This code was revoked. Contact your platform administrator." |
| Code redeemed | "This code was already used." |
| Code expired | "This code expired on &lt;date&gt;." |
| Code redeemable | Activation form pre-filled: school name shown, intended email shown (read-only), code field pre-filled, password fields empty |

### `ActivationController@submit`

```
Inputs: code, password, password_confirmation

1. Validate: code shape (12-char with hyphens), password (min 8, confirmed).
2. Look up code; reject if not redeemable (same messaging as GET).
3. Load tenant; reject if tenant.status !== 'pending_activation'.
4. tenancy()->initialize($tenant)
5. try {
       User::create([
           'name'                 => $code->intended_admin_name,
           'email'                => $code->intended_admin_email,
           'password'             => $request->password,   // 'hashed' cast
           'roles'                => ['admin'],            // 'array' cast
           'is_active'            => true,
           'must_change_password' => false,
       ]);
   } catch (UniqueConstraintViolationException $e) {
       // Re-issuing into a half-activated tenant
       return back()->withErrors([
           'code' => 'An admin user already exists for this school. Contact the platform operator.',
       ]);
   } finally {
       tenancy()->end();
   }
6. $code->update(['status' => 'redeemed', 'redeemed_at' => now()])
7. $tenant->update(['status' => 'active'])
8. Redirect to a success view: "<School Name> is ready. Sign in at
   http://<sub>.localhost:8081/login" + a button.
```

### Throttling

Apply Laravel's built-in `throttle:5,1` to POST `/activate` (5 attempts per
minute per IP). Codes are 12 chars over a 32-char alphabet (~3×10²¹ space) so
brute-force is impractical, but throttle keeps the floor solid.

### `tenant.active` middleware update

The existing `EnsureTenantIsActive` middleware blocks suspended tenants with a
403 page. Extend the message to switch on status:

| Status | Copy |
|---|---|
| `suspended` | "This school is currently suspended. Contact your platform administrator if this is unexpected." |
| `pending_activation` | "This school hasn't been activated yet. Visit localhost:8081/activate to redeem your code." |
| `failed` / `provisioning` | "This school is being set up. Try again in a moment." |

One Blade view (`resources/views/tenancy/suspended.blade.php`) with a `match`
expression on the status. Status code stays `403`.

## Wizard Rewrite (`admin.localhost/tenants/create`)

### Form fields

| Field | Notes |
|---|---|
| School name | unchanged |
| Subdomain | unchanged (`AvailableSubdomain` rule) |
| First admin name | unchanged — stored on the activation code as `intended_admin_name` |
| First admin email | unchanged — stored on the activation code as `intended_admin_email` |
| **Plan** | NEW — radio group rendered from `config('plans')`, default `free` |

### `TenantController@store` flow

1. Validate inputs (subdomain rule + plan must be a valid slug).
2. Insert `tenants` row with `status='provisioning'`, `plan=$request->plan`.
3. Mirror subdomain into `domains` table (unchanged).
4. Update `database` to `tenant_<id>` (unchanged).
5. **Run `ProvisionTenantJob` synchronously** (unchanged — DB + migrations +
   template seed). On failure, redirect to show page with error and stop.
6. **NEW:** transition tenant to `status='pending_activation'`.
7. **NEW:** create an `ActivationCode` row: `code = ActivationCode::generate()`,
   plan = chosen plan, intended_admin_name/email pre-filled from form,
   `expires_at = now() + 30 days`, `status = 'unredeemed'`.
8. Redirect to a new `created.blade.php` view that shows: code, activation URL
   (`http://localhost:8081/activate?code=XXXX-YYYY-ZZZZ`), plan, intended admin
   email, expiration. Big yellow "Copy code" button. "Shown once" framing.

### `ProvisionTenantJob` change

- Drop the `createFirstAdmin()` step. Job ends after seeding.
- Constructor no longer takes `adminName` / `adminEmail` / `adminTempPassword` —
  those move to the `activation_codes` row.
- Step 6 in handle() (mark active) becomes "no-op for new tenants" — the
  caller (`TenantController@store`) sets `pending_activation` instead.

To keep the job re-usable from a future "retry provisioning" flow, the job
itself does NOT touch tenant status. The caller decides what status to set
after the job succeeds.

## Internal Plans Dashboard (`admin.localhost/plans`)

New menu item in the super-admin dashboard. Operator-only.

### Layout

1. **Header** — "Plans" title + breadcrumb back to Schools.

2. **Plan cards row** — three plans rendered from `config('plans')` with extras:
   - Count badge: number of tenants with `plan=<slug>` AND `status='active'`.
   - "View schools on this plan" link → `/tenants?plan=<slug>`.

3. **Activation codes table** — all codes across tenants:

   | Tenant | Code | Plan | Status | Generated | Expires | Actions |
   |---|---|---|---|---|---|---|
   | Demo University | `JCDU-7K3M-9PQ2` | Pro | unredeemed | 2 days ago | 28 days | [Copy] [Revoke] |
   | St Mary's | `4NPF-X2LW-K8RV` | Free | redeemed | 5 days ago | — | — |
   | Old Test | `HMPK-9DQR-TZ7L` | Pro | expired | 35 days ago | (expired) | [Regenerate] |

   Filterable by status (default: hide `redeemed`).

### Routes added to `routes/admin.php`

```
GET   /plans                                  admin.plans.index
POST  /tenants/{tenant}/codes/regenerate      admin.tenants.codes.regenerate
POST  /tenants/{tenant}/codes/{code}/revoke   admin.tenants.codes.revoke
```

### Side touches to existing tenant pages

- **Tenants index (`/tenants`):** add a `Plan` column with color-coded badge
  (gray=Free, blue=Pro, purple=Enterprise). Add `?plan=` and
  `?status=pending_activation` query filters. Surface a "Pending activation"
  pill at the top when any exist.
- **Tenant show page (`/tenants/{id}`):** new Activation section above the
  Provisioning History block:
  - Tenant has current unredeemed code → show the code, activation URL,
    expiration, [Copy] + [Revoke + Regenerate] buttons.
  - Tenant has redeemed code → "Activated by &lt;email&gt; on &lt;date&gt;".
  - Tenant's last code revoked/expired → status + [Regenerate] button.

### `TenantController` gains methods

- `regenerateCode(Tenant $tenant)` — marks any current `unredeemed` code as
  `revoked` (with `revoked_at = now()`), generates a new one, redirects to
  show page with new code flashed.
- `revokeCode(Tenant $tenant, ActivationCode $code)` — marks `revoked`, leaves
  tenant in current status.

### `PlanController` (new, small)

- `index()` — gathers plan counts via `Tenant::groupBy('plan')->selectRaw(...)`,
  loads recent activation codes, renders the dashboard.

**Re-issue policy:** regenerate uses the **same plan** as the revoked code.
Plan changes after activation are out of scope (manual DB edit if needed).

## Testing Strategy

Three new feature tests. Existing Phase 1+2+3 tests must continue passing.

### `ActivationFlowTest`

- GET `/activate?code=<valid>` pre-fills the form.
- GET `/activate?code=<invalid|revoked|redeemed|expired>` shows the right error.
- POST `/activate` with valid code + matching passwords creates the user inside
  the tenant DB, transitions tenant to `active`, marks code `redeemed`.
- POST `/activate` with mismatched `password_confirmation` rejects.
- POST `/activate` with already-redeemed code refuses (idempotency check).

### `WizardCreatesActivationCodeTest`

- Submitting the wizard creates a tenant in `pending_activation` state with a
  fresh `unredeemed` activation code.
- Asserts no user row exists in the tenant DB yet (deferred to redemption).
- Asserts the code is unique per call.

### `RegenerateCodeTest`

- POST `/tenants/{id}/codes/regenerate` revokes the current code and creates a
  new unredeemed one with a new code value but the same plan + intended admin
  fields.

## Risks & Verification Notes

1. **Existing JCD tenant** picks up `plan='free'` from the migration default.
   Optional one-off tinker bump to `'pro'` for the demo.
2. **`pending_activation` tenants with empty user table** sit until manually
   cleaned. Listed prominently on the Plans page; no automated GC.
3. **Activation route is unauthenticated** — the code is the security boundary.
   Throttle + 12-char alphabet keep brute-force impractical.
4. **`Tenant::status` enum change** is a `change()` migration — requires
   `doctrine/dbal` in some Laravel versions; in Laravel 13 it should work
   natively. If migration fails, fall back to dropping + recreating the column
   with the wider enum (data preservation needed).
5. **Race condition:** if super-admin regenerates a code while a school admin is
   mid-redeem of the old code, the school admin sees "code revoked" on submit.
   Acceptable; rare.

## Success Criteria

- Public landing at `localhost:8081/` shows the 3-tier pricing, "Got an
  activation code?" CTA, and "How it works" section.
- Internal Plans page at `admin.localhost:8081/plans` shows plan distribution +
  pending codes; revoke/regenerate work.
- Wizard creates a school + activation code; success page shows the code + URL
  shown once.
- School admin visits the URL, picks a password, redirects to their subdomain
  login where their chosen password works.
- Existing JCD school continues to function (grandfathered as Free; optionally
  bumped to Pro).
- All Phase 1+2+3 tests still pass.
- Three new feature tests pass.
