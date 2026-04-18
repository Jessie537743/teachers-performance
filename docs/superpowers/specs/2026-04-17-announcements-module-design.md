# Announcements Module — Design Spec

**Date:** 2026-04-17
**Status:** Approved (brainstorming phase); ready for implementation planning
**Author:** iamroms (via Claude brainstorming)

## 1. Goals and non-goals

### Goals

- Let the Teachers Performance app publish announcements that reach a precisely-targeted set of users (roles, departments, individuals, with exclusion rules).
- Show announcements on the **login page** for unauthenticated visitors (a curated subset), and across the **authenticated app shell** (banner for critical, notification bell + archive page for the rest).
- Support tiered authoring: department-scoped authors (Dept Head, Dean) and system-wide authors (HR Officer, VP Academic, School President).
- Track per-user read state, with explicit acknowledgement required for critical announcements.

### Non-goals

- Email / push / SMS fan-out. This is in-app display only. Any cross-channel delivery is a separate feature.
- File attachments. Body-embedded links cover expected needs.
- Localization of announcement content beyond the app's default locale.
- A WYSIWYG authoring surface. Source of truth is markdown.
- A college-scoped tier. The schema has no `colleges` table; Dean is scoped to one department, same as Dept Head.

## 2. Scope decisions (from brainstorming)

| Decision | Choice |
|---|---|
| Targeting | Full: roles, departments, individual users, with exclusions |
| Authoring | Tiered: department-scoped vs system-scoped |
| In-app UX | Banner for `critical` (explicit ack), bell + archive page for `normal`/`info` |
| Login page UX | Small info box beside the form; items explicitly flagged `show_on_login` |
| Content format | Markdown source, server-rendered to sanitized HTML |
| Lifecycle | `publish_at` + `expires_at` + draft/published/archived status + pinned flag |
| Architecture | Polymorphic `announcement_targets` table (approach A from brainstorming) |

## 3. Data model

Four new tables (plus a cookie, not a table, for guest login-page dismissals).

### 3.1 `announcements`

Core row.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | varchar(200) | |
| `body_markdown` | text | Source of truth. |
| `body_html` | text | Cached render (CommonMark → HtmlPurifier). Recomputed on save. |
| `priority` | enum('info','normal','critical') | default `'normal'` |
| `is_pinned` | bool | default `false`; orthogonal to priority |
| `everyone` | bool | default `false`; fast-path that skips the targets table |
| `show_on_login` | bool | default `false` |
| `status` | enum('draft','published','archived') | default `'draft'` |
| `publish_at` | datetime nullable | null + status=published → publish immediately |
| `expires_at` | datetime nullable | null → no expiry |
| `created_by` | FK users.id | |
| `updated_by` | FK users.id nullable | |
| `created_at`, `updated_at` | timestamps | |

**Indexes:**
- `(status, publish_at, expires_at)` for the active-list hot path
- `(show_on_login, status)` for the login query
- `(is_pinned, publish_at)` for archive ordering

### 3.2 `announcement_targets`

Polymorphic include/exclude rules.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `announcement_id` | FK announcements.id, cascade delete | |
| `target_type` | enum('role','department','user') | |
| `target_id` | varchar(64) | role name (string) OR department_id (numeric) OR user_id (numeric), stored as string |
| `is_exclude` | bool | default `false` |

**Constraints:**
- `UNIQUE (announcement_id, target_type, target_id, is_exclude)`
- `INDEX (announcement_id, is_exclude)`

The `everyone` flag on the parent row is the fast-path; when set, this table is ignored for include-matching (but excludes still apply, so you can say "everyone except role=student").

### 3.3 `announcement_reads`

Per-user read state.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `announcement_id` | FK announcements.id, cascade delete | |
| `user_id` | FK users.id, cascade delete | |
| `read_at` | datetime | set when bell opened or archive visited |
| `acknowledged_at` | datetime nullable | set only for `priority=critical`, via explicit ack click |

**Constraints:**
- `UNIQUE (announcement_id, user_id)`

### 3.4 Guest session dismissals

Not a table. A signed cookie `ann_dismissed` holds a short comma-separated list of dismissed announcement IDs for a 24h TTL, cleared on login. Handles the "visitor dismissed a login-page notice" case without writing anonymous rows to the DB.

## 4. Permissions

