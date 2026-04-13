<?php

namespace App\Enums;

use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

class Permission
{
    // Dashboard
    const VIEW_ADMIN_DASHBOARD = 'view-admin-dashboard';
    const VIEW_DEAN_DASHBOARD = 'view-dean-dashboard';
    const VIEW_STUDENT_DASHBOARD = 'view-student-dashboard';
    const VIEW_FACULTY_DASHBOARD = 'view-faculty-dashboard';
    const VIEW_HR_DASHBOARD = 'view-hr-dashboard';

    // Analytics
    const VIEW_ANALYTICS = 'view-analytics';

    // Reports
    const GENERATE_REPORT = 'generate-report';
    const VIEW_GENERATED_REPORT = 'view-generated-report';
    const PRINT_OR_GENERATE_COMMENT = 'print-or-generate-comment';

    // Evaluation Submissions
    const SUBMIT_STUDENT_EVALUATION = 'submit-student-evaluation';
    const SUBMIT_DEAN_EVALUATION = 'submit-dean-evaluation';
    const SUBMIT_SELF_EVALUATION = 'submit-self-evaluation';
    const SUBMIT_PEER_EVALUATION = 'submit-peer-evaluation';

    // Evaluation Management
    const MANAGE_CRITERIA = 'manage-criteria';
    const MANAGE_EVALUATION_PERIODS = 'manage-evaluation-periods';
    const MONITOR_NOT_EVALUATED = 'monitor-not-evaluated';

    // Academic
    const MANAGE_COURSES = 'manage-courses';
    const MANAGE_SUBJECTS = 'manage-subjects';

    // People
    const MANAGE_DEPARTMENTS = 'manage-departments';
    const MANAGE_FACULTY = 'manage-faculty';
    const MANAGE_STUDENTS = 'manage-students';

    // Settings
    const MANAGE_SETTINGS = 'manage-settings';
    const MANAGE_ROLES = 'manage-roles';
    const VIEW_USERS = 'view-users';

    // All available roles in the system
    public static function allRoles(): array
    {
        return [
            'admin', 'dean', 'head', 'faculty', 'student',
            'school_president', 'vp_acad', 'vp_admin',
            'human_resource', 'staff',
        ];
    }

    // All available permissions with human-readable labels grouped by module
    public static function allPermissions(): array
    {
        return [
            'Dashboard' => [
                self::VIEW_ADMIN_DASHBOARD   => 'View Admin Dashboard',
                self::VIEW_DEAN_DASHBOARD    => 'View Dean Dashboard',
                self::VIEW_STUDENT_DASHBOARD => 'View Student Dashboard',
                self::VIEW_FACULTY_DASHBOARD => 'View Faculty Dashboard',
                self::VIEW_HR_DASHBOARD      => 'View HR Dashboard',
            ],
            'Analytics' => [
                self::VIEW_ANALYTICS => 'View Analytics',
            ],
            'Reports' => [
                self::GENERATE_REPORT           => 'Generate Report',
                self::VIEW_GENERATED_REPORT     => 'View Generated Report',
                self::PRINT_OR_GENERATE_COMMENT => 'Print or Generate Comment',
            ],
            'Evaluations' => [
                self::SUBMIT_STUDENT_EVALUATION => 'Submit Student Evaluation',
                self::SUBMIT_DEAN_EVALUATION    => 'Submit Dean Evaluation',
                self::SUBMIT_SELF_EVALUATION    => 'Submit Self Evaluation',
                self::SUBMIT_PEER_EVALUATION    => 'Submit Peer Evaluation',
            ],
            'Evaluation Management' => [
                self::MANAGE_CRITERIA           => 'Manage Criteria',
                self::MANAGE_EVALUATION_PERIODS => 'Manage Evaluation Periods',
                self::MONITOR_NOT_EVALUATED     => 'Monitor faculty & student evaluation compliance (institution-wide for HR)',
            ],
            'Academic' => [
                self::MANAGE_COURSES  => 'Manage Courses',
                self::MANAGE_SUBJECTS => 'Manage Subjects',
            ],
            'People' => [
                self::MANAGE_DEPARTMENTS => 'Manage Departments',
                self::MANAGE_FACULTY     => 'Manage Faculty',
                self::MANAGE_STUDENTS    => 'Manage Students',
            ],
            'Settings' => [
                self::MANAGE_SETTINGS => 'Manage Settings',
                self::MANAGE_ROLES    => 'Manage Roles & Permissions',
                self::VIEW_USERS      => 'View All Users',
            ],
        ];
    }

