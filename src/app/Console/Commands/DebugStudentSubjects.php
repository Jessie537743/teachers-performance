<?php

namespace App\Console\Commands;

use App\Models\StudentSubjectAssignment;
use App\Models\User;
use Illuminate\Console\Command;

class DebugStudentSubjects extends Command
{
    protected $signature = 'debug:student-subjects {email}';
    protected $description = 'Show why a student does or does not see assigned subjects on their dashboard.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        $profile = $user->studentProfile;
        if (! $profile) {
            $this->error('User has no student profile.');
            return self::FAILURE;
        }

        $this->info("Student: {$user->name}  ({$user->email})");
        $this->line("  course      : '{$profile->course}'");
        $this->line("  year_level  : '{$profile->year_level}'");
        $this->line("  section     : '{$profile->section}'");
        $this->line("  semester    : '{$profile->semester}'");
        $this->line("  school_year : '{$profile->school_year}'");

        $assignments = StudentSubjectAssignment::with('subject')
            ->where('student_profile_id', $profile->id)
            ->get();

        $this->newLine();
        $this->info("student_subject_assignments rows: {$assignments->count()}");

        if ($assignments->isEmpty()) {
            $this->warn('No subjects linked to this student in student_subject_assignments.');
            $this->line('Run your subject-assignment seeder/importer to populate this table.');
            return self::SUCCESS;
        }

        $headers = ['Subject ID', 'Code', 'Course', 'Year', 'Section', 'Semester', 'SY', 'Match?'];
        $rows = $assignments->map(function ($a) use ($profile) {
            $s = $a->subject;
            if (! $s) {
                return ['—', '(missing subject)', '', '', '', '', '', 'no'];
            }
            $courseOk  = mb_strtolower(trim((string)$s->course)) === mb_strtolower(trim((string)$profile->course));
            $yearOk    = trim((string)$s->year_level) === trim((string)$profile->year_level);
            $sectionOk = mb_strtolower(trim((string)$s->section)) === mb_strtolower(trim((string)$profile->section));
            $match = ($courseOk && $yearOk && $sectionOk) ? 'YES' : sprintf('no (%s%s%s)',
                $courseOk ? '' : 'C',
                $yearOk ? '' : 'Y',
                $sectionOk ? '' : 'S'
            );
            return [$s->id, $s->code, $s->course, $s->year_level, $s->section, $s->semester, $s->school_year, $match];
        })->all();

        $this->table($headers, $rows);
        $this->newLine();
        $this->line('Match column legend: C=course mismatch, Y=year mismatch, S=section mismatch');

        return self::SUCCESS;
    }
}
