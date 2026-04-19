<?php

use Database\Seeders\DeanHeadCriterionPersonnelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DeanHeadCriterionPersonnelSeeder::syncAll();
    }

    public function down(): void
    {
        if (! Schema::hasTable('criterion_personnel_types')) {
            return;
        }

        DB::table('criterion_personnel_types')
            ->whereIn('personnel_type', ['dean_head_teaching', 'dean_head_non_teaching'])
            ->delete();
    }
};
