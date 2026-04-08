<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE student_profiles
            SET section = TRIM(SUBSTRING(section, 8))
            WHERE LOWER(TRIM(section)) LIKE 'section %'
        ");

        DB::statement("
            UPDATE student_profiles
            SET section = CAST(ASCII(UPPER(TRIM(section))) - ASCII('A') + 1 AS CHAR)
            WHERE TRIM(section) REGEXP '^[A-Za-z]$'
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE student_profiles
            SET section = CONCAT('Section ', TRIM(section))
            WHERE TRIM(section) REGEXP '^[0-9]+$'
        ");
    }
};
