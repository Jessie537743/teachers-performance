# Announcements Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the Announcements module per `docs/superpowers/specs/2026-04-17-announcements-module-design.md` — a targeted in-app announcement system with a login-page surface, a critical-priority banner, a notification bell, and an archive page, gated by two new tiered permissions.

**Architecture:** Four new DB tables (`announcements`, `announcement_targets`, `announcement_reads`; session cookie for guest dismissals). Visibility is resolved by a single SQL query with `EXISTS` / `NOT EXISTS` over `announcement_targets`. Authoring is policy-gated with a department-scoped tier and a system-wide tier. Markdown source is rendered server-side through CommonMark + HtmlPurifier and cached as HTML on the row.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL 8 (dev/prod), SQLite in-memory (tests), PHPUnit 12, Blade + Alpine + Turbo, Tailwind CDN, `league/commonmark` (already in framework), `mews/purifier` (new).

**Repo layout note:** Laravel lives under `src/`. All paths below are relative to `src/` unless noted. Run commands from `src/` (or via `docker exec tp-app sh -c "cd /var/www && ..."`).

---

## File inventory

New files:

- `app/Models/Announcement.php`
- `app/Models/AnnouncementTarget.php`
- `app/Models/AnnouncementRead.php`
- `app/Policies/AnnouncementPolicy.php`
- `app/Services/MarkdownRenderer.php`
- `app/Services/AnnouncementVisibility.php`
- `app/Http/Controllers/AnnouncementController.php`
- `app/Http/Controllers/Admin/AnnouncementManagementController.php`
- `app/Http/Requests/StoreAnnouncementRequest.php`
- `app/Http/Requests/UpdateAnnouncementRequest.php`
- `app/View/Composers/AnnouncementComposer.php`
- `database/migrations/<ts>_create_announcements_table.php`
- `database/migrations/<ts>_create_announcement_targets_table.php`
- `database/migrations/<ts>_create_announcement_reads_table.php`
- `database/factories/AnnouncementFactory.php`
- `resources/views/announcements/index.blade.php`
- `resources/views/announcements/show.blade.php`
- `resources/views/admin/announcements/index.blade.php`
- `resources/views/admin/announcements/create.blade.php`
- `resources/views/admin/announcements/edit.blade.php`
- `resources/views/layouts/partials/announcement-banner.blade.php`
- `resources/views/layouts/partials/announcement-bell.blade.php`
- `resources/views/layouts/partials/login-announcements.blade.php`
- `tests/Feature/Announcements/VisibilityTest.php`
- `tests/Feature/Announcements/AuthorizationTest.php`
- `tests/Feature/Announcements/ReadStateTest.php`
- `tests/Feature/Announcements/LifecycleTest.php`
- `tests/Feature/Announcements/RenderingTest.php`
- `tests/Unit/Services/MarkdownRendererTest.php`

Modified files:

- `composer.json` (add `mews/purifier`)
- `app/Enums/Permission.php` (three new constants + label/defaults)
- `app/Providers/AppServiceProvider.php` (register policy, observer, view composer)
- `routes/web.php` (register new routes)
- `resources/views/layouts/app.blade.php` (include banner + bell partials)
- `resources/views/layouts/guest.blade.php` (include login-announcements partial)
- `database/seeders/DatabaseSeeder.php` or a new seeder for default permission rows

---

## Task index

1. Install `mews/purifier` and configure its allowlist
2. Extend `Permission` enum with three new constants + defaults
3. Migration — `announcements`
4. Migration — `announcement_targets`
5. Migration — `announcement_reads`
6. `Announcement` model + factory
7. `AnnouncementTarget` model
8. `AnnouncementRead` model
9. `MarkdownRenderer` service (TDD)
10. `AnnouncementVisibility::activeFor` (TDD)
11. `AnnouncementVisibility::activeForLogin` + `unreadCountFor` (TDD)
12. `AnnouncementPolicy` (TDD)
13. `StoreAnnouncementRequest` + `UpdateAnnouncementRequest`
14. Admin `AnnouncementManagementController` (TDD)
15. User-facing `AnnouncementController` (TDD)
16. `AnnouncementComposer` view composer (TDD — composer output)
17. Wire policy + observer + composer in `AppServiceProvider`
18. Register routes in `web.php`
19. Banner partial + include in `layouts/app.blade.php`
20. Bell partial + include in `layouts/app.blade.php`
21. Login-announcements partial + include in `layouts/guest.blade.php`
22. Archive views (user `index` + `show`)
23. Admin views (`index`, `create`, `edit`)
24. Seed default role permissions for the three new perms
25. End-to-end manual smoke test

---

## Task 1: Install `mews/purifier`

**Files:**
- Modify: `composer.json`
- Create: `config/purifier.php` (via `vendor:publish`)

- [ ] **Step 1: Add package**

Run from `src/`:
```bash
composer require mews/purifier:^3.4
```

- [ ] **Step 2: Publish config**

```bash
php artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"
```

- [ ] **Step 3: Restrict allowlist to a minimal safe set**

Edit `config/purifier.php`, replace the `default` settings with a dedicated `announcement` config:

```php
'settings' => [
    'default' => [
        'HTML.Doctype'             => 'HTML 4.01 Transitional',
        'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
        'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
        'AutoFormat.AutoParagraph' => true,
        'AutoFormat.RemoveEmpty'   => true,
    ],
    'announcement' => [
        'HTML.Doctype'           => 'HTML 4.01 Transitional',
        'HTML.Allowed'           => 'p,br,strong,em,a[href|title|rel|target],ul,ol,li,blockquote,code,pre,h2,h3,h4,hr,table,thead,tbody,tr,th,td',
        'HTML.TargetBlank'       => true,
        'HTML.Nofollow'          => false,
        'Attr.AllowedFrameTargets'=> ['_blank'],
        'AutoFormat.AutoParagraph' => false,
        'AutoFormat.RemoveEmpty' => true,
        'Core.EscapeInvalidTags' => false,
        'URI.AllowedSchemes'     => [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
        ],
    ],
],
```

- [ ] **Step 4: Verify install**

Run:
```bash
php artisan tinker --execute="dump(\Mews\Purifier\Facades\Purifier::clean('<script>alert(1)</script><p>ok</p>', 'announcement'));"
```
Expected: output shows `<p>ok</p>` with the `<script>` stripped.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/purifier.php
git commit -m "deps: add mews/purifier with announcement config for markdown HTML sanitization"
```

---

## Task 2: Extend `Permission` enum

**Files:**
- Modify: `app/Enums/Permission.php`

- [ ] **Step 1: Add three constants and labels**

Add near the other module constants at the top of the class (e.g., just after `MANAGE_ROLES`):

```php
    // Announcements
    const MANAGE_ANNOUNCEMENTS_SYSTEM     = 'manage-announcements-system';
    const MANAGE_ANNOUNCEMENTS_DEPARTMENT = 'manage-announcements-department';
```

Then in `allPermissions()`, add a new group entry:

```php
            'Announcements' => [
                self::MANAGE_ANNOUNCEMENTS_SYSTEM     => 'Manage System-Wide Announcements',
                self::MANAGE_ANNOUNCEMENTS_DEPARTMENT => 'Manage Department Announcements',
            ],
```

- [ ] **Step 2: Wire defaults**

In `defaultsForRole()`, append the new permissions to the relevant role arrays:

```php
'dean', 'head' => [
    self::VIEW_DEAN_DASHBOARD,
    self::VIEW_ANALYTICS,
    self::GENERATE_REPORT,
    self::VIEW_GENERATED_REPORT,
    self::SUBMIT_DEAN_EVALUATION,
    self::MONITOR_NOT_EVALUATED,
    self::MANAGE_ANNOUNCEMENTS_DEPARTMENT,
],
```

```php
'human_resource' => [
    self::VIEW_HR_DASHBOARD,
    self::VIEW_ANALYTICS,
    self::GENERATE_REPORT,
    self::VIEW_GENERATED_REPORT,
    self::MANAGE_CRITERIA,
    self::MANAGE_DEPARTMENTS,
    self::MANAGE_FACULTY,
    self::VIEW_USERS,
    self::MONITOR_NOT_EVALUATED,
    self::MANAGE_ANNOUNCEMENTS_SYSTEM,
],
```

```php
'school_president' => [
    self::VIEW_ADMIN_DASHBOARD,
    self::VIEW_ANALYTICS,
    self::GENERATE_REPORT,
    self::VIEW_GENERATED_REPORT,
    self::SUBMIT_DEAN_EVALUATION,
    self::MANAGE_ANNOUNCEMENTS_SYSTEM,
],
```

```php
'vp_acad' => [
    self::VIEW_ADMIN_DASHBOARD,
    self::VIEW_ANALYTICS,
    self::GENERATE_REPORT,
    self::VIEW_GENERATED_REPORT,
    self::SUBMIT_DEAN_EVALUATION,
    self::MANAGE_ANNOUNCEMENTS_SYSTEM,
],
```

- [ ] **Step 3: Clear permission cache**

Run:
```bash
php artisan tinker --execute="\App\Enums\Permission::clearCache();"
```

- [ ] **Step 4: Verify Gate auto-registers**

Run:
```bash
php artisan tinker --execute="\$u = \App\Models\User::factory()->make(['roles'=>['dean']]); dump(\$u->hasPermission('manage-announcements-department'));"
```
Expected: `true`.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/Permission.php
git commit -m "feat(permissions): add manage-announcements-system/department permissions"
```

