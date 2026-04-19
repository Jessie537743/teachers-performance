<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs against a freshly provisioned tenant DB to seed the system primitives
 * a school needs to function. Per-school data (departments, faculty, students,
 * subjects) is intentionally NOT seeded — the school admin populates those
 * after first login.
 */
class TenantTemplateSeeder extends Seeder
{
    public function run(): void
    {
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
}
