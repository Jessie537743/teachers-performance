<?php

namespace App\Services;

use App\Models\DeanEvaluationAnswer;
use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationAnswer;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\Question;
use App\Models\SelfEvaluationResult;
use Illuminate\Support\Collection;

class InterventionSuggestionService
{
    /** @var array<string, float> */
    private const SOURCE_WEIGHTS = [
        'student' => 0.40,
        'dean'    => 0.40,
        'self'    => 0.10,
        'peer'    => 0.10,
    ];

    /**
     * @return array{
     *   personnel_type: string,
     *   overall: array{weighted_average: float|null, performance_level: string|null, qualifies_intervention: bool},
     *   weak_questions: list<array{
     *     question_id: int,
     *     question_text: string,
     *     criterion_name: string,
     *     weighted_question_average: float,
     *     performance_level: string,
     *     sources: array<string, float|null>,
     *     interventions: \Illuminate\Support\Collection<int, \App\Models\Intervention>,
     *   }>,
     * }
     */
    public function analyze(FacultyProfile $profile, string $semester, string $schoolYear): array
    {
        $profile->loadMissing('department');
        $personnelType = $profile->evaluationCriteriaPersonnelType();

        $studentComp = EvaluationFeedback::query()
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->avg('total_average');

        $deanComp = DeanEvaluationFeedback::query()
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->avg('total_average');

        $selfComp = SelfEvaluationResult::query()
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->avg('total_average');

        $peerComp = FacultyPeerEvaluationFeedback::query()
            ->where('evaluatee_faculty_id', $profile->id)
            ->where('evaluation_type', 'peer')
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->avg('total_average');

        $hasAnyComponent = $studentComp !== null || $deanComp !== null || $selfComp !== null || $peerComp !== null;

        $weighted = EvaluationService::computeWeightedResult(
            $studentComp !== null ? (float) $studentComp : null,
            $deanComp !== null ? (float) $deanComp : null,
            $selfComp !== null ? (float) $selfComp : null,
            $peerComp !== null ? (float) $peerComp : null,
            $personnelType
        );

        $overallLevel = ($hasAnyComponent && (float) $weighted['weighted_average'] > 0)
            ? $weighted['performance_level']
            : null;
        $qualifies = EvaluationService::qualifiesForPerformanceIntervention($overallLevel, $personnelType);

        $weakQuestions = [];
        if ($qualifies) {
            $bySource = [
                'student' => $this->studentQuestionAverages($profile->id, $semester, $schoolYear),
                'dean'    => $this->deanQuestionAverages($profile->id, $semester, $schoolYear),
                'self'    => $this->facultyPeerQuestionAverages($profile->id, $semester, $schoolYear, 'self'),
                'peer'    => $this->facultyPeerQuestionAverages($profile->id, $semester, $schoolYear, 'peer'),
            ];

            $questionIds = collect($bySource)->flatMap(fn (Collection $c) => $c->keys())->unique()->values()->all();

            foreach ($questionIds as $qid) {
                $qid = (int) $qid;
                $weightedQ = $this->weightedQuestionScore($bySource, $qid);
                if ($weightedQ === null) {
                    continue;
                }

                $qLevel = EvaluationService::getPerformanceLevel($weightedQ, $personnelType);

                if (! EvaluationService::qualifiesForPerformanceIntervention($qLevel, $personnelType)) {
                    continue;
                }

                $sources = [];
                foreach (array_keys(self::SOURCE_WEIGHTS) as $src) {
                    $sources[$src] = $bySource[$src]->has($qid) ? round((float) $bySource[$src][$qid], 2) : null;
                }

                $weakQuestions[] = [
                    'question_id'               => $qid,
                    'question_text'             => '',
                    'criterion_name'            => '',
                    'weighted_question_average' => round($weightedQ, 2),
                    'performance_level'         => $qLevel,
                    'sources'                   => $sources,
                    'interventions'             => collect(),
                ];
            }

            usort($weakQuestions, fn ($a, $b) => $a['weighted_question_average'] <=> $b['weighted_question_average']);

            $questionModels = Question::query()
                ->with(['criterion', 'interventions'])
                ->whereIn('id', array_column($weakQuestions, 'question_id'))
                ->get()
                ->keyBy('id');

            foreach ($weakQuestions as $i => $row) {
                $q = $questionModels->get($row['question_id']);
                if ($q) {
                    $weakQuestions[$i]['question_text']  = $q->question_text;
                    $weakQuestions[$i]['criterion_name'] = $q->criterion?->name ?? '—';
                    $weakQuestions[$i]['interventions']  = $q->interventions;
                }
            }
        }

        return [
            'personnel_type'  => $personnelType,
            'overall'         => [
                'weighted_average'      => $weighted['weighted_average'] > 0 ? round((float) $weighted['weighted_average'], 2) : null,
                'performance_level'     => $overallLevel,
                'qualifies_intervention' => $qualifies,
                'components'            => $weighted['components'],
            ],
            'weak_questions' => $weakQuestions,
        ];
    }