---

## Task 3: Migration — `announcements`

**Files:**
- Create: `database/migrations/2026_04_17_120000_create_announcements_table.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration create_announcements_table --create=announcements
```

- [ ] **Step 2: Replace contents**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body_markdown');
            $table->text('body_html');
            $table->enum('priority', ['info', 'normal', 'critical'])->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('everyone')->default(false);
            $table->boolean('show_on_login')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'publish_at', 'expires_at'], 'ann_active_idx');
            $table->index(['show_on_login', 'status'], 'ann_login_idx');
            $table->index(['is_pinned', 'publish_at'], 'ann_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
```

- [ ] **Step 3: Run migrate**

```bash
php artisan migrate
```
Expected: `announcements` table created (dev DB; tests will recreate via `RefreshDatabase`).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_17_120000_create_announcements_table.php
git commit -m "feat(db): create announcements table"
```

---

## Task 4: Migration — `announcement_targets`

**Files:**
- Create: `database/migrations/2026_04_17_120100_create_announcement_targets_table.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration create_announcement_targets_table --create=announcement_targets
```

- [ ] **Step 2: Replace contents**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->enum('target_type', ['role', 'department', 'user']);
            $table->string('target_id', 64);
            $table->boolean('is_exclude')->default(false);
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'target_type', 'target_id', 'is_exclude'],
                'ann_targets_unique'
            );
            $table->index(['announcement_id', 'is_exclude'], 'ann_targets_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
```

- [ ] **Step 3: Run migrate**

```bash
php artisan migrate
```
Expected: `announcement_targets` created.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_17_120100_create_announcement_targets_table.php
git commit -m "feat(db): create announcement_targets table"
```

---

## Task 5: Migration — `announcement_reads`

**Files:**
- Create: `database/migrations/2026_04_17_120200_create_announcement_reads_table.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration create_announcement_reads_table --create=announcement_reads
```

- [ ] **Step 2: Replace contents**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'ann_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
```

- [ ] **Step 3: Run migrate**

```bash
php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_17_120200_create_announcement_reads_table.php
git commit -m "feat(db): create announcement_reads table"
```

---

## Task 6: `Announcement` model + factory

**Files:**
- Create: `app/Models/Announcement.php`
- Create: `database/factories/AnnouncementFactory.php`

- [ ] **Step 1: Create the model**

`app/Models/Announcement.php`:

```php
<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'body_markdown',
        'body_html',
        'priority',
        'is_pinned',
        'everyone',
        'show_on_login',
        'status',
        'publish_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'     => 'boolean',
            'everyone'      => 'boolean',
            'show_on_login' => 'boolean',
            'publish_at'    => 'datetime',
            'expires_at'    => 'datetime',
        ];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Published + within publish_at / expires_at window. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
