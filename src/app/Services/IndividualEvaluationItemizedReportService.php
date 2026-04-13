<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\EvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class IndividualEvaluationItemizedReportService
{
    /**
     * @return array{
     *   personnel_type: string,
     *   respondent_count: int,
     *   respondent_label: string,
     *   sections: list<array{name: string, questions: list<array{n: int, text: string, numerical: float|null, descriptive: string}>, category_avg: float|null, category_descriptive: string}>,
     *   overall_avg: float|null,
     *   overall_descriptive: string,
     *   has_likert_data: bool,
     * }
     */
    public function build(FacultyProfile $profile, string $reportType, ?string $semester, ?string $schoolYear): array
    {
        $profile->loadMissing('department', 'user');

        $evaluateePersonnel = $profile->evaluationCriteriaPersonnelType();

        $evaluatorGroup = match ($reportType) {
            'student' => 'student',
            'peer'    => 'peer',
            'dean'    => 'dean',
            'self'    => 'self',
            default   => 'student',
        };

        $criteria = Criterion::query()
            ->with(['questions' => fn ($q) => $q->orderBy('id')])
            ->forEvaluatorGroup($evaluatorGroup)
            ->forPersonnelType($evaluateePersonnel)
            ->orderBy('name')
            ->get();

        $avgByQuestionId = $this->averageRatingsByQuestionId($profile->id, $reportType, $semester, $schoolYear);

        $sections        = [];
        $allQuestionAvgs = [];

        foreach ($criteria as $criterion) {
            $rows       = [];
            $catNumeric = [];
            $qNum       = 1;

            foreach ($criterion->questions as $question) {
                if (! $this->isLikertAggregateQuestion($question)) {
                    continue;
                }

                $qid = (int) $question->id;
                $avg = $avgByQuestionId[$qid] ?? null;

                $numerical   = $avg !== null ? round((float) $avg, 2) : null;
                $descriptive = $numerical !== null
                    ? EvaluationService::getPerformanceLevel((float) $numerical, $evaluateePersonnel)
                    : '—';

                $rows[] = [
                    'n'             => $qNum,
                    'text'          => html_entity_decode(trim(strip_tags((string) $question->question_text)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'numerical'     => $numerical,
                    'descriptive'   => $descriptive,
                ];

                if ($numerical !== null) {
                    $catNumeric[] = (float) $numerical;
                    $allQuestionAvgs[] = (float) $numerical;
                }
                ++$qNum;
            }

            if ($rows === []) {
                continue;
            }

            $categoryAvg = $catNumeric !== [] ? round(array_sum($catNumeric) / count($catNumeric), 2) : null;
            $categoryDesc = $categoryAvg !== null
                ? EvaluationService::getPerformanceLevel($categoryAvg, $evaluateePersonnel)
                : '—';

            $sections[] = [
                'name'                 => mb_strtoupper((string) $criterion->name),
                'questions'            => $rows,
                'category_avg'         => $categoryAvg,
                'category_descriptive' => $categoryDesc,
            ];
        }

        $overallAvg = $allQuestionAvgs !== []
            ? round(array_sum($allQuestionAvgs) / count($allQuestionAvgs), 2)
            : null;
        $overallDesc = $overallAvg !== null
            ? EvaluationService::getPerformanceLevel($overallAvg, $evaluateePersonnel)
            : '—';

        [$respondentCount, $respondentLabel] = $this->respondentStats(
            $profile->id,
            $reportType,
            $semester,
            $schoolYear
        );

        return [
            'personnel_type'     => $evaluateePersonnel,
            'respondent_count'   => $respondentCount,
            'respondent_label'   => $respondentLabel,
            'sections'           => $sections,
            'overall_avg'        => $overallAvg,
            'overall_descriptive' => $overallDesc,
            'has_likert_data'    => $allQuestionAvgs !== [],
        ];
    }

    /**
     * @return array<int, float> question_id => average rating
     */
    private function averageRatingsByQuestionId(int $facultyProfileId, string $reportType, ?string $semester, ?string $schoolYear): array
    {
        if ($reportType === 'student') {
            $rows = DB::table('evaluation_answers as ea')
                ->join('evaluation_feedback as ef', function ($j) {
                    $j->on('ea.student_id', '=', 'ef.student_id')
                        ->on('ea.faculty_id', '=', 'ef.faculty_id')
                        ->on('ea.subject_id', '=', 'ef.subject_id');
                })
                ->join('questions as q', 'q.id', '=', 'ea.question_id')
                ->where('ea.faculty_id', $facultyProfileId)
                ->where('ef.evaluator_type', 'student')
                ->when($semester, fn ($q) => $q->where('ef.semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('ef.school_year', $schoolYear))
                ->where(function ($q) {
                    $q->whereNull('q.response_type')->orWhere('q.response_type', 'likert');
                })
                ->groupBy('ea.question_id')
                ->selectRaw('ea.question_id as qid, AVG(ea.rating * 1.0) as avg_r')
                ->get();

            return $rows->mapWithKeys(fn ($r) => [(int) $r->qid => (float) $r->avg_r])->all();
        }

        if ($reportType === 'dean') {
            $rows = DB::table('dean_evaluation_answers as da')
                ->join('questions as q', 'q.id', '=', 'da.question_id')
                ->where('da.faculty_id', $facultyProfileId)
                ->when($semester, fn ($q) => $q->where('da.semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('da.school_year', $schoolYear))
                ->where(function ($q) {
                    $q->whereNull('q.response_type')->orWhere('q.response_type', 'likert');
                })
                ->groupBy('da.question_id')
                ->selectRaw('da.question_id as qid, AVG(da.rating * 1.0) as avg_r')
                ->get();

            return $rows->mapWithKeys(fn ($r) => [(int) $r->qid => (float) $r->avg_r])->all();
        }

        if ($reportType === 'peer' || $reportType === 'self') {
            $type = $reportType === 'self' ? 'self' : 'peer';

            $q = DB::table('faculty_peer_evaluation_answers as fpa')
                ->join('questions as q', 'q.id', '=', 'fpa.question_id')
                ->where('fpa.evaluatee_faculty_id', $facultyProfileId)
                ->where('fpa.evaluation_type', $type)
                ->when($semester, fn ($qq) => $qq->where('fpa.semester', $semester))
                ->when($schoolYear, fn ($qq) => $qq->where('fpa.school_year', $schoolYear))
                ->where(function ($qq) {
                    $qq->whereNull('q.response_type')->orWhere('q.response_type', 'likert');
                });

            if ($reportType === 'self') {
                $q->whereColumn('fpa.evaluator_faculty_id', 'fpa.evaluatee_faculty_id');
            }

            $rows = $q->groupBy('fpa.question_id')
                ->selectRaw('fpa.question_id as qid, AVG(fpa.rating * 1.0) as avg_r')
                ->get();

            return $rows->mapWithKeys(fn ($r) => [(int) $r->qid => (float) $r->avg_r])->all();
        }

        return [];
    }

    private function isLikertAggregateQuestion(Question $question): bool
    {
        $t = $question->response_type ?? 'likert';

        return $t === 'likert' || $t === null;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function respondentStats(int $facultyProfileId, string $reportType, ?string $semester, ?string $schoolYear): array
    {
        if ($reportType === 'student') {
            $c = EvaluationFeedback::query()
                ->where('faculty_id', $facultyProfileId)
                ->where('evaluator_type', 'student')
                ->when($semester, fn ($q) => $q->where('semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->count();

            return [$c, 'Student evaluation forms received'];
        }

        if ($reportType === 'peer') {
            $c = DB::table('faculty_peer_evaluation_feedback')
                ->where('evaluatee_faculty_id', $facultyProfileId)
                ->where('evaluation_type', 'peer')
                ->when($semester, fn ($q) => $q->where('semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->count();

            return [$c, 'Peer evaluation records'];
        }

        if ($reportType === 'dean') {
            $c = DB::table('dean_evaluation_feedback')
                ->where('faculty_id', $facultyProfileId)
                ->when($semester, fn ($q) => $q->where('semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->count();

            return [$c, 'Dean / supervisor evaluation records'];
        }

        $c = DB::table('faculty_peer_evaluation_feedback')
            ->where('evaluatee_faculty_id', $facultyProfileId)
            ->where('evaluation_type', 'self')
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->count();

        return [$c, 'Self evaluation records'];
    }
}
