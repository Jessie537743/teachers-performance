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

        // Only insert when this role already has RolePermission rows (i.e. DB is overriding
        // Permission::defaultsForRole). Otherwise defaults apply and seeding is unnecessary.
        foreach ($map as $role => $permissions) {
            if (! RolePermission::where('role', $role)->exists()) {
                continue;
            }
            foreach ($permissions as $perm) {
                RolePermission::firstOrCreate(['role' => $role, 'permission' => $perm]);
            }
        }

        Permission::clearCache();
    }
}
