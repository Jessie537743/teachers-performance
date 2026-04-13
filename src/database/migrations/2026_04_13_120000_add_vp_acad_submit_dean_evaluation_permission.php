<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('role_permissions')
            ->where('role', 'vp_acad')
            ->where('permission', Permission::SUBMIT_DEAN_EVALUATION)
            ->exists();

        if (! $exists) {
            DB::table('role_permissions')->insert([
                'role'         => 'vp_acad',
                'permission'   => Permission::SUBMIT_DEAN_EVALUATION,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        Permission::clearCache('vp_acad');
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('role', 'vp_acad')
            ->where('permission', Permission::SUBMIT_DEAN_EVALUATION)
            ->delete();

        Permission::clearCache('vp_acad');
    }
};