Three new permission strings, plugged into the existing `role_permissions` / `PermissionDelegation` system.

| Permission | Meaning |
|---|---|
| `announcements.manage.department` | Create/edit/delete announcements scoped to the author's own `department_id`. Cannot set `everyone=true`. Cannot target other departments, arbitrary users outside their dept, or roles. |
| `announcements.manage.system` | Full authoring — any target combination. |
| `announcements.view` | Implicit for every authenticated user; no explicit row. Guests always see the login-page box. |

**Default assignments (via seeder):**

- Department Head → `announcements.manage.department`
- Dean → `announcements.manage.department`
- HR Officer, VP Academic, School President → `announcements.manage.system`

**Enforcement.** `AnnouncementPolicy@create|update|delete` gates the admin controller. For `announcements.manage.department`, the policy additionally validates every submitted target:

1. `everyone` must be `false`.
2. Every include target must be **either** `target_type=department` with `target_id = author.department_id`, **or** `target_type=user` where that user's `department_id = author.department_id`.
3. `target_type=role` is rejected outright (too broad).

Excludes follow the same scope rules as includes — a department-scoped author may only add exclude rows targeting users in their own department.

**Delegation.** No new work; the existing `PermissionDelegation` mechanism handles temporary reassignment because these are plain permission strings.

**Audit.** Create/update/delete/archive write to the existing `audit_logs` table. Critical acks also log (useful if "prove everyone saw this policy" ever comes up).

## 5. Visibility and read state

### 5.1 Visibility query (authenticated)

Single service: `App\Services\AnnouncementVisibility::activeFor(User $user): Collection`.

```sql
SELECT a.* FROM announcements a
WHERE a.status = 'published'
  AND (a.publish_at IS NULL OR a.publish_at <= NOW())
  AND (a.expires_at IS NULL OR a.expires_at >  NOW())
  AND (
        a.everyone = 1
     OR EXISTS (
          SELECT 1 FROM announcement_targets t
          WHERE t.announcement_id = a.id
            AND t.is_exclude = 0
            AND (
                  (t.target_type = 'role'       AND t.target_id IN (:user_roles))
               OR (t.target_type = 'department' AND t.target_id = :user_department_id)
               OR (t.target_type = 'user'       AND t.target_id = :user_id)
            )
     )
  )
  AND NOT EXISTS (
       SELECT 1 FROM announcement_targets t
        WHERE t.announcement_id = a.id
          AND t.is_exclude = 1
          AND (
                (t.target_type = 'role'       AND t.target_id IN (:user_roles))
             OR (t.target_type = 'department' AND t.target_id = :user_department_id)
             OR (t.target_type = 'user'       AND t.target_id = :user_id)
          )
  )
ORDER BY a.is_pinned DESC,
         FIELD(a.priority,'critical','normal','info'),
         COALESCE(a.publish_at, a.created_at) DESC
```

`FIELD()` is MySQL-specific; the project runs MySQL 8.0 in dev and Railway, so that's fine. If the query needs to stay portable, replace with a `CASE WHEN` expression or an integer `priority_sort` column.

Results memoized per request (one call per HTTP request at most). No Redis/file cache; invalidation on edit gets fiddly and query volume is low.

### 5.2 Visibility query (login page)

`activeForLogin(): Collection` is the same query minus target joins, filtered by `show_on_login = 1`, limit 3. Runs on the unauthenticated GET `/login`.

### 5.3 Read state

Driven by `announcement_reads`.

| Event | Effect |
|---|---|
| User opens the bell dropdown | Bell lists every visible announcement (critical included). For each **non-critical** unread item in the list, bulk upsert with `read_at = NOW()`. Critical items stay unacknowledged until the banner ack click. Single query. |
| User visits `/announcements` archive | Same — mark all listed items as read on page load. |
| User clicks "I've read this" on a critical banner | Upsert with both `read_at` **and** `acknowledged_at` set. Banner disappears. |
| Guest dismisses a login-page item | Signed cookie `ann_dismissed` appends the id; 24h TTL; cleared on login. |

The **unread count** = `visibleCount − readCount`, one cheap query, cached per-request.
The **critical banner** renders iff the user has at least one visible `priority=critical` announcement with no `acknowledged_at` row.

### 5.4 Lifecycle

