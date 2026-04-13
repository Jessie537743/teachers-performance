<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Course;
use App\Models\Criterion;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Models\PermissionDelegation;
use App\Models\RolePermission;
use App\Models\SentimentLexicon;
use App\Models\Setting;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use App\Observers\AuditObserver;
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

        // Register audit observer on key models
        $auditableModels = [
            User::class, Department::class, Course::class, Subject::class,
            FacultyProfile::class, StudentProfile::class, EvaluationPeriod::class,
            Criterion::class, SentimentLexicon::class, PermissionDelegation::class,
            RolePermission::class, Setting::class,
        ];
        foreach ($auditableModels as $model) {
            $model::observe(AuditObserver::class);
        }

        // Register all permission gates derived from Permission constants
        $permissions = (new \ReflectionClass(Permission::class))->getConstants();
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                Gate::define($permission, function ($user) use ($permission) {
                    return in_array($permission, Permission::forRoles($user->roles ?? []));
                });
            }
        }

        // Super admin bypass — admin role passes every Gate check
        Gate::before(function ($user, $ability) {
            if (in_array('admin', $user->roles ?? [], true)) {
                return true;
            }
        });

        // Explicit policy registrations (supplements auto-discovery)
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(FacultyProfile::class, FacultyProfilePolicy::class);
    }
}
