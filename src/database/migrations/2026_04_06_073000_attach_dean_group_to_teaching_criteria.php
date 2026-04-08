<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $teachingCriterionNames = [
            '1. PROFESSIONAL ATTITUDE & APPEARANCE',
            'A. Knowledge of Subject Matter',
            'B. Teaching Skills',
            'C. Classroom Management',
            'D. Assessment of Learning',
            'E. General Observation',
        ];

        $criteria = DB::table('criteria')
            ->whereIn('name', $teachingCriterionNames)
            ->get(['id']);

        foreach ($criteria as $criterion) {
            $criterionId = (int) $criterion->id;

            DB::table('criterion_personnel_types')->insertOrIgnore([
                'criterion_id' => $criterionId,
                'personnel_type' => 'teaching',
            ]);

            DB::table('criterion_evaluator_groups')->insertOrIgnore([
                'criterion_id' => $criterionId,
                'evaluator_group' => 'dean',
            ]);
        }
    }

    public function down(): void
    {
        $teachingCriterionNames = [
            '1. PROFESSIONAL ATTITUDE & APPEARANCE',
            'A. Knowledge of Subject Matter',
            'B. Teaching Skills',
            'C. Classroom Management',
            'D. Assessment of Learning',
            'E. General Observation',
        ];

        $criterionIds = DB::table('criteria')
            ->whereIn('name', $teachingCriterionNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($criterionIds !== []) {
            DB::table('criterion_evaluator_groups')
                ->whereIn('criterion_id', $criterionIds)
                ->where('evaluator_group', 'dean')
                ->delete();
        }
    }
};
