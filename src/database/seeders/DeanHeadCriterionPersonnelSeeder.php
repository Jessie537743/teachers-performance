<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Replaced by AcademicAdministratorsCriteriaSeeder. Older migrations may still
 *             call syncAll(); it is intentionally a no-op.
 */
class DeanHeadCriterionPersonnelSeeder extends Seeder
{
    public function run(): void
    {
        self::syncAll();
    }

    public static function syncAll(): void
    {
        // Dean/Head evaluatees use "EVALUATION FOR ACADEMIC ADMINISTRATORS" (see AcademicAdministratorsCriteriaSeeder).
    }
}
