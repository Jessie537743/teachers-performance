<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add roles JSON column if it doesn't exist yet
        if (! Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('roles')->nullable()->after(
                    Schema::hasColumn('users', 'role') ? 'role' : 'remember_token'
                );
            });
        }

        // Step 2: Populate from old role column (if it still exists)
        if (Schema::hasColumn('users', 'role')) {
            DB::statement('UPDATE users SET roles = JSON_ARRAY(role) WHERE roles IS NULL');
        }

        // Step 3: Set NOT NULL + default via raw SQL (avoids ->change() issues)
        DB::statement("ALTER TABLE users MODIFY COLUMN roles JSON NOT NULL DEFAULT (JSON_ARRAY('student'))");

        // Step 4: Drop old role column if it exists
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'admin',
                'student',
                'faculty',
                'dean',
                'school_president',
                'vp_acad',
                'vp_admin',
                'human_resource',
                'head',
                'staff',
            ])->default('student')->after('password');
        });

        DB::statement("UPDATE users SET role = JSON_UNQUOTE(JSON_EXTRACT(roles, '$[0]'))");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
