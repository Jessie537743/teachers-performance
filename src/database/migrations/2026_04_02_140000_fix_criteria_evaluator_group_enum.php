<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily allow both values, normalize data, then tighten enum.
        DB::statement("ALTER TABLE criteria MODIFY COLUMN evaluator_group ENUM('student','dean_head','dean','self','peer') NOT NULL DEFAULT 'student'");
        DB::statement("UPDATE criteria SET evaluator_group = 'dean' WHERE evaluator_group = 'dean_head'");
        DB::statement("ALTER TABLE criteria MODIFY COLUMN evaluator_group ENUM('student','dean','self','peer') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE criteria MODIFY COLUMN evaluator_group ENUM('student','dean_head','dean','self','peer') NOT NULL DEFAULT 'student'");
        DB::statement("UPDATE criteria SET evaluator_group = 'dean_head' WHERE evaluator_group = 'dean'");
        DB::statement("ALTER TABLE criteria MODIFY COLUMN evaluator_group ENUM('student','dean_head','self','peer') NOT NULL DEFAULT 'student'");
    }
};
