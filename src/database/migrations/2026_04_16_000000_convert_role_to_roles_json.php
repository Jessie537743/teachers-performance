<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('role');
        });

        DB::statement('UPDATE users SET roles = JSON_ARRAY(role)');

        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->default('["student"]')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
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
