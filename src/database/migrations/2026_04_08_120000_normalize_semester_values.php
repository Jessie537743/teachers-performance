<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that store a semester string. All values are normalized to one of:
     *   '1st Semester', '2nd Semester', 'Summer'
     */
    private array $tables = [
        'student_profiles',
        'courses',
        'subjects',
        'subject_offerings',
        'evaluation_periods',
        'evaluation_feedback',
        'dean_evaluation_answers',
        'dean_evaluation_feedback',
        'faculty_peer_evaluation_answers',
        'faculty_peer_evaluation_feedback',
        'peer_evaluation_results',
        'self_evaluation_results',
        'faculty_evaluation_summary',
        'faculty_predictions',
        'ai_model_metrics',
        'ai_feature_importance',
    ];

    private array $map = [
        // 2nd
        '2nd'           => '2nd Semester',
        '2nd semest'    => '2nd Semester',
        '2nd sem'       => '2nd Semester',
        'second'        => '2nd Semester',
        '2'             => '2nd Semester',
        // 1st
        '1st'           => '1st Semester',
        '1st semest'    => '1st Semester',
        '1st sem'       => '1st Semester',
        'first'         => '1st Semester',
        '1'             => '1st Semester',
        // Summer
        'summer'        => 'Summer',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'semester')) {
                continue;
            }

            // Special-case the unique index on evaluation_periods to avoid
            // duplicate-key collisions when normalizing.
            if ($table === 'evaluation_periods') {
                $this->normalizeEvaluationPeriods();
                continue;
            }

            foreach ($this->map as $from => $to) {
                DB::table($table)
                    ->whereRaw('LOWER(TRIM(semester)) = ?', [$from])
                    ->update(['semester' => $to]);
            }
        }

        // last_promoted_semester on student_profiles
        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'last_promoted_semester')) {
            foreach ($this->map as $from => $to) {
                DB::table('student_profiles')
                    ->whereRaw('LOWER(TRIM(last_promoted_semester)) = ?', [$from])
                    ->update(['last_promoted_semester' => $to]);
            }
        }
    }

    private function normalizeEvaluationPeriods(): void
    {
        $rows = DB::table('evaluation_periods')->get();
        foreach ($rows as $row) {
            $canonical = $this->canonical($row->semester);
            if ($canonical === null || $canonical === $row->semester) {
                continue;
            }
            // Skip if a canonical row already exists for the same school year.
            $exists = DB::table('evaluation_periods')
                ->where('school_year', $row->school_year)
                ->where('semester', $canonical)
                ->where('id', '!=', $row->id)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('evaluation_periods')->where('id', $row->id)->update(['semester' => $canonical]);
        }
    }

    private function canonical(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $key = strtolower(trim($value));
        return $this->map[$key] ?? null;
    }

    public function down(): void
    {
        // No-op: this migration only standardizes data; we do not restore
        // truncated/inconsistent values.
    }
};
