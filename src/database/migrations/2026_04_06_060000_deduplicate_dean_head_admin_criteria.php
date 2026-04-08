<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $targetNames = [
                'A. ADMINISTRATIVE/SUPERVISORY COMPETENCE',
                'B. INSTRUCTIONAL LEADERSHIP',
                'C. PERSONAL/ PROFESSIONAL RELATIONSHIPS WITH PERSONNEL',
                'D. INTERPERSONAL RELATIONSHIP WITH STUDENTS',
            ];

            foreach ($targetNames as $criterionName) {
                $criteria = DB::table('criteria')
                    ->where('name', $criterionName)
                    ->orderBy('id')
                    ->get(['id']);

                if ($criteria->count() <= 1) {
                    continue;
                }

                $keepCriterionId = (int) $criteria->first()->id;
                $duplicateCriterionIds = $criteria
                    ->skip(1)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                foreach ($duplicateCriterionIds as $duplicateCriterionId) {
                    $this->mergeCriterionMetadata($keepCriterionId, $duplicateCriterionId);
                    $this->remapQuestionsAndReferences($keepCriterionId, $duplicateCriterionId);

                    DB::table('criterion_evaluator_groups')
                        ->where('criterion_id', $duplicateCriterionId)
                        ->delete();
                    DB::table('criterion_personnel_types')
                        ->where('criterion_id', $duplicateCriterionId)
                        ->delete();
                    DB::table('criteria')
                        ->where('id', $duplicateCriterionId)
                        ->delete();
                }

                $this->deduplicateQuestionsWithinCriterion($keepCriterionId);
            }
        });
    }

    public function down(): void
    {
        // Irreversible data cleanup migration.
    }

    private function mergeCriterionMetadata(int $keepCriterionId, int $duplicateCriterionId): void
    {
        $groups = DB::table('criterion_evaluator_groups')
            ->where('criterion_id', $duplicateCriterionId)
            ->pluck('evaluator_group')
            ->all();

        foreach ($groups as $group) {
            DB::table('criterion_evaluator_groups')->insertOrIgnore([
                'criterion_id' => $keepCriterionId,
                'evaluator_group' => $group,
            ]);
        }

        $personnelTypes = DB::table('criterion_personnel_types')
            ->where('criterion_id', $duplicateCriterionId)
            ->pluck('personnel_type')
            ->all();

        foreach ($personnelTypes as $personnelType) {
            DB::table('criterion_personnel_types')->insertOrIgnore([
                'criterion_id' => $keepCriterionId,
                'personnel_type' => $personnelType,
            ]);
        }
    }

    private function remapQuestionsAndReferences(int $keepCriterionId, int $duplicateCriterionId): void
    {
        $dupQuestions = DB::table('questions')
            ->where('criteria_id', $duplicateCriterionId)
            ->orderBy('id')
            ->get(['id', 'question_text', 'response_type']);

        foreach ($dupQuestions as $dupQuestion) {
            $targetQuestionId = DB::table('questions')
                ->where('criteria_id', $keepCriterionId)
                ->where('question_text', $dupQuestion->question_text)
                ->where('response_type', $dupQuestion->response_type)
                ->value('id');

            if (! $targetQuestionId) {
                $targetQuestionId = DB::table('questions')->insertGetId([
                    'criteria_id' => $keepCriterionId,
                    'question_text' => $dupQuestion->question_text,
                    'response_type' => $dupQuestion->response_type,
                ]);
            }

            $dupQuestionId = (int) $dupQuestion->id;
            $targetQuestionId = (int) $targetQuestionId;

            DB::table('evaluation_answers')
                ->where('question_id', $dupQuestionId)
                ->update(['question_id' => $targetQuestionId]);

            DB::table('dean_evaluation_answers')
                ->where('question_id', $dupQuestionId)
                ->update([
                    'question_id' => $targetQuestionId,
                    'criteria_id' => $keepCriterionId,
                ]);

            DB::table('faculty_peer_evaluation_answers')
                ->where('question_id', $dupQuestionId)
                ->update([
                    'question_id' => $targetQuestionId,
                    'criteria_id' => $keepCriterionId,
                ]);

            DB::table('interventions')
                ->where('question_id', $dupQuestionId)
                ->update(['question_id' => $targetQuestionId]);
        }

        DB::table('dean_evaluation_answers')
            ->where('criteria_id', $duplicateCriterionId)
            ->update(['criteria_id' => $keepCriterionId]);

        DB::table('faculty_peer_evaluation_answers')
            ->where('criteria_id', $duplicateCriterionId)
            ->update(['criteria_id' => $keepCriterionId]);

        DB::table('questions')
            ->where('criteria_id', $duplicateCriterionId)
            ->delete();
    }

    private function deduplicateQuestionsWithinCriterion(int $criterionId): void
    {
        $groups = DB::table('questions')
            ->where('criteria_id', $criterionId)
            ->orderBy('id')
            ->get(['id', 'question_text', 'response_type'])
            ->groupBy(function ($q) {
                return (string) $q->question_text . '||' . (string) $q->response_type;
            });

        foreach ($groups as $items) {
            if ($items->count() <= 1) {
                continue;
            }

            $keepQuestionId = (int) $items->first()->id;
            $duplicateIds = $items
                ->skip(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            foreach ($duplicateIds as $duplicateQuestionId) {
                DB::table('evaluation_answers')
                    ->where('question_id', $duplicateQuestionId)
                    ->update(['question_id' => $keepQuestionId]);

                DB::table('dean_evaluation_answers')
                    ->where('question_id', $duplicateQuestionId)
                    ->update(['question_id' => $keepQuestionId]);

                DB::table('faculty_peer_evaluation_answers')
                    ->where('question_id', $duplicateQuestionId)
                    ->update(['question_id' => $keepQuestionId]);

                DB::table('interventions')
                    ->where('question_id', $duplicateQuestionId)
                    ->update(['question_id' => $keepQuestionId]);
            }

            DB::table('questions')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }
};
