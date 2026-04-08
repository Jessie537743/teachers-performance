<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\Question;
use Illuminate\Database\Seeder;

class DeanRecommendationCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $criterion = Criterion::firstOrCreate(
            [
                'name' => 'E. WHAT RECOMMENDATIONS WILL YOU MAKE FOR YOUR ACADEMIC ADMINISTRATOR?',
            ],
            []
        );

        foreach (['dean', 'self', 'peer'] as $evaluatorGroup) {
            $criterion->evaluatorGroups()->firstOrCreate(
                ['evaluator_group' => $evaluatorGroup],
                []
            );
        }

        $criterion->personnelTypes()->whereIn('personnel_type', ['teaching', 'non-teaching'])->delete();

        foreach (['dean_head_teaching', 'dean_head_non_teaching'] as $pt) {
            $criterion->personnelTypes()->firstOrCreate(
                ['personnel_type' => $pt],
                []
            );
        }

        Question::firstOrCreate(
            [
                'criteria_id'    => $criterion->id,
                'response_type'  => 'dean_recommendation',
            ],
            [
                'question_text' => 'Select one recommendation below.',
            ]
        );
    }
}