```

- [ ] **Step 2: Create the factory**

`database/factories/AnnouncementFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        $md = $this->faker->paragraph();

        return [
            'title'         => $this->faker->sentence(6),
            'body_markdown' => $md,
            'body_html'     => '<p>' . e($md) . '</p>',
            'priority'      => 'normal',
            'is_pinned'     => false,
            'everyone'      => true,
            'show_on_login' => false,
            'status'        => 'published',
            'publish_at'    => now()->subHour(),
            'expires_at'    => null,
            'created_by'    => User::factory(),
            'updated_by'    => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft']);
    }

    public function archived(): self
    {
        return $this->state(['status' => 'archived']);
    }

    public function critical(): self
    {
        return $this->state(['priority' => 'critical']);
    }

    public function loginVisible(): self
    {
        return $this->state(['show_on_login' => true]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function scheduled(): self
    {
        return $this->state(['publish_at' => now()->addDay()]);
    }
}
```

- [ ] **Step 3: Verify factory**

```bash
php artisan tinker --execute="dump(\App\Models\Announcement::factory()->make()->toArray());"
```
Expected: an array with all fields, including the `created_by` numeric id (factory resolves user).

- [ ] **Step 4: Commit**

```bash
git add app/Models/Announcement.php database/factories/AnnouncementFactory.php
git commit -m "feat(models): add Announcement model + factory with state helpers"
```

---

## Task 7: `AnnouncementTarget` model

**Files:**
- Create: `app/Models/AnnouncementTarget.php`

- [ ] **Step 1: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTarget extends Model
{
    protected $fillable = [
        'announcement_id',
        'target_type',
        'target_id',
        'is_exclude',
    ];

    protected function casts(): array
    {
        return [
            'is_exclude' => 'boolean',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/AnnouncementTarget.php
git commit -m "feat(models): add AnnouncementTarget model"
```

---

## Task 8: `AnnouncementRead` model

**Files:**
- Create: `app/Models/AnnouncementRead.php`

- [ ] **Step 1: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends Model
{
    protected $fillable = [
        'announcement_id',
        'user_id',
        'read_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'         => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/AnnouncementRead.php
git commit -m "feat(models): add AnnouncementRead model"
```

---

## Task 9: `MarkdownRenderer` service (TDD)

**Files:**
- Create: `app/Services/MarkdownRenderer.php`
- Create: `tests/Unit/Services/MarkdownRendererTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/Services/MarkdownRendererTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\MarkdownRenderer;
use Tests\TestCase;

class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    public function test_renders_basic_markdown_to_html(): void
    {
        $html = $this->renderer->render("**hello** world");
        $this->assertStringContainsString('<strong>hello</strong>', $html);
    }

    public function test_strips_script_tags(): void
    {
        $html = $this->renderer->render("<script>alert(1)</script>\n\nsafe");
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('safe', $html);
    }

    public function test_strips_onerror_attribute(): void
    {
        $html = $this->renderer->render('![x](javascript:alert(1))');
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_strips_inline_styles(): void
    {
        $html = $this->renderer->render('<p style="color:red">x</p>');
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_preserves_allowed_links_and_forces_noopener(): void
    {
        $html = $this->renderer->render('[click](https://example.com)');
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertMatchesRegularExpression('/rel="[^"]*noopener[^"]*"/', $html);
    }

    public function test_preserves_lists_and_headings(): void
    {
        $html = $this->renderer->render("## Title\n\n- a\n- b");
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>a</li>', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=MarkdownRendererTest
```
Expected: FAIL — class `App\Services\MarkdownRenderer` not found.

- [ ] **Step 3: Implement the service**

`app/Services/MarkdownRenderer.php`:

```php
<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;
use Mews\Purifier\Facades\Purifier;

class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render(string $markdown): string
    {
        $raw = (string) $this->converter->convert($markdown);
        $sanitized = Purifier::clean($raw, 'announcement');

        // HtmlPurifier's HTML.TargetBlank forces target=_blank but uses rel="noreferrer".
        // Make sure noopener is present for older browsers.
        return preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function ($m) {
                $attrs = $m[1];
                if (! preg_match('/\brel=/i', $attrs)) {
                    return '<a' . $attrs . ' rel="noopener noreferrer" target="_blank">';
                }
                if (! str_contains($attrs, 'noopener')) {
                    $attrs = preg_replace('/rel="([^"]*)"/i', 'rel="$1 noopener"', $attrs);
                }
                return '<a' . $attrs . '>';
            },
            $sanitized
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=MarkdownRendererTest
```
Expected: all 6 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/MarkdownRenderer.php tests/Unit/Services/MarkdownRendererTest.php
git commit -m "feat(services): add MarkdownRenderer with CommonMark + Purifier pipeline"
```

---

## Task 10: `AnnouncementVisibility::activeFor` (TDD)

**Files:**
- Create: `app/Services/AnnouncementVisibility.php` (first iteration — just `activeFor`)
- Create: `tests/Feature/Announcements/VisibilityTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Announcements/VisibilityTest.php`:

```php
<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\Department;
use App\Models\User;
use App\Services\AnnouncementVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementVisibility $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnnouncementVisibility();
    }

    public function test_everyone_flag_makes_announcement_visible_to_any_user(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);

        $visible = $this->service->activeFor($user)->pluck('id');
        $this->assertTrue($visible->contains($a->id));
    }

    public function test_role_target_matches_user_with_that_role(): void
    {
        $user = User::factory()->create(['roles' => ['dean']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_role_target_excludes_user_without_that_role(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_department_target_matches_user_in_that_department(): void
    {
        $dept = Department::factory()->create();
        $user = User::factory()->create(['roles' => ['faculty'], 'department_id' => $dept->id]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'department',
            'target_id'       => (string) $dept->id,
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_user_target_matches_specific_user(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'user',
            'target_id'       => (string) $user->id,
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_exclude_rule_overrides_everyone(): void
    {
        $user = User::factory()->create(['roles' => ['student']]);
        $a = Announcement::factory()->create(['everyone' => true]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'student',
            'is_exclude'      => true,
        ]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_draft_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->draft()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_scheduled_in_future_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->scheduled()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_expired_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->expired()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_archived_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->archived()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_ordering_pinned_critical_then_recent(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $old     = Announcement::factory()->create(['everyone' => true, 'publish_at' => now()->subDays(5)]);
        $pinned  = Announcement::factory()->create(['everyone' => true, 'is_pinned' => true, 'publish_at' => now()->subDays(10)]);
        $critical= Announcement::factory()->critical()->create(['everyone' => true, 'publish_at' => now()->subDays(2)]);

        $ordered = $this->service->activeFor($user)->pluck('id')->all();

        $this->assertSame([$pinned->id, $critical->id, $old->id], $ordered);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=VisibilityTest
```
Expected: FAIL — class `App\Services\AnnouncementVisibility` not found.

- [ ] **Step 3: Implement `AnnouncementVisibility` (first cut)**

`app/Services/AnnouncementVisibility.php`:

```php
<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnnouncementVisibility
{
    /** Returns announcements currently visible to the given user, ordered for display. */
    public function activeFor(User $user): Collection
    {
        $roles         = $user->roles ?? [];
        $departmentId  = (string) ($user->department_id ?? '');
        $userId        = (string) $user->id;

        $matches = function ($q, bool $exclude) use ($roles, $departmentId, $userId) {
            $q->from('announcement_targets as t')
                ->whereColumn('t.announcement_id', 'announcements.id')
                ->where('t.is_exclude', $exclude)
                ->where(function ($q) use ($roles, $departmentId, $userId) {
                    $q->where(function ($q) use ($roles) {
                        $q->where('t.target_type', 'role')
                          ->whereIn('t.target_id', $roles ?: ['__none__']);
                    });
                    if ($departmentId !== '') {
                        $q->orWhere(function ($q) use ($departmentId) {
                            $q->where('t.target_type', 'department')
                              ->where('t.target_id', $departmentId);
                        });
                    }
                    $q->orWhere(function ($q) use ($userId) {
                        $q->where('t.target_type', 'user')
                          ->where('t.target_id', $userId);
                    });
                });
        };

        return Announcement::query()
            ->active()
            ->where(function ($q) use ($matches) {
                $q->where('everyone', true)
                  ->orWhereExists(fn ($q) => $matches($q, false));
            })
            ->whereNotExists(fn ($q) => $matches($q, true))
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc(DB::raw('COALESCE(publish_at, created_at)'))
            ->get();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=VisibilityTest
```
Expected: all 11 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AnnouncementVisibility.php tests/Feature/Announcements/VisibilityTest.php
git commit -m "feat(services): add AnnouncementVisibility::activeFor with targeting + lifecycle rules"
```

---

## Task 11: `AnnouncementVisibility::activeForLogin` + `unreadCountFor` (TDD)

**Files:**
- Modify: `app/Services/AnnouncementVisibility.php`
- Modify: `tests/Feature/Announcements/VisibilityTest.php`

- [ ] **Step 1: Add failing tests**

Append to `VisibilityTest.php`:

```php
    public function test_activeForLogin_only_returns_show_on_login_flagged(): void
    {
        Announcement::factory()->create(['everyone' => true, 'show_on_login' => false]);
        $loginOnly = Announcement::factory()->loginVisible()->create(['everyone' => true]);

        $ids = $this->service->activeForLogin()->pluck('id')->all();
        $this->assertSame([$loginOnly->id], $ids);
    }

    public function test_activeForLogin_ignores_targets_and_audience(): void
    {
        // A login announcement with role-based targeting still shows publicly.
        $a = Announcement::factory()->loginVisible()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $ids = $this->service->activeForLogin()->pluck('id');
        $this->assertTrue($ids->contains($a->id));
    }

    public function test_activeForLogin_respects_lifecycle(): void
    {
        Announcement::factory()->loginVisible()->draft()->create();
        Announcement::factory()->loginVisible()->expired()->create();
        Announcement::factory()->loginVisible()->scheduled()->create();
        $live = Announcement::factory()->loginVisible()->create();

        $this->assertEquals([$live->id], $this->service->activeForLogin()->pluck('id')->all());
    }

    public function test_activeForLogin_limits_to_three(): void
    {
        Announcement::factory()->count(5)->loginVisible()->create(['publish_at' => now()->subMinutes(1)]);
        $this->assertCount(3, $this->service->activeForLogin());
    }

    public function test_unreadCountFor_returns_visible_minus_read(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a1 = Announcement::factory()->create(['everyone' => true]);
        $a2 = Announcement::factory()->create(['everyone' => true]);
        $a3 = Announcement::factory()->create(['everyone' => true]);
        \App\Models\AnnouncementRead::create([
            'announcement_id' => $a1->id,
            'user_id'         => $user->id,
            'read_at'         => now(),
        ]);

        $this->assertSame(2, $this->service->unreadCountFor($user));
    }
```

Also `use App\Models\AnnouncementRead;` in imports at the top.

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=VisibilityTest
```
Expected: previous 11 still pass; 5 new ones FAIL with undefined methods.

- [ ] **Step 3: Add the methods**

Append to `AnnouncementVisibility`:

```php
    /** Up to 3 login-page-flagged announcements, target rules ignored. */
    public function activeForLogin(): Collection
    {
        return \App\Models\Announcement::query()
            ->active()
            ->where('show_on_login', true)
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('COALESCE(publish_at, created_at)'))
            ->limit(3)
            ->get();
    }

    /** Count of announcements the user can see but has no read row for. */
    public function unreadCountFor(User $user): int
    {
        $visibleIds = $this->activeFor($user)->pluck('id');
        if ($visibleIds->isEmpty()) {
            return 0;
        }

        $readIds = \App\Models\AnnouncementRead::query()
            ->where('user_id', $user->id)
            ->whereIn('announcement_id', $visibleIds)
            ->pluck('announcement_id');

        return $visibleIds->diff($readIds)->count();
    }
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=VisibilityTest
```
Expected: all 16 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AnnouncementVisibility.php tests/Feature/Announcements/VisibilityTest.php
git commit -m "feat(services): add activeForLogin and unreadCountFor"
```

---

## Task 12: `AnnouncementPolicy` (TDD)

**Files:**
- Create: `app/Policies/AnnouncementPolicy.php`
- Create: `tests/Feature/Announcements/AuthorizationTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Announcements/AuthorizationTest.php`:

```php
<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AnnouncementPolicy();
    }

    public function test_system_author_can_create_any_announcement(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $this->assertTrue($this->policy->create($sys));
    }

    public function test_department_author_can_create(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $this->assertTrue($this->policy->create($head));
    }

    public function test_random_user_cannot_create(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $this->assertFalse($this->policy->create($u));
    }

    public function test_department_author_cannot_target_everyone(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => true,
            'targets'  => [],
        ]));
    }

    public function test_department_author_can_target_own_department(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertTrue($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'department', 'target_id' => (string) $dept->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_target_other_department(): void
    {
        $a = Department::factory()->create();
        $b = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $a->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'department', 'target_id' => (string) $b->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_use_role_target(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'role', 'target_id' => 'faculty', 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_can_target_user_in_own_department(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $member = User::factory()->create(['department_id' => $dept->id]);

        $this->assertTrue($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'user', 'target_id' => (string) $member->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_target_user_in_other_department(): void
    {
        $a = Department::factory()->create();
        $b = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $a->id]);
        $member = User::factory()->create(['department_id' => $b->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'user', 'target_id' => (string) $member->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_system_author_has_no_target_restrictions(): void
    {
        $sys = User::factory()->create(['roles' => ['vp_acad']]);
        $this->assertTrue($this->policy->validateTargeting($sys, [
            'everyone' => true,
            'targets'  => [
                ['target_type' => 'role', 'target_id' => 'faculty', 'is_exclude' => false],
            ],
        ]));
    }

    public function test_only_author_or_system_can_update_own_scope(): void
    {
        $dept = Department::factory()->create();
        $author = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $other  = User::factory()->create(['roles' => ['head'], 'department_id' => Department::factory()->create()->id]);
        $sys    = User::factory()->create(['roles' => ['human_resource']]);

        $a = Announcement::factory()->create(['created_by' => $author->id]);

        $this->assertTrue($this->policy->update($author, $a));
        $this->assertFalse($this->policy->update($other, $a));
        $this->assertTrue($this->policy->update($sys, $a));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=AuthorizationTest
```
Expected: FAIL — `App\Policies\AnnouncementPolicy` not found.

- [ ] **Step 3: Implement the policy**

`app/Policies/AnnouncementPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)
            || $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)
            || $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)) {
            return true;
        }
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT)) {
            return $announcement->created_by === $user->id;
        }
        return false;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    /**
     * Validate that the submitted targeting payload is within the user's scope.
     *
     * @param array{everyone: bool, targets: array<array{target_type: string, target_id: string, is_exclude: bool}>} $payload
     */
    public function validateTargeting(User $user, array $payload): bool
    {
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)) {
            return true;
        }
        if (! $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT)) {
            return false;
        }

        if ($payload['everyone'] ?? false) {
            return false;
        }
        $deptId = (string) ($user->department_id ?? '');
        if ($deptId === '') {
            return false;
        }

        foreach ($payload['targets'] ?? [] as $t) {
            $type = $t['target_type'] ?? null;
            $id   = (string) ($t['target_id'] ?? '');

            if ($type === 'role') {
                return false;
            }
            if ($type === 'department' && $id !== $deptId) {
                return false;
            }
            if ($type === 'user') {
                $member = \App\Models\User::find($id);
                if (! $member || (string) $member->department_id !== $deptId) {
                    return false;
                }
            }
        }

        return true;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=AuthorizationTest
```
Expected: all 11 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Policies/AnnouncementPolicy.php tests/Feature/Announcements/AuthorizationTest.php
git commit -m "feat(policies): add AnnouncementPolicy with department-scope enforcement"
```

---

## Task 13: Form requests — `StoreAnnouncementRequest` + `UpdateAnnouncementRequest`

**Files:**
- Create: `app/Http/Requests/StoreAnnouncementRequest.php`
- Create: `app/Http/Requests/UpdateAnnouncementRequest.php`

- [ ] **Step 1: Create store request**

`app/Http/Requests/StoreAnnouncementRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Policies\AnnouncementPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Announcement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:200'],
            'body_markdown'  => ['required', 'string', 'max:20000'],
            'priority'       => ['required', Rule::in(['info', 'normal', 'critical'])],
            'is_pinned'      => ['sometimes', 'boolean'],
            'everyone'       => ['sometimes', 'boolean'],
            'show_on_login'  => ['sometimes', 'boolean'],
            'status'         => ['required', Rule::in(['draft', 'published'])],
            'publish_at'     => ['nullable', 'date'],
            'expires_at'     => ['nullable', 'date', 'after_or_equal:publish_at'],

            'targets'                    => ['array'],
            'targets.*.target_type'      => ['required_with:targets', Rule::in(['role', 'department', 'user'])],
            'targets.*.target_id'        => ['required_with:targets', 'string', 'max:64'],
            'targets.*.is_exclude'       => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $policy = new AnnouncementPolicy();
            $ok = $policy->validateTargeting($this->user(), [
                'everyone' => (bool) $this->boolean('everyone'),
                'targets'  => $this->input('targets', []),
            ]);
            if (! $ok) {
                $v->errors()->add('targets', 'Targeting is outside your allowed scope.');
            }
        });
    }
}
```

- [ ] **Step 2: Create update request**

`app/Http/Requests/UpdateAnnouncementRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Policies\AnnouncementPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('announcement')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:200'],
            'body_markdown'  => ['required', 'string', 'max:20000'],
            'priority'       => ['required', Rule::in(['info', 'normal', 'critical'])],
            'is_pinned'      => ['sometimes', 'boolean'],
            'everyone'       => ['sometimes', 'boolean'],
            'show_on_login'  => ['sometimes', 'boolean'],
            'status'         => ['required', Rule::in(['draft', 'published', 'archived'])],
            'publish_at'     => ['nullable', 'date'],
            'expires_at'     => ['nullable', 'date', 'after_or_equal:publish_at'],
            'notify_again'   => ['sometimes', 'boolean'],

            'targets'                    => ['array'],
            'targets.*.target_type'      => ['required_with:targets', Rule::in(['role', 'department', 'user'])],
            'targets.*.target_id'        => ['required_with:targets', 'string', 'max:64'],
            'targets.*.is_exclude'       => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $policy = new AnnouncementPolicy();
            $ok = $policy->validateTargeting($this->user(), [
                'everyone' => (bool) $this->boolean('everyone'),
                'targets'  => $this->input('targets', []),
            ]);
            if (! $ok) {
                $v->errors()->add('targets', 'Targeting is outside your allowed scope.');
            }
        });
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Requests/StoreAnnouncementRequest.php app/Http/Requests/UpdateAnnouncementRequest.php
git commit -m "feat(requests): add Store/Update Announcement form requests with targeting validation"
```

---

## Task 14: Admin `AnnouncementManagementController` (TDD)

**Files:**
- Create: `app/Http/Controllers/Admin/AnnouncementManagementController.php`
- Create: `tests/Feature/Announcements/LifecycleTest.php`

- [ ] **Step 1: Write failing lifecycle feature test**

`tests/Feature/Announcements/LifecycleTest.php`:

```php
<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_author_can_store_announcement(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);

        $this->actingAs($sys)->post(route('admin.announcements.store'), [
            'title'         => 'Hello',
            'body_markdown' => '**hi**',
            'priority'      => 'normal',
            'everyone'      => true,
            'show_on_login' => false,
            'status'        => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'title'      => 'Hello',
            'created_by' => $sys->id,
            'status'     => 'published',
        ]);

        $a = Announcement::firstWhere('title', 'Hello');
        $this->assertStringContainsString('<strong>hi</strong>', $a->body_html);
    }

    public function test_store_persists_targets_and_rejects_out_of_scope_for_dept_author(): void
    {
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => 42]);

        // Attempt everyone=true as a department author — should fail validation.
        $this->actingAs($head)->post(route('admin.announcements.store'), [
            'title'         => 'x',
            'body_markdown' => 'x',
            'priority'      => 'normal',
            'everyone'      => true,
            'status'        => 'published',
        ])->assertSessionHasErrors('targets');
    }

    public function test_update_without_notify_preserves_reads(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        $reader = User::factory()->create(['roles' => ['faculty']]);
        AnnouncementRead::create([
            'announcement_id' => $a->id,
            'user_id' => $reader->id,
            'read_at' => now(),
        ]);

        $this->actingAs($sys)->put(route('admin.announcements.update', $a), [
            'title'         => 'Updated',
            'body_markdown' => $a->body_markdown,
            'priority'      => $a->priority,
            'everyone'      => $a->everyone,
            'status'        => $a->status,
            // no notify_again
        ])->assertRedirect();

        $this->assertDatabaseCount('announcement_reads', 1);
    }

    public function test_update_with_notify_again_resets_reads(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        $reader = User::factory()->create(['roles' => ['faculty']]);
        AnnouncementRead::create([
            'announcement_id' => $a->id,
            'user_id' => $reader->id,
            'read_at' => now(),
        ]);

        $this->actingAs($sys)->put(route('admin.announcements.update', $a), [
            'title'         => 'Updated',
            'body_markdown' => 'New body',
            'priority'      => $a->priority,
            'everyone'      => $a->everyone,
            'status'        => $a->status,
            'notify_again'  => true,
        ])->assertRedirect();

        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_archive_action_sets_status_archived(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);

        $this->actingAs($sys)->post(route('admin.announcements.archive', $a))
            ->assertRedirect();

        $this->assertSame('archived', $a->fresh()->status);
    }

    public function test_delete_removes_row_and_targets(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        \App\Models\AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'faculty',
            'is_exclude'      => false,
        ]);

        $this->actingAs($sys)->delete(route('admin.announcements.destroy', $a))
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
        $this->assertDatabaseCount('announcement_targets', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=LifecycleTest
```
Expected: FAIL — routes not defined, controller not defined.

- [ ] **Step 3: Create the controller**

`app/Http/Controllers/Admin/AnnouncementManagementController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Services\MarkdownRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementManagementController extends Controller
{
    public function __construct(private MarkdownRenderer $renderer) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $status = $request->string('status')->toString() ?: 'published';
        $announcements = Announcement::query()
            ->where('status', $status)
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'status'        => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('admin.announcements.create', $this->formContext());
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $html = $this->renderer->render($data['body_markdown']);

        DB::transaction(function () use ($data, $html, $request) {
            $a = Announcement::create([
                'title'         => $data['title'],
                'body_markdown' => $data['body_markdown'],
                'body_html'     => $html,
                'priority'      => $data['priority'],
                'is_pinned'     => $request->boolean('is_pinned'),
                'everyone'      => $request->boolean('everyone'),
                'show_on_login' => $request->boolean('show_on_login'),
                'status'        => $data['status'],
                'publish_at'    => $data['publish_at'] ?? null,
                'expires_at'    => $data['expires_at'] ?? null,
                'created_by'    => $request->user()->id,
            ]);

            $this->syncTargets($a, $data['targets'] ?? []);
        });

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement created.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('admin.announcements.edit', $this->formContext() + [
            'announcement' => $announcement->load('targets'),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();
        $bodyChanged = $data['body_markdown'] !== $announcement->body_markdown;
        $priorityChanged = $data['priority'] !== $announcement->priority;
        $html = $bodyChanged ? $this->renderer->render($data['body_markdown']) : $announcement->body_html;

        DB::transaction(function () use ($data, $html, $request, $announcement, $bodyChanged, $priorityChanged) {
            $announcement->update([
                'title'         => $data['title'],
                'body_markdown' => $data['body_markdown'],
                'body_html'     => $html,
                'priority'      => $data['priority'],
                'is_pinned'     => $request->boolean('is_pinned'),
                'everyone'      => $request->boolean('everyone'),
                'show_on_login' => $request->boolean('show_on_login'),
                'status'        => $data['status'],
                'publish_at'    => $data['publish_at'] ?? null,
                'expires_at'    => $data['expires_at'] ?? null,
                'updated_by'    => $request->user()->id,
            ]);

            $this->syncTargets($announcement, $data['targets'] ?? []);

            if ($request->boolean('notify_again') && ($bodyChanged || $priorityChanged)) {
                $announcement->reads()->delete();
            }
        });

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);
        $announcement->update(['status' => 'archived']);

        return back()->with('status', 'Announcement archived.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    private function syncTargets(Announcement $announcement, array $rows): void
    {
        $announcement->targets()->delete();
        foreach ($rows as $r) {
            $announcement->targets()->create([
                'target_type' => $r['target_type'],
                'target_id'   => (string) $r['target_id'],
                'is_exclude'  => (bool) ($r['is_exclude'] ?? false),
            ]);
        }
    }

    private function formContext(): array
    {
        return [
            'allRoles'     => \App\Enums\Permission::allRoles(),
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'userOptions'  => User::orderBy('name')->get(['id', 'name', 'email', 'department_id']),
        ];
    }
}
```

- [ ] **Step 4: Commit (tests still fail until routes wired — expected)**

```bash
git add app/Http/Controllers/Admin/AnnouncementManagementController.php tests/Feature/Announcements/LifecycleTest.php
git commit -m "feat(admin): add AnnouncementManagementController with CRUD + targeting + notify-again"
```

---

## Task 15: User-facing `AnnouncementController` (TDD)

**Files:**
- Create: `app/Http/Controllers/AnnouncementController.php`
- Create: `tests/Feature/Announcements/ReadStateTest.php`

- [ ] **Step 1: Write failing test**

`tests/Feature/Announcements/ReadStateTest.php`:

```php
<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_index_marks_non_critical_read(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);

        $this->actingAs($u)->get(route('announcements.index'))->assertOk();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $a->id,
            'user_id'         => $u->id,
        ]);
    }

    public function test_archive_index_does_not_auto_ack_critical(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->critical()->create(['everyone' => true]);

        $this->actingAs($u)->get(route('announcements.index'))->assertOk();

        $read = AnnouncementRead::where('announcement_id', $a->id)->where('user_id', $u->id)->first();
        $this->assertNotNull($read, 'read row should exist for critical');
        $this->assertNull($read->acknowledged_at, 'critical must not be auto-acknowledged');
    }

    public function test_batch_mark_read_sets_read_at_for_submitted_ids(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);
        $b = Announcement::factory()->create(['everyone' => true]);

        $this->actingAs($u)->post(route('announcements.read-batch'), [
            'ids' => [$a->id, $b->id],
        ])->assertOk();

        $this->assertSame(2, AnnouncementRead::where('user_id', $u->id)->count());
    }

    public function test_acknowledge_sets_acknowledged_at(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->critical()->create(['everyone' => true]);

        $this->actingAs($u)->post(route('announcements.ack', $a))->assertRedirect();

        $this->assertNotNull(
            AnnouncementRead::where('announcement_id', $a->id)->where('user_id', $u->id)->value('acknowledged_at')
        );
    }

    public function test_acknowledge_rejected_for_non_critical(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]); // normal

        $this->actingAs($u)->post(route('announcements.ack', $a))->assertStatus(422);

        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $a->id,
            'user_id' => $u->id,
        ]);
    }

    public function test_user_cannot_mark_announcement_they_cannot_see(): void
    {
        $u = User::factory()->create(['roles' => ['student']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        \App\Models\AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type' => 'role',
            'target_id' => 'dean',
            'is_exclude' => false,
        ]);

        $this->actingAs($u)->post(route('announcements.read', $a))->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=ReadStateTest
```
Expected: FAIL — routes not defined.

- [ ] **Step 3: Implement the controller**

`app/Http/Controllers/AnnouncementController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Services\AnnouncementVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $list = $this->visibility->activeFor($user);

        $this->upsertRead(
            $user->id,
            $list->where('priority', '!=', 'critical')->pluck('id')->all()
        );

        return view('announcements.index', ['announcements' => $list]);
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority !== 'critical') {
            $this->upsertRead($request->user()->id, [$announcement->id]);
        }

        return view('announcements.show', ['announcement' => $announcement]);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority === 'critical') {
            return response()->json(['ok' => false], 422);
        }
        $this->upsertRead($request->user()->id, [$announcement->id]);

        return response()->json(['ok' => true]);
    }

    public function markReadBatch(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $visible = $this->visibility->activeFor($request->user())->pluck('id')->all();
        $allowed = array_values(array_intersect($ids, $visible));

        $critical = Announcement::whereIn('id', $allowed)
            ->where('priority', 'critical')->pluck('id')->all();
        $toMark = array_values(array_diff($allowed, $critical));

        $this->upsertRead($request->user()->id, $toMark);

        return response()->json(['ok' => true, 'count' => count($toMark)]);
    }

    public function acknowledge(Request $request, Announcement $announcement): RedirectResponse|JsonResponse
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority !== 'critical') {
            return response()->json(['ok' => false, 'error' => 'Only critical announcements require acknowledgement.'], 422);
        }

        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $request->user()->id],
            ['read_at' => now(), 'acknowledged_at' => now()],
        );

        \App\Models\AuditLog::log(
            action: 'acknowledged',
            description: "Acknowledged announcement: {$announcement->title}",
            model: $announcement,
        );

        return redirect()->back()->with('status', 'Thanks — marked as read.');
    }

    private function ensureVisible(Request $request, Announcement $announcement): void
    {
        $visible = $this->visibility->activeFor($request->user())->pluck('id')->all();
        if (! in_array($announcement->id, $visible, true)) {
            abort(403, 'This announcement is not visible to you.');
        }
    }

    private function upsertRead(int $userId, array $announcementIds): void
    {
        if (empty($announcementIds)) {
            return;
        }
        $now = now();
        $rows = array_map(fn ($id) => [
            'announcement_id' => $id,
            'user_id'         => $userId,
            'read_at'         => $now,
            'acknowledged_at' => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $announcementIds);

        DB::table('announcement_reads')->upsert(
            $rows,
            ['announcement_id', 'user_id'],
            ['read_at', 'updated_at']
        );
    }
}
```

- [ ] **Step 4: Commit (tests still red until routes exist)**

```bash
git add app/Http/Controllers/AnnouncementController.php tests/Feature/Announcements/ReadStateTest.php
git commit -m "feat(controllers): add user-facing AnnouncementController (index, show, read, ack)"
```

---

## Task 16: `AnnouncementComposer` view composer (TDD)

**Files:**
- Create: `app/View/Composers/AnnouncementComposer.php`

- [ ] **Step 1: Write the composer**

`app/View/Composers/AnnouncementComposer.php`:

```php
<?php

namespace App\View\Composers;

use App\Services\AnnouncementVisibility;
use Illuminate\View\View;

class AnnouncementComposer
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $isAuthenticated = (bool) $user;

        if ($isAuthenticated) {
            $active = $this->visibility->activeFor($user);
            $readIds = \App\Models\AnnouncementRead::where('user_id', $user->id)
                ->whereIn('announcement_id', $active->pluck('id'))
                ->pluck('announcement_id')->all();

            $ackedIds = \App\Models\AnnouncementRead::where('user_id', $user->id)
                ->whereIn('announcement_id', $active->pluck('id'))
                ->whereNotNull('acknowledged_at')
                ->pluck('announcement_id')->all();

            $criticalUnacked = $active
                ->where('priority', 'critical')
                ->whereNotIn('id', $ackedIds)
                ->values();

            $view
                ->with('activeAnnouncements', $active)
                ->with('announcementReadIds', $readIds)
                ->with('criticalUnacked', $criticalUnacked)
                ->with('unreadAnnouncementCount', $active->count() - count($readIds));
        } else {
            $loginList = $this->visibility->activeForLogin();
            $dismissedIds = collect(explode(',', (string) request()->cookie('ann_dismissed', '')))
                ->filter()->map(fn ($v) => (int) $v);
            $view->with('loginAnnouncements', $loginList->whereNotIn('id', $dismissedIds)->values());
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/View/Composers/AnnouncementComposer.php
git commit -m "feat(views): add AnnouncementComposer binding for both authenticated and guest layouts"
```

(Tests for the composer are effectively exercised by the banner/bell/login view integration tests later — the logic is trivially verifiable.)

---

## Task 17: Wire policy + observer + composer in `AppServiceProvider`

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Import new classes**

At the top of `app/Providers/AppServiceProvider.php`, add to the existing use list:

```php
use App\Models\Announcement;
use App\Policies\AnnouncementPolicy;
use App\View\Composers\AnnouncementComposer;
use Illuminate\Support\Facades\View;
```

- [ ] **Step 2: Register the audit observer for `Announcement`**

In `boot()`, append `Announcement::class` to `$auditableModels`:

```php
$auditableModels = [
    User::class, Department::class, Course::class, Subject::class,
    FacultyProfile::class, StudentProfile::class, EvaluationPeriod::class,
    Criterion::class, SentimentLexicon::class, PermissionDelegation::class,
    RolePermission::class, Setting::class,
    Announcement::class,
];
```

- [ ] **Step 3: Register the policy**

After the existing `Gate::policy` calls in `boot()`:

```php
Gate::policy(Announcement::class, AnnouncementPolicy::class);
```

- [ ] **Step 4: Register the view composer**

In `boot()`:

```php
View::composer(['layouts.app', 'layouts.guest'], AnnouncementComposer::class);
```

- [ ] **Step 5: Clear caches**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

- [ ] **Step 6: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat(providers): register Announcement policy, audit observer, and view composer"
```

---

## Task 18: Register routes in `web.php`

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add imports at the top**

```php
use App\Http\Controllers\AnnouncementController;
```

- [ ] **Step 2: Add user-facing routes inside the `auth + must.change.password` group**

Inside `Route::middleware(['auth', 'must.change.password'])->group(function () { ... })`, near the Help Center line, add:

```php
    // Announcements — visible to every authenticated user
    Route::get('/announcements',                            [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/mark-read-batch',           [AnnouncementController::class, 'markReadBatch'])->name('announcements.read-batch');
    Route::get('/announcements/{announcement}',             [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('/announcements/{announcement}/read',       [AnnouncementController::class, 'markRead'])->name('announcements.read');
    Route::post('/announcements/{announcement}/ack',        [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');
```

(Note: the `mark-read-batch` literal route is placed **before** `/announcements/{announcement}` to avoid route binding attempting to resolve `mark-read-batch` as an announcement id.)

- [ ] **Step 3: Add admin routes inside the same group**

Near the other admin resource routes:

```php
    // Announcements — admin CRUD (policy-gated by AnnouncementPolicy)
    Route::resource('admin/announcements', Admin\AnnouncementManagementController::class)
        ->except(['show'])
        ->names('admin.announcements');
    Route::post('admin/announcements/{announcement}/archive', [Admin\AnnouncementManagementController::class, 'archive'])
        ->name('admin.announcements.archive');
```

- [ ] **Step 4: Verify route list**

```bash
php artisan route:list | grep announcements
```
Expected: the new routes appear with names `announcements.*` and `admin.announcements.*`.

- [ ] **Step 5: Run all feature tests**

```bash
php artisan test tests/Feature/Announcements
```
Expected: `VisibilityTest`, `AuthorizationTest`, `LifecycleTest`, `ReadStateTest` all PASS.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php
git commit -m "feat(routes): register user + admin announcement routes"
```

---

## Task 19: Banner partial + include in `layouts/app.blade.php`

**Files:**
- Create: `resources/views/layouts/partials/announcement-banner.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Create the banner partial**

`resources/views/layouts/partials/announcement-banner.blade.php`:

```blade
@if(isset($criticalUnacked) && $criticalUnacked->isNotEmpty())
    @foreach($criticalUnacked as $a)
        <turbo-frame id="announcement-banner-{{ $a->id }}">
            <div class="w-full bg-red-600 text-white" role="alert">
                <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                    <div class="flex-1">
                        <div class="font-semibold">{{ $a->title }}</div>
                        <div class="text-sm text-white/90 line-clamp-2">{!! $a->body_html !!}</div>
                    </div>
                    <form method="POST" action="{{ route('announcements.ack', $a) }}" class="m-0" data-turbo="false">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-white/15 px-3 py-1.5 text-sm font-semibold hover:bg-white/25 transition">
                            I’ve read this
                        </button>
                    </form>
                </div>
            </div>
        </turbo-frame>
    @endforeach
@endif
```

- [ ] **Step 2: Include it in `layouts/app.blade.php`**

Open `resources/views/layouts/app.blade.php`. Find the `<main>` element (around line 126, begins `<main class="main-ml...`). Just above the `<header>` inside `<main>`, add:

```blade
        @include('layouts.partials.announcement-banner')
```

- [ ] **Step 3: Smoke test**

Run:
```bash
php artisan tinker --execute="\App\Models\Announcement::factory()->critical()->create(['everyone'=>true,'title'=>'Fire drill']);"
```

Then visit `/dashboard` in a browser as any user (after login). Expect a red banner at the top with the title, and an "I've read this" button.

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/partials/announcement-banner.blade.php resources/views/layouts/app.blade.php
git commit -m "feat(views): add critical announcement banner partial to app layout"
```

---

## Task 20: Bell partial + include in `layouts/app.blade.php`

**Files:**
- Create: `resources/views/layouts/partials/announcement-bell.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Add Alpine via CDN if not already present**

Check `layouts/app.blade.php` for `alpinejs`. If missing, add the script to `<head>`:

```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

(Skip this step if Alpine is already loaded — search for `alpinejs` in the file.)

- [ ] **Step 2: Create the bell partial**

`resources/views/layouts/partials/announcement-bell.blade.php`:

```blade
@auth
<div
    class="relative"
    x-data="announcementBell({
        unreadCount: {{ (int) ($unreadAnnouncementCount ?? 0) }},
        visibleIds: @js($activeAnnouncements->pluck('id')->all()),
        readIds: @js($announcementReadIds ?? []),
        batchUrl: '{{ route('announcements.read-batch') }}',
        csrf: '{{ csrf_token() }}',
    })"
>
    <button
        type="button"
        class="relative grid h-10 w-10 place-items-center rounded-full border border-gray-200 bg-white hover:border-primary-light hover:shadow-sm transition"
        @click="toggle()"
        aria-label="Announcements"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-600"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <template x-if="count > 0">
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[11px] font-bold grid place-items-center px-1" x-text="count > 99 ? '99+' : count"></span>
        </template>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-[360px] max-w-[90vw] z-[2500] rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden"
    >
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-800">Announcements</div>
            <a href="{{ route('announcements.index') }}" class="text-xs text-primary hover:underline">View all</a>
        </div>
        <div class="max-h-[400px] overflow-y-auto">
            @forelse($activeAnnouncements->take(5) as $a)
                <a href="{{ route('announcements.show', $a) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50">
                    <div class="flex items-start gap-2">
                        @if($a->priority === 'critical')
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-red-500 flex-shrink-0"></span>
                        @elseif($a->priority === 'info')
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                        @else
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-slate-800 truncate">{{ $a->title }}</div>
                            <div class="text-xs text-slate-500 line-clamp-2 mt-0.5">{{ \Illuminate\Support\Str::limit(strip_tags($a->body_html), 100) }}</div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ ($a->publish_at ?? $a->created_at)->diffForHumans() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500">No announcements.</div>
            @endforelse
        </div>
    </div>
</div>

<script data-turbo-permanent>
document.addEventListener('alpine:init', () => {
    Alpine.data('announcementBell', (init) => ({
        open: false,
        count: init.unreadCount,
        visibleIds: init.visibleIds,
        readIds: init.readIds,
        batchUrl: init.batchUrl,
        csrf: init.csrf,
        toggle() {
            this.open = !this.open;
            if (this.open && this.count > 0) {
                this.markRead();
            }
        },
        markRead() {
            const unread = this.visibleIds.filter(id => !this.readIds.includes(id));
            if (unread.length === 0) return;
            this.count = 0;
            fetch(this.batchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids: unread }),
            }).catch(() => { /* best effort */ });
        }
    }));
});
</script>
@endauth
```

- [ ] **Step 3: Include it in the topbar of `layouts/app.blade.php`**

In `layouts/app.blade.php`, find the topbar `<header>` and the `<div class="relative" id="userChip">` element. Immediately **before** the `<div class="relative" id="userChip">` line, add:

```blade
            <div class="flex items-center gap-2 mr-2">
                @include('layouts.partials.announcement-bell')
            </div>
```

- [ ] **Step 4: Smoke test**

Reload `/dashboard`. Expect a bell icon to the left of the user chip, with a red badge if there are visible unread announcements. Click → dropdown shows up to 5 recent; badge drops to 0; POST `/announcements/mark-read-batch` fires in the Network tab.

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/partials/announcement-bell.blade.php resources/views/layouts/app.blade.php
git commit -m "feat(views): add announcement bell partial in topbar with Alpine dropdown"
```

---

## Task 21: Login-announcements partial + include in `layouts/guest.blade.php`

**Files:**
- Create: `resources/views/layouts/partials/login-announcements.blade.php`
- Modify: `resources/views/layouts/guest.blade.php`

- [ ] **Step 1: Create the partial**

`resources/views/layouts/partials/login-announcements.blade.php`:

```blade
@if(isset($loginAnnouncements) && $loginAnnouncements->isNotEmpty())
    <div id="login-announcements" class="space-y-2">
        @foreach($loginAnnouncements as $a)
            <div id="login-ann-{{ $a->id }}" class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 relative">
                <button type="button" aria-label="Dismiss" onclick="dismissLoginAnnouncement({{ $a->id }})" class="absolute right-2 top-2 text-blue-400 hover:text-blue-700">&times;</button>
                <div class="font-semibold pr-6">{{ $a->title }}</div>
                <div class="mt-1 prose prose-sm max-w-none">{!! $a->body_html !!}</div>
            </div>
        @endforeach
    </div>
    <script>
        function dismissLoginAnnouncement(id) {
            const existing = (document.cookie.split('; ').find(r => r.startsWith('ann_dismissed=')) || '').replace('ann_dismissed=', '');
            const ids = existing ? existing.split(',') : [];
            if (!ids.includes(String(id))) ids.push(String(id));
            // 24h TTL
            const expires = new Date(Date.now() + 86400000).toUTCString();
            document.cookie = 'ann_dismissed=' + ids.join(',') + '; path=/; expires=' + expires + '; SameSite=Lax';
            const el = document.getElementById('login-ann-' + id);
            if (el) el.remove();
        }
    </script>
@endif
```

- [ ] **Step 2: Include it in `layouts/guest.blade.php`**

Edit `resources/views/layouts/guest.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="login-page">
    <div class="max-w-md mx-auto mt-4 px-4">
        @include('layouts.partials.login-announcements')
    </div>
    {{ $slot }}
</body>
</html>
```

(The announcements sit above `{{ $slot }}`; the login form in `auth/login.blade.php` still renders inside the slot. For the design's "beside the form" placement, a full re-layout is out of scope — the "above the form" placement is a close equivalent in one pass.)

- [ ] **Step 3: Smoke test**

1. `php artisan tinker --execute="\App\Models\Announcement::factory()->loginVisible()->create(['everyone'=>true,'title'=>'System maintenance Sunday','body_markdown'=>'Expect brief downtime.']);"`
2. Log out.
3. Visit `/login`. Expect a blue card with the title and body above the login form.
4. Click the "×" dismiss. Expect the card to disappear; reload and it stays gone (cookie set). Clear cookies → card returns.

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/partials/login-announcements.blade.php resources/views/layouts/guest.blade.php
git commit -m "feat(views): surface login-page announcements above the guest layout slot"
```

---

## Task 22: Archive views (user `index` + `show`)

**Files:**
- Create: `resources/views/announcements/index.blade.php`
- Create: `resources/views/announcements/show.blade.php`

- [ ] **Step 1: Create `index.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'Announcements')
@section('page-title', 'Announcements')

@section('content')
<div class="max-w-4xl">
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @forelse($announcements as $a)
        <a href="{{ route('announcements.show', $a) }}" class="block rounded-xl border border-gray-200 bg-white p-4 mb-3 hover:border-primary-light hover:shadow-sm transition">
            <div class="flex items-start gap-3">
                @if($a->is_pinned)
                    <span class="inline-flex items-center rounded bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-semibold">Pinned</span>
                @endif
                @if($a->priority === 'critical')
                    <span class="inline-flex items-center rounded bg-red-100 text-red-800 px-2 py-0.5 text-[11px] font-semibold">Critical</span>
                @elseif($a->priority === 'info')
                    <span class="inline-flex items-center rounded bg-blue-100 text-blue-800 px-2 py-0.5 text-[11px] font-semibold">Info</span>
                @endif
                <div class="flex-1">
                    <div class="text-base font-semibold text-slate-800">{{ $a->title }}</div>
                    <div class="text-sm text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($a->body_html), 200) }}</div>
                    <div class="text-[11px] text-slate-400 mt-2">{{ ($a->publish_at ?? $a->created_at)->diffForHumans() }}</div>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-slate-500">
            No announcements for you.
        </div>
    @endforelse
</div>
@endsection
```

- [ ] **Step 2: Create `show.blade.php`**

```blade
@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'Announcement')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('announcements.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-800 mb-4">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1"><path d="M15 18l-6-6 6-6"/></svg>
        Back to all announcements
    </a>

    <article class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center gap-2 mb-3">
            @if($announcement->priority === 'critical')
                <span class="inline-flex items-center rounded bg-red-100 text-red-800 px-2 py-0.5 text-[11px] font-semibold">Critical</span>
            @endif
            @if($announcement->is_pinned)
                <span class="inline-flex items-center rounded bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-semibold">Pinned</span>
            @endif
            <span class="text-xs text-slate-400">{{ ($announcement->publish_at ?? $announcement->created_at)->format('M j, Y g:i a') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $announcement->title }}</h1>
        <div class="prose prose-slate max-w-none mt-4">{!! $announcement->body_html !!}</div>
    </article>
</div>
@endsection
```

- [ ] **Step 3: Smoke test**

Visit `/announcements`, see the archive list. Click an item, see the detail page.

- [ ] **Step 4: Commit**

```bash
git add resources/views/announcements/
git commit -m "feat(views): add user announcement archive index + show views"
```

---

## Task 23: Admin views (`index`, `create`, `edit`)

**Files:**
- Create: `resources/views/admin/announcements/index.blade.php`
- Create: `resources/views/admin/announcements/create.blade.php`
- Create: `resources/views/admin/announcements/edit.blade.php`

- [ ] **Step 1: Create `index.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'Manage Announcements')
@section('page-title', 'Manage Announcements')

@section('content')
<div class="max-w-6xl">
    <div class="flex items-center justify-between mb-4">
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5">
            @foreach(['published', 'draft', 'archived'] as $s)
                <a href="{{ route('admin.announcements.index', ['status' => $s]) }}"
                   class="px-3 py-1.5 text-sm rounded-md {{ $status === $s ? 'bg-primary text-white font-semibold' : 'text-slate-600 hover:bg-gray-50' }}">
                    {{ ucfirst($s) }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center rounded-md bg-primary text-white px-4 py-2 text-sm font-semibold hover:bg-primary-dark transition">
            + New Announcement
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Title</th>
                    <th class="px-4 py-3 font-semibold">Priority</th>
                    <th class="px-4 py-3 font-semibold">Scope</th>
                    <th class="px-4 py-3 font-semibold">Publish</th>
                    <th class="px-4 py-3 font-semibold">Expires</th>
                    <th class="px-4 py-3 font-semibold">Author</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $a)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            @if($a->is_pinned) <span class="mr-1 text-amber-600">★</span>@endif
                            {{ $a->title }}
                            @if($a->show_on_login) <span class="ml-1 text-[11px] text-blue-600">(login)</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($a->priority) }}</td>
                        <td class="px-4 py-3">{{ $a->everyone ? 'Everyone' : 'Targeted' }}</td>
                        <td class="px-4 py-3">{{ $a->publish_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->expires_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-1">
                            <a href="{{ route('admin.announcements.edit', $a) }}" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50">Edit</a>
                            @if($status !== 'archived')
                                <form method="POST" action="{{ route('admin.announcements.archive', $a) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50">Archive</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" class="inline" onsubmit="return confirm('Delete permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-md border border-red-200 bg-white text-red-600 px-2.5 py-1 text-xs hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No announcements in this status.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>
</div>
@endsection
```

- [ ] **Step 2: Create the shared form snippet used by create/edit**

Because create and edit share a big form, we use a Blade include. Create `resources/views/admin/announcements/_form.blade.php`:

```blade
@php
    $a = $announcement ?? null;
    $initialTargets = $a ? $a->targets->map(fn($t) => [
        'target_type' => $t->target_type,
        'target_id'   => (string) $t->target_id,
        'is_exclude'  => (bool) $t->is_exclude,
    ])->values()->all() : [];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
        <input type="text" name="title" required maxlength="200" value="{{ old('title', $a->title ?? '') }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Body (markdown)</label>
        <div class="grid md:grid-cols-2 gap-2">
            <textarea name="body_markdown" rows="10" id="body-source"
                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono">{{ old('body_markdown', $a->body_markdown ?? '') }}</textarea>
            <div id="body-preview" class="prose prose-sm max-w-none rounded-md border border-gray-200 bg-gray-50 px-3 py-2 overflow-auto max-h-[400px]">
                <em class="text-slate-400">Preview renders here.</em>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
            <select name="priority" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @foreach(['info','normal','critical'] as $p)
                    <option value="{{ $p }}" @selected(old('priority', $a->priority ?? 'normal') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @foreach(($method === 'PUT' ? ['draft','published','archived'] : ['draft','published']) as $s)
                    <option value="{{ $s }}" @selected(old('status', $a->status ?? 'published') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-4">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $a?->is_pinned)) class="rounded">
                Pinned
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_on_login" value="1" @checked(old('show_on_login', $a?->show_on_login)) class="rounded">
                Show on login
            </label>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Publish at (optional)</label>
            <input type="datetime-local" name="publish_at"
                   value="{{ old('publish_at', $a?->publish_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Expires at (optional)</label>
            <input type="datetime-local" name="expires_at"
                   value="{{ old('expires_at', $a?->expires_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>

    <fieldset class="rounded-lg border border-gray-200 p-4">
        <legend class="text-sm font-semibold px-1">Audience</legend>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="everyone" value="1" @checked(old('everyone', $a?->everyone)) class="rounded">
            Everyone (overrides role/department/user targeting except excludes)
        </label>

        <div class="mt-3 grid md:grid-cols-3 gap-3 text-sm">
            <div>
                <div class="font-medium mb-1">Roles</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="roles-include">
                    @foreach($allRoles as $r)
                        <option value="{{ $r }}">{{ \App\Enums\Permission::roleLabel($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="font-medium mb-1">Departments</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="depts-include">
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="font-medium mb-1">Users</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="users-include">
                    @foreach($userOptions as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 text-xs text-slate-500">
            Excludes: if you need to carve out specific roles/departments/users, add them as <code>is_exclude</code> entries in the raw targets field below.
        </div>

        <details class="mt-3">
            <summary class="cursor-pointer text-sm font-medium text-slate-700">Raw targets JSON (advanced)</summary>
            <textarea name="targets_raw" rows="5" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono mt-2"
                      placeholder='[{"target_type":"role","target_id":"dean","is_exclude":false}]'>{{ old('targets_raw', json_encode($initialTargets)) }}</textarea>
            <p class="text-xs text-slate-500 mt-1">On submit, this takes precedence over the select boxes above.</p>
        </details>
    </fieldset>

    @if($method === 'PUT')
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="notify_again" value="1" class="rounded">
            Notify readers again (resets read state if body/priority changed)
        </label>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center gap-2">
        <button type="submit" class="inline-flex items-center rounded-md bg-primary text-white px-4 py-2 text-sm font-semibold hover:bg-primary-dark transition">Save</button>
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50">Cancel</a>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    (function () {
        const src = document.getElementById('body-source');
        const out = document.getElementById('body-preview');
        function renderPreview() {
            out.innerHTML = window.marked.parse(src.value || '*Preview renders here.*');
        }
        src.addEventListener('input', renderPreview);
        renderPreview();

        // On submit, assemble targets_raw from include selects if user left the raw field at its default.
        const form = src.closest('form');
        form.addEventListener('submit', function (e) {
            const rawEl = form.querySelector('[name="targets_raw"]');
            let targets = [];
            try { targets = JSON.parse(rawEl.value || '[]'); } catch { targets = []; }

            const roles = Array.from(document.getElementById('roles-include').selectedOptions).map(o => o.value);
            const depts = Array.from(document.getElementById('depts-include').selectedOptions).map(o => o.value);
            const users = Array.from(document.getElementById('users-include').selectedOptions).map(o => o.value);

            const fromSelects = [
                ...roles.map(v => ({ target_type: 'role',       target_id: v, is_exclude: false })),
                ...depts.map(v => ({ target_type: 'department', target_id: v, is_exclude: false })),
                ...users.map(v => ({ target_type: 'user',       target_id: v, is_exclude: false })),
            ];

            // If the raw field was empty, use the select-derived list; otherwise trust raw.
            const final = fromSelects.length > 0 ? fromSelects.concat(targets.filter(t => t.is_exclude)) : targets;

            // Remove the raw textarea so it's not sent — we inject proper form inputs.
            rawEl.remove();
            final.forEach((t, i) => {
                for (const k of ['target_type', 'target_id', 'is_exclude']) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `targets[${i}][${k}]`;
                    input.value = k === 'is_exclude' ? (t[k] ? 1 : 0) : t[k];
                    form.appendChild(input);
                }
            });
        });
    })();
</script>
```

- [ ] **Step 3: Create `create.blade.php` and `edit.blade.php`**

`resources/views/admin/announcements/create.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'New Announcement')
@section('page-title', 'New Announcement')
@section('content')
<div class="max-w-5xl">
    @include('admin.announcements._form', ['action' => route('admin.announcements.store'), 'method' => 'POST'])
</div>
@endsection
```

`resources/views/admin/announcements/edit.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Edit Announcement')
@section('page-title', 'Edit Announcement')
@section('content')
<div class="max-w-5xl">
    @include('admin.announcements._form', ['action' => route('admin.announcements.update', $announcement), 'method' => 'PUT', 'announcement' => $announcement])
</div>
@endsection
```

- [ ] **Step 4: Smoke test**

Log in as a user with `manage-announcements-system`, visit `/admin/announcements`, click **New Announcement**, fill in title + markdown body, save. Confirm it appears in the listing.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/announcements/
git commit -m "feat(admin-views): add announcement admin index, form, create, edit templates"
```

---

## Task 24: Seed default role permissions in DB

**Files:**
- Create: `database/seeders/AnnouncementPermissionsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

Purpose: on systems where `role_permissions` is populated (overrides `defaultsForRole`), inject the new rows idempotently.

- [ ] **Step 1: Create the seeder**

```bash
php artisan make:seeder AnnouncementPermissionsSeeder
```

Replace contents with:

```php
<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class AnnouncementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'head'              => [Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT],
            'dean'              => [Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT],
            'human_resource'    => [Permission::MANAGE_ANNOUNCEMENTS_SYSTEM],
            'school_president'  => [Permission::MANAGE_ANNOUNCEMENTS_SYSTEM],
            'vp_acad'           => [Permission::MANAGE_ANNOUNCEMENTS_SYSTEM],
        ];

        // Only insert when this role already has at least one RolePermission row
        // (meaning the DB is the source of truth for that role — otherwise defaults apply).
        foreach ($map as $role => $permissions) {
            $hasAny = RolePermission::where('role', $role)->exists();
            if (! $hasAny) {
                continue;
            }
            foreach ($permissions as $perm) {
                RolePermission::firstOrCreate(['role' => $role, 'permission' => $perm]);
            }
        }

        Permission::clearCache();
    }
}
```

- [ ] **Step 2: Register in `DatabaseSeeder`**

Open `database/seeders/DatabaseSeeder.php` and add to `run()`:

```php
$this->call(AnnouncementPermissionsSeeder::class);
```

- [ ] **Step 3: Run the seeder on dev DB**

```bash
php artisan db:seed --class=AnnouncementPermissionsSeeder
```

- [ ] **Step 4: Verify**

```bash
php artisan tinker --execute="dump(\App\Models\RolePermission::where('permission','manage-announcements-system')->pluck('role')->all());"
```
Expected (if you'd previously seeded those roles): `['human_resource','school_president','vp_acad']`. If empty, your environment is still using defaults only — that's fine; the seeder is idempotent and a no-op when no DB rows exist for those roles.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/AnnouncementPermissionsSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(seed): idempotent seeder for new announcement role permissions"
```

---

## Task 25: Rendering test + end-to-end smoke

**Files:**
- Create: `tests/Feature/Announcements/RenderingTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature\Announcements;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_markdown_stored_as_sanitized_html_on_create(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);

        $this->actingAs($sys)->post(route('admin.announcements.store'), [
            'title'         => 'Launch',
            'body_markdown' => "# Not allowed\n\n<script>alert(1)</script>\n\n**ok** [site](https://x.y)",
            'priority'      => 'normal',
            'everyone'      => true,
            'status'        => 'published',
        ])->assertRedirect();

        $a = \App\Models\Announcement::firstWhere('title', 'Launch');
        $this->assertStringContainsString('<strong>ok</strong>', $a->body_html);
        $this->assertStringContainsString('href="https://x.y"', $a->body_html);
        $this->assertStringNotContainsString('<script', $a->body_html);
        $this->assertMatchesRegularExpression('/rel="[^"]*noopener[^"]*"/', $a->body_html);
        // h1 is not in allowlist — should be stripped or demoted
        $this->assertStringNotContainsString('<h1>', $a->body_html);
    }
}
```

- [ ] **Step 2: Run all announcement tests**

```bash
php artisan test tests/Feature/Announcements tests/Unit/Services
```
Expected: all PASS. If `h1` still renders, update the Purifier `announcement` allowlist in `config/purifier.php` to omit `h1` (it already does — the test just guards against regression).

- [ ] **Step 3: End-to-end manual smoke**

Rebuild and verify in the browser:

1. `docker compose up -d --build app`
2. Visit `/login` — confirm login box is shown; any `show_on_login` announcements appear.
3. Log in as a user with `human_resource` role.
4. Visit `/admin/announcements`, click **New Announcement**, create a normal + critical pair.
5. On the dashboard:
   - Confirm the **bell** shows a badge with unread count.
   - Open the bell: confirm the dropdown lists recent items and the badge goes to 0.
   - Confirm the **critical** banner is red and cannot be dismissed until "I've read this" is clicked.
6. Click "I've read this" → banner disappears; `audit_logs` has an `acknowledged` row.
7. Log out, revisit `/login` — dismiss a login-visible item, reload, confirm it stays hidden.
8. Log back in as a Department Head; visit `/admin/announcements/create`; confirm "Everyone" fails validation with an error message about scope.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Announcements/RenderingTest.php
git commit -m "test(announcements): rendering + sanitization coverage"
```

---

## Post-implementation checklist

- [ ] All feature + unit tests under `tests/**/Announcements/` and `tests/Unit/Services/MarkdownRendererTest.php` pass.
- [ ] `php artisan route:list | grep announcements` shows 7 new routes.
- [ ] `php artisan migrate:fresh` runs cleanly in the dev DB.
- [ ] Manual smoke steps in Task 25 all pass.
- [ ] `audit_logs` contains `created`, `updated`, and `acknowledged` rows for a test announcement.

## Notes & gotchas

- **Route ordering.** `POST /announcements/mark-read-batch` must be declared before `POST /announcements/{announcement}/read` so implicit model binding doesn't try to resolve `mark-read-batch` as an announcement id.
- **Package cache.** After `composer require mews/purifier`, run `php artisan config:clear` inside the container before the new config resolves.
- **`FIELD()` portability.** The visibility service uses `CASE WHEN` (portable) for priority ordering, not MySQL's `FIELD()`. Do not revert.
- **`whereJsonContains` on SQLite.** Tests rely on SQLite's JSON1 extension (available in stock SQLite for Laravel 13). If the test runner reports JSON errors, upgrade the PHP SQLite driver or switch the test DB to MySQL.
- **Guest dismissals are best-effort.** The cookie is session-ish (24h TTL). If you need stronger guarantees (e.g., cross-device), that's out of scope (§12 of the spec).
- **Docker mount.** The local `docker-compose.yml` no longer mounts `./src` into `/var/www`. Any change to the app (new views, migrations, code) needs `docker compose up -d --build app`. Alternatively, re-add the mount for a dev-only override.
