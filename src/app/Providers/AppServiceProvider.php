<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Policies\DepartmentPolicy;
use App\Policies\FacultyProfilePolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.simple');

        // Register all permission gates derived from Permission constants
        $permissions = (new \ReflectionClass(Permission::class))->getConstants();
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                Gate::define($permission, function ($user) use ($permission) {
                    return in_array($permission, Permission::forRole($user->role));
                });
            }
        }

        // Super admin bypass — admin role passes every Gate check
        Gate::before(function ($user, $ability) {
            if ($user->role === 'admin') {
                return true;
            }
        });

        // Explicit policy registrations (supplements auto-discovery)
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(FacultyProfile::class, FacultyProfilePolicy::class);
    }
}
