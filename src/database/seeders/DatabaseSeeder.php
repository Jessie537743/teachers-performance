<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            DefaultUserSeeder::class,       // admin only
            RolePermissionSeeder::class,    // seed default role permissions into DB
            FacultySeeder::class,           // deans + faculty + faculty_profiles
            StudentSeeder::class,           // students + student_profiles
            CourseSeeder::class,            // programs from courses.sql (all colleges)
            SubjectSeeder::class,           // subjects (writes subject_id_map.json)
            SubjectAssignmentSeeder::class, // subject -> faculty links (reads both maps)
            CriteriaSeeder::class,
            QuestionSeeder::class,
            DeanRecommendationCriterionSeeder::class,
            AcademicAdministratorsCriteriaSeeder::class,
            InterventionSeeder::class,
            // SampleEvaluationDataSeeder::class, // optional: php artisan db:seed --class=Database\\Seeders\\SampleEvaluationDataSeeder
        ]);
    }
}
