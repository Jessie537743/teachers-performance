<?php

use App\Models\Criterion;
use Database\Seeders\AcademicAdministratorsCriteriaSeeder;
use Database\Seeders\DeanRecommendationCriterionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Removes legacy mirrored dean_head_* tags (which duplicated classroom criteria for deans).
     * If criteria already exist (existing database), seeds "EVALUATION FOR ACADEMIC ADMINISTRATORS".
     * Fresh installs with empty DB skip auto-seed here; run DatabaseSeeder which includes the same seeder.
     */
    public function up(): void
    {
        if (! Schema::hasTable('criterion_personnel_types')) {
            return;
        }

        DB::table('criterion_personnel_types')
            ->whereIn('personnel_type', ['dean_head_teaching', 'dean_head_non_teaching'])
            ->delete();

        if (Schema::hasTable('criteria') && Criterion::query()->exists()) {
            Artisan::call('db:seed', [
                '--class' => AcademicAdministratorsCriteriaSeeder::class,
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => DeanRecommendationCriterionSeeder::class,
                '--force' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Cannot restore removed pivots; re-run DeanHeadCriterionPersonnelSeeder::syncAll if needed.
    }
};