    /**
     * @return Collection<int, float> question_id => avg rating
     */
    private function studentQuestionAverages(int $facultyProfileId, string $semester, string $schoolYear): Collection
    {
        return EvaluationAnswer::query()
            ->join('evaluation_feedback', function ($join) {
                $join->on('evaluation_feedback.student_id', '=', 'evaluation_answers.student_id')
                    ->on('evaluation_feedback.faculty_id', '=', 'evaluation_answers.faculty_id')
                    ->on('evaluation_feedback.subject_id', '=', 'evaluation_answers.subject_id');
            })
            ->join('questions', 'questions.id', '=', 'evaluation_answers.question_id')
            ->where('evaluation_answers.faculty_id', $facultyProfileId)
            ->where('evaluation_feedback.semester', $semester)
            ->where('evaluation_feedback.school_year', $schoolYear)
            ->where(function ($q) {
                $q->whereNull('questions.response_type')
                    ->orWhere('questions.response_type', '!=', 'dean_recommendation');
            })
            ->groupBy('evaluation_answers.question_id')
            ->selectRaw('evaluation_answers.question_id as qid, AVG(evaluation_answers.rating) as avg_rating')
            ->pluck('avg_rating', 'qid');
    }

    /**
     * @return Collection<int, float>
     */
    private function deanQuestionAverages(int $facultyProfileId, string $semester, string $schoolYear): Collection
    {
        return DeanEvaluationAnswer::query()
            ->join('questions', 'questions.id', '=', 'dean_evaluation_answers.question_id')
            ->where('dean_evaluation_answers.faculty_id', $facultyProfileId)
            ->where('dean_evaluation_answers.semester', $semester)
            ->where('dean_evaluation_answers.school_year', $schoolYear)
            ->where(function ($q) {
                $q->whereNull('questions.response_type')
                    ->orWhere('questions.response_type', '!=', 'dean_recommendation');
            })
            ->groupBy('dean_evaluation_answers.question_id')
            ->selectRaw('dean_evaluation_answers.question_id as qid, AVG(dean_evaluation_answers.rating) as avg_rating')
            ->pluck('avg_rating', 'qid');
    }

    /**
     * @return Collection<int, float>
     */
    private function facultyPeerQuestionAverages(int $facultyProfileId, string $semester, string $schoolYear, string $type): Collection
    {
        return FacultyPeerEvaluationAnswer::query()
            ->join('questions', 'questions.id', '=', 'faculty_peer_evaluation_answers.question_id')
            ->where('faculty_peer_evaluation_answers.evaluatee_faculty_id', $facultyProfileId)
            ->where('faculty_peer_evaluation_answers.evaluation_type', $type)
            ->where('faculty_peer_evaluation_answers.semester', $semester)
            ->where('faculty_peer_evaluation_answers.school_year', $schoolYear)
            ->where(function ($q) {
                $q->whereNull('questions.response_type')
                    ->orWhere('questions.response_type', '!=', 'dean_recommendation');
            })
            ->groupBy('faculty_peer_evaluation_answers.question_id')
            ->selectRaw('faculty_peer_evaluation_answers.question_id as qid, AVG(faculty_peer_evaluation_answers.rating) as avg_rating')
            ->pluck('avg_rating', 'qid');
    }

    /**
     * @param  array<string, Collection<int, float>>  $bySource
     */
    private function weightedQuestionScore(array $bySource, int $questionId): ?float
    {
        $sum = 0.0;
        $w   = 0.0;

        foreach (self::SOURCE_WEIGHTS as $src => $weight) {
            if ($bySource[$src]->has($questionId)) {
                $sum += (float) $bySource[$src][$questionId] * $weight;
                $w += $weight;
            }
        }

        if ($w <= 0) {
            return null;
        }

        if ($w < 1.0 - 1e-6) {
            return $sum / $w;
        }

        return $sum;
    }
}
