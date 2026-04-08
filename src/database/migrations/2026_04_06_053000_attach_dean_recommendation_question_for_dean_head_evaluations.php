<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $questionText = 'Select one recommendation below.';
        $personnelTypes = ['dean_head_teaching', 'dean_head_non_teaching'];

        // Ensure there is at least one criterion that applies to both dean-head personnel
        // and dean evaluator group.
        $criteriaIds = DB::table('criteria as c')
            ->join('criterion_personnel_types as cpt', 'cpt.criterion_id', '=', 'c.id')
            ->join('criterion_evaluator_groups as ceg', 'ceg.criterion_id', '=', 'c.id')
            ->whereIn('cpt.personnel_type', $personnelTypes)
            ->where('ceg.evaluator_group', 'dean')
            ->pluck('c.id')
            ->unique()
            ->values()
            ->all();

        if ($criteriaIds === []) {
            $criterionId = DB::table('criteria')->insertGetId([
                'name' => 'E. Recommendations for Academic Administrator',
            ]);

            foreach ($personnelTypes as $personnelType) {
                DB::table('criterion_personnel_types')->insert([
                    'criterion_id' => $criterionId,
                    'personnel_type' => $personnelType,
                ]);
            }

            DB::table('criterion_evaluator_groups')->insert([
                'criterion_id' => $criterionId,
                'evaluator_group' => 'dean',
            ]);

            $criteriaIds = [$criterionId];
        }

        foreach ($criteriaIds as $criterionId) {
            foreach ($personnelTypes as $personnelType) {
                $existsPersonnel = DB::table('criterion_personnel_types')
                    ->where('criterion_id', $criterionId)
                    ->where('personnel_type', $personnelType)
                    ->exists();

                if (! $existsPersonnel) {
                    DB::table('criterion_personnel_types')->insert([
                        'criterion_id' => $criterionId,
                        'personnel_type' => $personnelType,
                    ]);
                }
            }

            $existsDeanGroup = DB::table('criterion_evaluator_groups')
                ->where('criterion_id', $criterionId)
                ->where('evaluator_group', 'dean')
                ->exists();

            if (! $existsDeanGroup) {
                DB::table('criterion_evaluator_groups')->insert([
                    'criterion_id' => $criterionId,
                    'evaluator_group' => 'dean',
                ]);
            }

            $existsRecommendationQuestion = DB::table('questions')
                ->where('criteria_id', $criterionId)
                ->where('response_type', 'dean_recommendation')
                ->exists();

            if (! $existsRecommendationQuestion) {
                DB::table('questions')->insert([
                    'criteria_id' => $criterionId,
                    'question_text' => $questionText,
                    'response_type' => 'dean_recommendation',
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('questions')
            ->where('response_type', 'dean_recommendation')
            ->where('question_text', 'Select one recommendation below.')
            ->delete();
    }
};