```
draft  ──publish──▶  published  ──expires_at passes──▶  (auto-hidden, row kept)
  ▲                      │
  │                      └──author archives──▶  archived
  └── author unpublishes (back to draft)
```

- `draft` — invisible to everyone, editable by author.
- `published` — visible when `publish_at ≤ now < expires_at`. Scheduled before `publish_at`; auto-hidden after `expires_at`.
- `archived` — explicit author action, removed from every list. Row kept for audit.

No cron job is required; expiry is enforced in the visibility query. An optional `announcements:prune` artisan command may hard-delete `archived` rows older than 12 months; not required in v1.

### 5.5 Edit semantics

Edits **do not** reset read state by default (prevents typo fixes from re-popping a banner for everyone). The edit form shows a "Notify readers again" checkbox when `body_markdown` or `priority` changes; checking it deletes matching `announcement_reads` rows so the announcement re-appears as unread.

## 6. Components

### 6.1 Backend layout

```
app/
├── Models/
│   ├── Announcement.php           (fillable, casts, relationships, scopes: published(), active())
│   ├── AnnouncementTarget.php
│   └── AnnouncementRead.php
├── Http/Controllers/
│   ├── AnnouncementController.php            (user-facing: index, show, markRead, ack)
│   └── Admin/AnnouncementManagementController.php  (CRUD)
├── Services/
│   ├── AnnouncementVisibility.php  (activeFor, activeForLogin, unreadCountFor)
│   └── MarkdownRenderer.php        (CommonMark → HtmlPurifier pipeline)
├── Policies/
│   └── AnnouncementPolicy.php
├── View/Composers/
│   └── AnnouncementComposer.php    (binds active announcements to layouts/app + layouts/guest)
└── Http/Requests/
    ├── StoreAnnouncementRequest.php
    └── UpdateAnnouncementRequest.php
```

### 6.2 Routes (`routes/web.php`)

```php
// Authenticated, already inside the auth + must.change.password group
Route::get('/announcements',                          [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}',           [AnnouncementController::class, 'show'])->name('announcements.show');
Route::post('/announcements/{announcement}/read',     [AnnouncementController::class, 'markRead'])->name('announcements.read');
Route::post('/announcements/{announcement}/ack',      [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');
Route::post('/announcements/mark-read-batch',         [AnnouncementController::class, 'markReadBatch'])->name('announcements.read-batch');

// Admin (permission-gated by policy)
Route::resource('admin/announcements', Admin\AnnouncementManagementController::class)
    ->except(['show'])
    ->names('admin.announcements');
Route::post('admin/announcements/{announcement}/archive', [Admin\AnnouncementManagementController::class, 'archive'])
    ->name('admin.announcements.archive');
```

The login page is already public (`/login`); it needs no new route — the view composer feeds `layouts/guest.blade.php`.

### 6.3 Views

**Layout partials:**
- `layouts/partials/announcement-banner.blade.php` — top of `layouts/app.blade.php`. Renders a fixed banner only if the user has a visible unacknowledged critical announcement. Non-dismissible; "I've read this" button is the only exit.
- `layouts/partials/announcement-bell.blade.php` — Alpine dropdown in `layouts/navigation.blade.php` near the existing user menu. Shows badge + up to 5 most recent visible announcements; a "View all" link to `/announcements`.
- `layouts/partials/login-announcements.blade.php` — rendered in `layouts/guest.blade.php`, beside the login form. Up to 3 items, each dismissible via the signed cookie.

**User-facing pages:**
- `announcements/index.blade.php` — archive list. Ordering: pinned desc, then priority (critical, normal, info), then publish date desc. Auto-marks-read on page load.
- `announcements/show.blade.php` — single full view.

**Admin pages:**
- `admin/announcements/index.blade.php` — list with draft/published/archived tabs; filters; actions (edit, archive, delete).
- `admin/announcements/create.blade.php` / `edit.blade.php` — form fields:
  - Title, markdown body (textarea with client-side live preview via `marked.js`)
  - `priority`, `is_pinned`, `show_on_login` toggles
  - Schedule: `publish_at`, `expires_at` datetime pickers
  - Targeting UI: radio (Everyone / Specific). When Specific, three multi-select widgets (roles / departments / users), each with an "exclude" tab.
  - "Notify readers again" checkbox — only visible on edit, only shown when `body_markdown` or `priority` has pending changes.

### 6.4 Client-side

