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
        $this->guardActiveTenant();
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

    /**
     * Refuse to run if tenancy isn't initialized or the active connection
     * still points at a known central/legacy database — the truncate step
     * below would otherwise destroy production tenant data.
     */
    private function guardActiveTenant(): void
    {
        if (! function_exists('tenant') || tenant() === null) {
            throw new \RuntimeException(
                'TenantTemplateSeeder must run inside an initialized tenancy context. '
                . 'Invoke via `php artisan tenants:seed --tenants=<id>` or from a job '
                . 'that calls tenancy()->initialize($tenant) first.'
            );
        }

        $activeDb = DB::connection()->getDatabaseName();
        $reserved = ['central', 'mysql', 'information_schema', 'performance_schema', 'sys'];

        if (in_array($activeDb, $reserved, true)) {
            throw new \RuntimeException(
                "TenantTemplateSeeder refusing to run against reserved DB '{$activeDb}'. "
                . 'Verify the tenancy connection actually swapped before seeding.'
            );
        }

        $expectedDb = tenant()->getAttribute('database');
        if ($activeDb !== $expectedDb) {
            throw new \RuntimeException(
                "Active DB '{$activeDb}' does not match active tenant DB '{$expectedDb}'. "
                . 'The connection swap did not take effect — refusing to truncate.'
            );
        }
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
