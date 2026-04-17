<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $criterionIds = DB::table('questions')
            ->where('response_type', 'dean_recommendation')
            ->distinct()
            ->pluck('criteria_id')
            ->all();

        foreach ($criterionIds as $criterionId) {
            foreach (['teaching', 'non-teaching'] as $personnelType) {
                DB::table('criterion_personnel_types')->insertOrIgnore([
                    'criterion_id'   => $criterionId,
                    'personnel_type' => $personnelType,
                ]);
            }

            DB::table('criterion_evaluator_groups')->insertOrIgnore([
                'criterion_id'    => $criterionId,
                'evaluator_group' => 'self',
            ]);
        }
    }

    public function down(): void
    {
        $criterionIds = DB::table('questions')
            ->where('response_type', 'dean_recommendation')
            ->distinct()
            ->pluck('criteria_id')
            ->all();

        foreach ($criterionIds as $criterionId) {
            DB::table('criterion_personnel_types')
                ->where('criterion_id', $criterionId)
                ->whereIn('personnel_type', ['teaching', 'non-teaching'])
                ->delete();
        }
    }
};
