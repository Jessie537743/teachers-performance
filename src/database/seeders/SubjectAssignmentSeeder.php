<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding subject_assignments...');

        // Load the ID maps written by FacultySeeder and SubjectSeeder
        $facultyMapPath = storage_path('app/faculty_profile_id_map.json');
        $subjectMapPath = storage_path('app/subject_id_map.json');

        if (! file_exists($facultyMapPath)) {
            $this->command->error('faculty_profile_id_map.json not found. Run FacultySeeder first.');
            return;
        }
        if (! file_exists($subjectMapPath)) {
            $this->command->error('subject_id_map.json not found. Run SubjectSeeder first.');
            return;
        }

        /** @var array<string,int> $facultyMap  old_profile_id -> new_profile_id */
        $facultyMap = json_decode(file_get_contents($facultyMapPath), true);

        /** @var array<string,int> $subjectMap  old_subject_id -> new_subject_id */
        $subjectMap = json_decode(file_get_contents($subjectMapPath), true);

        // Raw rows from the dump INSERT INTO subject_assignments
        // (id, subject_id, faculty_id)   — faculty_id here is faculty_profiles.id in the OLD db
        // Only rows whose subject_id and faculty_id exist in the dump's subjects and
        // faculty_profiles tables are relevant. IDs 1-11 reference subject_ids 1-11 which
        // do NOT exist in the subjects dump (only ids 12-32 are present), so those rows
        // are silently skipped.
        $rawAssignments = [
            ['old_subject_id' => 1,  'old_faculty_id' => 5],
            ['old_subject_id' => 2,  'old_faculty_id' => 6],
            ['old_subject_id' => 3,  'old_faculty_id' => 6],
            ['old_subject_id' => 1,  'old_faculty_id' => 5],
            ['old_subject_id' => 2,  'old_faculty_id' => 6],
            ['old_subject_id' => 3,  'old_faculty_id' => 5],
            ['old_subject_id' => 4,  'old_faculty_id' => 3],
            ['old_subject_id' => 5,  'old_faculty_id' => 4],
            ['old_subject_id' => 6,  'old_faculty_id' => 5],
            ['old_subject_id' => 7,  'old_faculty_id' => 6],
            ['old_subject_id' => 8,  'old_faculty_id' => 6],
            ['old_subject_id' => 9,  'old_faculty_id' => 6],
            ['old_subject_id' => 10, 'old_faculty_id' => 15],
            ['old_subject_id' => 11, 'old_faculty_id' => 16],
            ['old_subject_id' => 12, 'old_faculty_id' => 47],
            ['old_subject_id' => 13, 'old_faculty_id' => 80],
            ['old_subject_id' => 14, 'old_faculty_id' => 45],
            ['old_subject_id' => 15, 'old_faculty_id' => 24],
            ['old_subject_id' => 16, 'old_faculty_id' => 38],
            ['old_subject_id' => 17, 'old_faculty_id' => 18],
            ['old_subject_id' => 18, 'old_faculty_id' => 53],
            ['old_subject_id' => 19, 'old_faculty_id' => 53],
            ['old_subject_id' => 21, 'old_faculty_id' => 27],
            ['old_subject_id' => 22, 'old_faculty_id' => 53],
            ['old_subject_id' => 23, 'old_faculty_id' => 57],
            ['old_subject_id' => 24, 'old_faculty_id' => 27],
            ['old_subject_id' => 25, 'old_faculty_id' => 53],
            ['old_subject_id' => 26, 'old_faculty_id' => 37],
            ['old_subject_id' => 28, 'old_faculty_id' => 81],
            ['old_subject_id' => 29, 'old_faculty_id' => 31],
            ['old_subject_id' => 31, 'old_faculty_id' => 82],
            ['old_subject_id' => 32, 'old_faculty_id' => 57],
        ];

        $inserted = 0;
        $skipped  = 0;

        // Track pairs we've already inserted to avoid duplicates (the dump has some)
        $seen = [];

        foreach ($rawAssignments as $row) {
            $newSubjectId = $subjectMap[(string) $row['old_subject_id']] ?? null;
            $newFacultyId = $facultyMap[(string) $row['old_faculty_id']] ?? null;

            if ($newSubjectId === null || $newFacultyId === null) {
                $skipped++;
                continue;
            }

            $key = "{$newSubjectId}:{$newFacultyId}";
            if (isset($seen[$key])) {
                $skipped++;
                continue;
            }
            $seen[$key] = true;

            DB::table('subject_assignments')->insert([
                'subject_id' => $newSubjectId,
                'faculty_id' => $newFacultyId,
            ]);
            $inserted++;
        }

        $this->command->info("Seeded {$inserted} subject_assignments ({$skipped} skipped — missing reference or duplicate).");
    }
}
