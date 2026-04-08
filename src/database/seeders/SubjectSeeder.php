<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    private array $deptIdToCode = [
        1  => 'CCIS',
        4  => 'CTE',
        5  => 'CCJE',
        6  => 'CAS',
        7  => 'CBMA',
        8  => 'CTHM',
        14 => 'EDP',
        15 => 'PMO',
        16 => 'Registrar',
        17 => 'Finance',
        18 => 'OSAS',
        19 => 'RIID',
    ];

    public function run(): void
    {
        $this->command->info('Seeding subjects...');

        $deptMap = DB::table('departments')->pluck('id', 'code')->toArray();

        $dept = function (int $oldId) use ($deptMap): ?int {
            $code = $this->deptIdToCode[$oldId] ?? null;
            return $code ? ($deptMap[$code] ?? null) : null;
        };

        // Raw rows from dump INSERT INTO subjects
        // (old_id, code, title, old_dept_id, course, year_level, section, semester, school_year)
        $subjects = [
            ['old_id' => 12, 'code' => 'ACCTG 1',  'title' => 'Fundamentals of Accounting',                                  'old_dept_id' => 7, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 13, 'code' => 'REED 102',  'title' => 'Ecclesiology',                                                'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 14, 'code' => 'NSTP',      'title' => 'National Service Training Program 2',                        'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 15, 'code' => 'GEC 6',     'title' => 'Purposive Communication',                                   'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 16, 'code' => 'PE 2',      'title' => 'Pathgit 2: Excised-Based Fitness',                          'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 17, 'code' => 'GEC 2',     'title' => 'Reading in the Philippines History',                        'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 18, 'code' => 'MUL',       'title' => 'Multimedia System',                                         'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 19, 'code' => 'IAS',       'title' => 'Information Assurance and Security 1',                     'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 21, 'code' => 'PROG 2',    'title' => 'Computer Programming 2',                                    'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '1', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 22, 'code' => 'IT IPT',    'title' => 'Integrative Programming and Technologies',                 'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 23, 'code' => 'IT HCI',    'title' => 'Human Computer Interaction',                                'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 24, 'code' => 'IT WEB',    'title' => 'Web System and Technologies',                               'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 25, 'code' => 'IT APPDEV', 'title' => 'Application and Development and Emerging Technologies',    'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 26, 'code' => 'PE 4',      'title' => 'Pathfit 4: Sports',                                        'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 28, 'code' => 'REED 104',  'title' => 'Liturgy and Sacraments',                                   'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 29, 'code' => 'GEC 7',     'title' => 'Arts Appreciation',                                        'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 31, 'code' => 'GEC 8',     'title' => 'Life and Works of Rizal',                                  'old_dept_id' => 6, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
            ['old_id' => 32, 'code' => 'IT DM',     'title' => 'Discrete Mathematics',                                     'old_dept_id' => 1, 'course' => 'BSIT', 'year_level' => '2', 'section' => '1', 'semester' => '2nd Semester', 'school_year' => '2025-2026'],
        ];

        // old_subject_id -> new_subject_id (persisted for SubjectAssignmentSeeder)
        $oldSubjectToNew = [];

        foreach ($subjects as $row) {
            $newId = DB::table('subjects')->insertGetId([
                'code'          => $row['code'],
                'title'         => $row['title'],
                'department_id' => $dept($row['old_dept_id']),
                'course'        => $row['course'],
                'year_level'    => $row['year_level'],
                'section'       => $row['section'],
                'semester'      => $row['semester'],
                'school_year'   => $row['school_year'],
                'catalog_id'    => null,
            ]);

            $oldSubjectToNew[$row['old_id']] = $newId;
        }

        // Persist map so SubjectAssignmentSeeder can use it
        $mapPath = storage_path('app/subject_id_map.json');
        file_put_contents($mapPath, json_encode($oldSubjectToNew));

        $this->command->info('Seeded ' . count($subjects) . ' subjects. Map saved to ' . $mapPath);
    }
}
