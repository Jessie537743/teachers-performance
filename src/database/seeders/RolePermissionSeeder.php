<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        RolePermission::truncate();

        foreach (Permission::allRoles() as $role) {
            $permissions = Permission::defaultsForRole($role);
            foreach ($permissions as $permission) {
                RolePermission::create([
                    'role'       => $role,
                    'permission' => $permission,
                ]);
            }
        }

        $this->command->info('Role permissions seeded: ' . RolePermission::count() . ' entries');
    }
}
