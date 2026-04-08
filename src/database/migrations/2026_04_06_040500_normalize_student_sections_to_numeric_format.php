<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE student_profiles
            SET section = 'Section 1'
            WHERE UPPER(TRIM(section)) = 'A'
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE student_profiles
            SET section = 'A'
            WHERE UPPER(TRIM(section)) = 'SECTION 1'
        ");
    }
};
