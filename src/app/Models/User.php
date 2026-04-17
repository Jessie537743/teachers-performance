<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        // Legacy DBs still have a single `role` enum; app code expects `roles` JSON array.
        // syncOriginal() so the virtual `roles` attribute is not dirty — otherwise save() would
        // try to UPDATE a non-existent `roles` column (e.g. change password).
        static::retrieved(function (User $user): void {
            if (Schema::hasColumn($user->getTable(), 'roles')) {
                return;
            }
            $attrs = $user->getAttributes();
            if (array_key_exists('role', $attrs)) {
                $user->setAttribute('roles', [$attrs['role']]);
                $user->syncOriginal();
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'roles',
        'is_active',
        'department_id',
        'must_change_password',
        'date_of_birth',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
            'date_of_birth'        => 'date',
            'roles'                => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function facultyProfile(): HasOne
    {
        return $this->hasOne(FacultyProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    // -------------------------------------------------------------------------
    // Backward-compatible accessor
    // -------------------------------------------------------------------------

    public function getRoleAttribute(): string
    {
        return $this->primaryRole();
    }

    // -------------------------------------------------------------------------
    // Multi-role helpers
    // -------------------------------------------------------------------------

    /**
     * Return the highest-priority role this user holds.
     */
    public function primaryRole(): string
    {
        $priority = [
            'admin',
            'school_president',
            'vp_acad',
            'vp_admin',
            'dean',
            'head',
            'human_resource',
            'faculty',
            'staff',
            'student',
        ];

        $roles = $this->roles ?? [];

        foreach ($priority as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'student';
    }

    // -------------------------------------------------------------------------
    // Role check helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return in_array('admin', $this->roles ?? [], true);
    }

    public function isDean(): bool
    {
        return in_array('dean', $this->roles ?? [], true);
    }

    public function isFaculty(): bool
    {
        return in_array('faculty', $this->roles ?? [], true);
    }

    public function isStudent(): bool
    {
        return in_array('student', $this->roles ?? [], true);
    }

    public function isSchoolPresident(): bool
    {
        return in_array('school_president', $this->roles ?? [], true);
    }

    public function isVpAcad(): bool
    {
        return in_array('vp_acad', $this->roles ?? [], true);
    }

    public function isVpAdmin(): bool
    {
        return in_array('vp_admin', $this->roles ?? [], true);
    }

    public function isHumanResource(): bool
    {
        return in_array('human_resource', $this->roles ?? [], true);
    }

    public function isHead(): bool
    {
        return in_array('head', $this->roles ?? [], true);
    }

    public function isStaff(): bool
    {
        return in_array('staff', $this->roles ?? [], true);
    }

    public function hasRole(string|array $roles): bool
    {
        return count(array_intersect($this->roles ?? [], (array) $roles)) > 0;
    }

    // -------------------------------------------------------------------------
    // Permission helpers
    // -------------------------------------------------------------------------

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function permissions(): array
    {
        return array_values(array_unique(array_merge(
            Permission::forRoles($this->roles ?? []),
            $this->delegatedPermissions()
        )));
    }

    /**
     * Permissions granted via active delegations (where this user is the delegatee).
     */
    public function delegatedPermissions(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "delegated_perms:user:{$this->id}",
            60,
            function () {
                return PermissionDelegation::active()
                    ->where('delegatee_id', $this->id)
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->unique()
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * Clear the delegated permissions cache for this user.
     */
    public function clearDelegatedPermissionsCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("delegated_perms:user:{$this->id}");
    }

    // -------------------------------------------------------------------------
    // Scope
    // -------------------------------------------------------------------------

    /**
     * Scope to users who hold at least one of the given roles.
     */
    public function scopeWhereHasRole($query, string|array $roles)
    {
        $roles = (array) $roles;

        if (Schema::hasColumn((new static)->getTable(), 'roles')) {
            if (count($roles) === 1) {
                return $query->whereJsonContains('roles', $roles[0]);
            }

            return $query->where(function ($q) use ($roles) {
                foreach ($roles as $role) {
                    $q->orWhereJsonContains('roles', $role);
                }
            });
        }

        if (count($roles) === 1) {
            return $query->where('role', $roles[0]);
        }

        return $query->whereIn('role', $roles);
    }

    // -------------------------------------------------------------------------
    // Role mutation helpers
    // -------------------------------------------------------------------------

    /**
     * Append a role if the user does not already have it.
     */
    public function addRole(string $role): void
    {
        $roles = $this->roles ?? [];

        if (! in_array($role, $roles, true)) {
            $roles[] = $role;
            $this->roles = $roles;
            $this->save();
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $role): void
    {
        $roles = $this->roles ?? [];
        $roles = array_values(array_filter($roles, fn ($r) => $r !== $role));
        $this->roles = $roles;
        $this->save();
    }

    // -------------------------------------------------------------------------
    // Display helpers
    // -------------------------------------------------------------------------

    /**
     * Comma-separated human-readable role labels.
     */
    public function rolesLabel(): string
    {
        return collect($this->roles ?? [])
            ->map(fn ($role) => Permission::roleLabel($role))
            ->implode(', ');
    }
}
