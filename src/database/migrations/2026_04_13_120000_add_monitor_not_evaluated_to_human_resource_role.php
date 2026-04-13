<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('role_permissions')
            ->where('role', 'human_resource')
            ->where('permission', 'monitor-not-evaluated')
            ->exists();

        if (! $exists) {
            DB::table('role_permissions')->insert([
                'role'         => 'human_resource',
                'permission'   => 'monitor-not-evaluated',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('role', 'human_resource')
            ->where('permission', 'monitor-not-evaluated')
            ->delete();
    }
};
