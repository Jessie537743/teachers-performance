<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs against a freshly provisioned tenant DB to seed the system primitives
 * a school needs to function. Per-school data (departments, faculty, students,
 * subjects) is intentionally NOT seeded — the school admin populates those
 * after first login.
 *
 * Some Phase-1 data migrations partially pre-seed criteria, questions, and
 * role_permissions; the seeders below assume an empty starting state and
 * use hardcoded primary keys, so we truncate first to get clean rows.
 */
class TenantTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetConflictingTables();

        $this->call([
            RolePermissionSeeder::class,
            AnnouncementPermissionsSeeder::class,
            CriteriaSeeder::class,
            QuestionSeeder::class,
            DeanRecommendationCriterionSeeder::class,
            AcademicAdministratorsCriteriaSeeder::class,
            InterventionSeeder::class,
            SentimentLexiconSeeder::class,
        ]);
    }

    private function resetConflictingTables(): void
    {
        $tables = [
            'criterion_evaluator_groups',
            'criterion_personnel_types',
            'questions',
            'criteria',
            'role_permissions',
            'sentiment_lexicon',
            'interventions',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
