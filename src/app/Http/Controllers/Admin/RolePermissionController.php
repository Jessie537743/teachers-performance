<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\Permission;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-roles');

        $roles           = Permission::allRoles();
        $permissionGroups = Permission::allPermissions();

        // Build the current permission set for every role.
        // Prefer DB rows; fall back to hardcoded defaults when the table is empty
        // for a given role (e.g. before the seeder has run).
        $rolePermissions = [];
        foreach ($roles as $role) {
            $dbPermissions = RolePermission::where('role', $role)
                ->pluck('permission')
                ->toArray();

            $rolePermissions[$role] = !empty($dbPermissions)
                ? $dbPermissions
                : Permission::defaultsForRole($role);
        }

        return view('roles.index', compact('roles', 'permissionGroups', 'rolePermissions'));
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-roles');

        $role        = $request->input('role');
        $permissions = $request->input('permissions', []);

        if (!in_array($role, Permission::allRoles())) {
            return back()->with('error', 'Invalid role.');
        }

        $allValidPermissions = [];
        foreach (Permission::allPermissions() as $group) {
            $allValidPermissions = array_merge($allValidPermissions, array_keys($group));
        }
        $permissions = array_values(array_intersect($permissions, $allValidPermissions));

        // Replace every permission row for this role atomically
        RolePermission::where('role', $role)->delete();

        foreach ($permissions as $permission) {
            RolePermission::create([
                'role'       => $role,
                'permission' => $permission,
            ]);
        }

        Permission::clearCache($role);

        return back()->with('success', 'Permissions for ' . Permission::roleLabel($role) . ' updated successfully.');
    }

    public function reset(): RedirectResponse
    {
        Gate::authorize('manage-roles');

        RolePermission::truncate();

        foreach (Permission::allRoles() as $role) {
            foreach (Permission::defaultsForRole($role) as $permission) {
                RolePermission::create([
                    'role'       => $role,
                    'permission' => $permission,
                ]);
            }
        }

        Permission::clearCache();

        return back()->with('success', 'All role permissions have been reset to defaults.');
    }
}
