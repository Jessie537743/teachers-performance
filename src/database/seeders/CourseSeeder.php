<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Programs from courses.sql (phpMyAdmin dump). Dump department_id values are legacy
 * FKs (1=CCIS, 4=CTE, 5=CCJE, 6=CAS, 7=CBMA, 8=CTHM); they are resolved to current
 * departments.id via code. BSBA — MM was stored under dept 6 in the dump but is
 * placed under CBMA to match the program.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding courses from courses.sql dataset...');

        $deptIds = Department::query()->pluck('id', 'code')->all();

        $rows = [
            ['BSIT', 'Bachelor of Science in Information Technology', 1],
            ['BSIS', 'Bachelor of Science in Information System', 1],
            ['BSCS', 'Bachelor of Science in Computer Science', 1],
            ['BLIS', 'Bachelor of Library and Information Science', 1],
            ['AB English', 'Bachelor of Arts major in English Language', 6],
            ['BHumServ', 'Bachelor in Human Services', 6],
            ['BSBA - FM', 'Bachelor of Science in Business Administration major in Financial Management', 7],
            ['BSBA - HRM', 'Bachelor of Science in Business Administration major in Human Resource Management', 7],
            ['BSBA - MM', 'Bachelor of Science in Business Administration major in Marketing Management', 6],
            ['BSBA - OM', 'Bachelor of Science in Business Administration major in Operations Management', 7],
            ['BSBA - BSUN ECON', 'Bachelor of Science in Business Administration major in Business Economics', 7],
            ['BSAIS', 'Bachelor of Science in Accounting Information System', 7],
            ['BPA', 'Bachelor of Public Administration', 7],
            ['BSE', 'Bachelor of Science in Entreprenuership', 7],
            ['BSC', 'Bachelor of Science in Commerce', 7],
            ['BSCrim', 'Bachelor of Science in Criminology', 5],
            ['BEEd', 'Bachelor of Elementary Education', 4],
            ['BSEd - English', 'Bachelor of Secondary Education major in English', 4],
            ['BSED-Math', 'Bachelor of Secondary Education major in Mathematics', 4],
            ['BSEd - Science', 'Bachelor of Secondary Education major in Science', 4],
            ['BSEd - Soc Stud', 'Bachelor of Secondary Education major in Social Studies', 4],
            ['BPEd', 'Bachelor of Physical Education', 4],
            ['BTVTE', 'Bachelor of Technical Vocational Teacher Education', 4],
            ['BSHM', 'Bachelor of Science in Hospitality Management', 8],
            ['BSTM', 'Bachelor of Science in Tourism Management', 8],
            ['DHMT', 'Diploma in Hospitality Management Technology', 8],
            ['DTMT', 'Diploma in Tourism Management Technology', 8],
        ];

        $inserted = 0;

        foreach ($rows as [$code, $name, $dumpDeptId]) {
            $deptCode = $this->departmentCodeFromDump((int) $dumpDeptId, $code);

            if (! isset($deptIds[$deptCode])) {
                $this->command->warn("Skip {$code}: department code {$deptCode} not found. Run DepartmentSeeder first.");

                continue;
            }

            $departmentId = (int) $deptIds[$deptCode];

            Course::updateOrCreate(
                [
                    'course_code' => $code,
                    'department_id' => $departmentId,
                    'semester' => null,
                    'school_year' => null,
                ],
                [
                    'course_name' => $name,
                    'year_levels' => null,
                    'is_active' => true,
                ]
            );

            $inserted++;
        }

        $this->command->info("Upserted {$inserted} courses.");
    }

    /**
     * Map courses.sql department_id to departments.code for this app’s DepartmentSeeder order.
     */
    private function departmentCodeFromDump(int $dumpDeptId, string $courseCode): string
    {
        if ($dumpDeptId === 6 && strncmp($courseCode, 'BSBA', 4) === 0) {
            return 'CBMA';
        }

        switch ($dumpDeptId) {
            case 1:
                return 'CCIS';
            case 4:
                return 'CTE';
            case 5:
                return 'CCJE';
            case 6:
                return 'CAS';
            case 7:
                return 'CBMA';
            case 8:
                return 'CTHM';
            default:
                return 'CCIS';
        }
    }
}
