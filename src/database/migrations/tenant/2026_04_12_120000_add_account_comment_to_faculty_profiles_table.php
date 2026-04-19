<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->text('account_comment')->nullable()->after('department_position');
        });
    }

    public function down(): void
    {
        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->dropColumn('account_comment');
        });
    }
};