- **Bell dropdown (Alpine):** on `@click.open`, optimistically zero the badge and POST `/announcements/mark-read-batch` with the currently-visible unread IDs.
- **Critical banner:** the ack button POSTs `/announcements/{id}/ack`; on success the banner DOM node is removed via a Turbo frame swap.
- **Login box:** vanilla JS dismiss → appends id to `ann_dismissed` cookie and removes the card.
- **Markdown preview (admin):** client-side `marked.js` for live preview only. Real render is server-side and sanitized; the preview is "good enough," not authoritative.

## 7. Markdown pipeline

`MarkdownRenderer::render(string $markdown): string`

1. Parse with `league/commonmark` using the default extension set (tables, auto-linking). GFM is fine but not required.
2. Sanitize output HTML with `mews/purifier` (HtmlPurifier wrapper for Laravel) using a tight allowlist: `p, br, strong, em, a, ul, ol, li, blockquote, code, pre, h2, h3, h4, hr, table, thead, tbody, tr, th, td`.
3. Force `rel="noopener noreferrer"` and `target="_blank"` on `<a>`.
4. Reject any tag/attribute not on the allowlist; drop inline styles and scripts.

The rendered HTML is stored in `announcements.body_html` on save so the read path never touches the markdown parser.

## 8. Testing

Feature tests (`tests/Feature/Announcements/`):

1. **VisibilityTest** — matrix of {everyone / role / department / user / exclude} × {draft / scheduled / published / expired / archived}. Asserts who sees what.
2. **AuthorizationTest** — department-scoped author cannot create system-wide; cannot target another department's user or a role; system-scoped author has no restrictions; delegated permissions grant the expected reach.
3. **ReadStateTest** — bell open marks non-critical items read; critical requires explicit ack before banner disappears; guest cookie isolates login-page dismissals.
4. **LifecycleTest** — draft invisible; expired invisible; archived invisible; edit without checkbox does not reset reads; edit with checkbox does.
5. **RenderingTest** — markdown renders correctly; XSS payloads (`<script>`, `<img onerror>`, `javascript:` URLs) are stripped; `<a>` tags get `rel="noopener noreferrer"`.

Unit tests:
- `AnnouncementVisibility::activeFor()` with seeded fixtures (roles, departments, users, various target combos).
- `MarkdownRenderer::render()` on a handful of hostile payloads.

Manual smoke:
- Login page shows the box; dismissal persists across page reloads within the session.
- Bell + banner appear on every page of the authenticated shell (spot-check dashboard, admin, profile).
- Archive page loads, reads are marked, bell badge drops to zero.

## 9. Dependencies

New composer packages:

- `league/commonmark` — markdown parsing.
- `mews/purifier` — HtmlPurifier wrapper; the project doesn't currently depend on a sanitizer.

No new npm packages — `marked.js` for admin live preview is loaded via CDN inside the edit view.

## 10. Migrations and seeding

Migration ordering:

1. `create_announcements_table` (includes columns + indexes listed in §3.1).
2. `create_announcement_targets_table` (§3.2).
3. `create_announcement_reads_table` (§3.3).
4. Permission seeder update — insert three new `role_permissions` rows per the defaults in §4.

Rollback drops in reverse order; the permission seeder is idempotent (check-then-insert).

## 11. Open judgment calls

These were flagged during brainstorming and accepted as-designed; listing them here so a future reader can revisit without re-deriving the trade-off.

- **`target_id` is `VARCHAR(64)`**, not two nullable columns. Less normalized, but keeps the targets table compact and the query flat.
- **Per-request memo**, not a cross-request cache. Correctness > 20ms for this feature's query volume.
- **Live markdown preview uses a different renderer** from the server. Rare formatting surprises possible; UX cost considered acceptable.
- **Bell-open auto-marks-read**, rather than requiring a per-item click. Simpler; matches how most notification UIs behave.
- **No `colleges` table** — Dean collapses to department scope. If a college tier is ever added, `target_type='college'` fits without schema churn.

## 12. Out of scope (explicitly deferred)

- Email / push / SMS channels.
- Attachments.
- Per-announcement rich analytics ("N users read, M acknowledged"). The data is there (`announcement_reads`) but no dashboard in v1.
- A recurring/repeating announcement schedule (every Monday, etc.).
- Markdown extensions beyond CommonMark defaults.