    // Human-readable role labels
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin'          => 'Administrator',
            'dean'           => 'Dean',
            'head'           => 'Department Head',
            'faculty'        => 'Faculty',
            'student'        => 'Student',
            'school_president' => 'School President',
            'vp_acad'        => 'VP Academic',
            'vp_admin'       => 'VP Admin',
            'human_resource' => 'Human Resource',
            'staff'          => 'Staff',
            default          => ucfirst($role),
        };
    }

    // Get permissions for a role - database first, then fallback to hardcoded defaults
    public static function forRole(string $role): array
    {
        return Cache::remember("role_permissions:{$role}", 300, function () use ($role) {
            $dbPermissions = RolePermission::where('role', $role)->pluck('permission')->toArray();

            if (!empty($dbPermissions)) {
                return $dbPermissions;
            }

            return self::defaultsForRole($role);
        });
    }

    // The original hardcoded defaults (used for initial seeding and fallback)
    public static function defaultsForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                self::VIEW_ADMIN_DASHBOARD,
                self::VIEW_ANALYTICS,
                self::GENERATE_REPORT,
                self::VIEW_GENERATED_REPORT,
                self::MANAGE_DEPARTMENTS,
                self::MANAGE_FACULTY,
                self::MANAGE_STUDENTS,
                self::MANAGE_COURSES,
                self::MANAGE_SUBJECTS,
                self::MANAGE_CRITERIA,
                self::MANAGE_EVALUATION_PERIODS,
                self::MANAGE_SETTINGS,
                self::MANAGE_ROLES,
                self::VIEW_USERS,
                self::PRINT_OR_GENERATE_COMMENT,
                self::MONITOR_NOT_EVALUATED,
            ],
            'dean', 'head' => [
                self::VIEW_DEAN_DASHBOARD,
                self::VIEW_ANALYTICS,
                self::GENERATE_REPORT,
                self::VIEW_GENERATED_REPORT,
                self::SUBMIT_DEAN_EVALUATION,
                self::MONITOR_NOT_EVALUATED,
            ],
            'faculty' => [
                self::VIEW_FACULTY_DASHBOARD,
                self::SUBMIT_SELF_EVALUATION,
                self::SUBMIT_PEER_EVALUATION,
            ],
            'student' => [
                self::VIEW_STUDENT_DASHBOARD,
                self::SUBMIT_STUDENT_EVALUATION,
            ],
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
            ],
            'school_president' => [
                self::VIEW_ADMIN_DASHBOARD,
                self::VIEW_ANALYTICS,
                self::GENERATE_REPORT,
                self::VIEW_GENERATED_REPORT,
                self::SUBMIT_DEAN_EVALUATION,
            ],
            'vp_admin' => [
                self::VIEW_ADMIN_DASHBOARD,
                self::VIEW_ANALYTICS,
                self::GENERATE_REPORT,
                self::VIEW_GENERATED_REPORT,
            ],
            'vp_acad' => [
                self::VIEW_ADMIN_DASHBOARD,
                self::VIEW_ANALYTICS,
                self::GENERATE_REPORT,
                self::VIEW_GENERATED_REPORT,
                self::SUBMIT_DEAN_EVALUATION,
            ],
            'staff' => [
                self::VIEW_HR_DASHBOARD,
            ],
            default => [],
        };
    }

    // Clear cached permissions (call after updates)
    public static function clearCache(?string $role = null): void
    {
        if ($role) {
            Cache::forget("role_permissions:{$role}");
        } else {
            foreach (self::allRoles() as $r) {
                Cache::forget("role_permissions:{$r}");
            }
        }
    }
}
