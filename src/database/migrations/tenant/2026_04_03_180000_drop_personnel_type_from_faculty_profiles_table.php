<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('faculty_profiles', 'personnel_type')) {
            return;
        }

        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->dropColumn('personnel_type');
        });
    }

    public function down(): void
    {
        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->string('personnel_type', 32)->default('teaching')->after('department_position');
        });
    }
};
