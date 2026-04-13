<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\DeanEvaluationFeedback;
use App\Models\Department;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\Question;
use App\Models\SelfEvaluationResult;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\FacultyMlPredictionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    use LoadsFacultyPerformance;

    public function __construct(
        private readonly FacultyMlPredictionService $facultyMlPredictionService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('view-analytics');

        $user = auth()->user();

        // Dean/Head users see department-scoped analytics
        if ($user->hasRole(['dean', 'head'])) {
            return $this->departmentAnalytics($request);
        }

        return $this->systemAnalytics($request);
    }

    private function systemAnalytics(Request $request): View
    {
        $period = EvaluationService::getOpenEvaluationPeriod();

        $schoolYear = $request->input('school_year', $period?->school_year);
        $semester   = $request->input('semester', $period?->semester);

        $allFacultyRows = $this->loadFacultyWithPerformance(
            semester:   $semester,
            schoolYear: $schoolYear
        );

        $levelCounts = $allFacultyRows
            ->groupBy('performance_level')
            ->map(fn($rows) => $rows->count());

        $chartLabels = EvaluationService::analyticsPerformanceLevelLabels();
        $chartData   = collect($chartLabels)->map(fn($l) => $levelCounts->get($l, 0))->toArray();

        $departmentSummaries = Department::with('facultyProfiles')
            ->where('is_active', true)
            ->get()
            ->map(function (Department $dept) use ($allFacultyRows) {
                $deptRows = $allFacultyRows->filter(
                    fn($row) => $row['user']->department?->id === $dept->id
                );

                $avg = $deptRows->avg('weighted_average');

                return [
                    'department'        => $dept,
                    'faculty_count'     => $deptRows->count(),
                    'average_score'     => $avg ? round($avg, 2) : null,
                    'performance_level' => $avg ? EvaluationService::getPerformanceLevel($avg) : 'N/A',
                ];
            });

        $facultyNameSearch = mb_substr(trim((string) $request->input('faculty_name', '')), 0, 255);
        $tableFacultyRows  = $this->filterFacultyRowsByNameSearch($allFacultyRows, $facultyNameSearch);

        $page    = $request->input('page', 1);
        $perPage = 25;
        $facultyRows = new LengthAwarePaginator(
            $tableFacultyRows->forPage($page, $perPage)->values(),
            $tableFacultyRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $facultyRows->setCollection(
            $this->facultyMlPredictionService->attachPredictionsToRows(
                collect($facultyRows->items()),
                $semester,
                $schoolYear
            )
        );

        $mlPeriodReady = filled($semester) && filled($schoolYear);

        // Student-question analytics (used for Random Forest input visualization)
        $studentAnswerAggregate = EvaluationAnswer::query()
            ->from('evaluation_answers as ea')
            ->selectRaw('ea.question_id, SUM(ea.rating) as total_rating, COUNT(*) as response_count, COUNT(DISTINCT ea.student_id) as student_count');

        if (filled($schoolYear) || filled($semester)) {
            $studentAnswerAggregate->whereExists(function ($q) use ($schoolYear, $semester) {
                $q->selectRaw('1')
                    ->from('evaluation_feedback as ef')
                    ->whereColumn('ef.student_id', 'ea.student_id')
                    ->whereColumn('ef.faculty_id', 'ea.faculty_id')
                    ->whereColumn('ef.subject_id', 'ea.subject_id')
                    ->where('ef.evaluator_type', 'student');

                if (filled($schoolYear)) {
                    $q->where('ef.school_year', $schoolYear);
                }
                if (filled($semester)) {
                    $q->where('ef.semester', $semester);
                }
            });
        }

        $studentAnswerAggregate->groupBy('ea.question_id');

        $questionAverages = Question::query()
            ->join('criteria', 'criteria.id', '=', 'questions.criteria_id')
            ->join('criterion_evaluator_groups as ceg', function ($join) {
                $join->on('ceg.criterion_id', '=', 'criteria.id')
                    ->where('ceg.evaluator_group', '=', 'student');
            })
            ->leftJoinSub($studentAnswerAggregate, 'agg', function ($join) {
                $join->on('agg.question_id', '=', 'questions.id');
            })
            ->orderBy('criteria.name')
            ->orderBy('questions.id')
            ->get([
                'questions.id as question_id',
                'questions.question_text',
                'criteria.name as criterion_name',
                'agg.total_rating',
                'agg.response_count',
                'agg.student_count',
            ])
            ->map(function ($row) {
                $totalRating = (int) ($row->total_rating ?? 0);
                $responseCount = (int) ($row->response_count ?? 0);
                $studentCount = (int) ($row->student_count ?? 0);
                $averageRating = $responseCount > 0 ? round($totalRating / $responseCount, 2) : 0;

                $chartLabel = (string) ($row->criterion_name . ' - ' . $row->question_text);
                if (mb_strlen($chartLabel) > 110) {
                    $chartLabel = mb_substr($chartLabel, 0, 107) . '...';
                }

                return [
                    'question_id'     => (int) $row->question_id,
                    'criterion_name'  => (string) $row->criterion_name,
                    'question_text'   => (string) $row->question_text,
                    'total_rating'    => $totalRating,
                    'response_count'  => $responseCount,
                    'student_count'   => $studentCount,
                    'average_rating'  => $averageRating,
                    'chart_label'     => $chartLabel,
                ];
            })
            ->values();

        $questionAverageChartLabels = $questionAverages->pluck('chart_label')->all();
        $questionAverageChartData   = $questionAverages->pluck('average_rating')->all();

        // Historical trend by selected personnel (admin)
        $personnelOptions = User::query()
            ->with('facultyProfile')
            ->whereHasRole('faculty')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->facultyProfile !== null)
            ->values()
            ->map(function (User $u) {
                return [
                    'user_id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'profile_id' => (int) $u->facultyProfile->id,
                ];
            })
            ->values();

        $selectedPersonnelProfileId = (int) $request->input(
            'personnel_profile_id',
            (int) ($personnelOptions->first()['profile_id'] ?? 0)
        );

        if ($selectedPersonnelProfileId > 0 && ! $personnelOptions->contains(fn ($p) => $p['profile_id'] === $selectedPersonnelProfileId)) {
            $selectedPersonnelProfileId = (int) ($personnelOptions->first()['profile_id'] ?? 0);
        }

        $selectedPersonnel = $personnelOptions->first(
            fn ($p) => $p['profile_id'] === $selectedPersonnelProfileId
        );

        $selectedPersonnelUser = null;
        if ($selectedPersonnelProfileId > 0) {
            $selectedPersonnelUser = User::query()
                ->with('facultyProfile')
                ->whereHasRole('faculty')
                ->whereHas('facultyProfile', fn ($q) => $q->where('id', $selectedPersonnelProfileId))
                ->first();
        }

        $historicalTrend = [
            'labels' => [],
            'student' => [],
            'dean' => [],
            'self' => [],
            'peer' => [],
            'weighted' => [],
            'weighted_levels' => [],
        ];

        if ($selectedPersonnelUser && $selectedPersonnelUser->facultyProfile) {
            $historicalTrend = $this->buildPersonnelHistoricalTrend(
                (int) $selectedPersonnelUser->facultyProfile->id,
                (string) $selectedPersonnelUser->facultyProfile->evaluationCriteriaPersonnelType()
            );
        }

        $facultyRowsWithData = $this->facultyRowsWithEvaluationData($allFacultyRows);
        $analyticsFacultyWithDataCount = $facultyRowsWithData->count();
        $analyticsAvgGwa = $facultyRowsWithData->isNotEmpty()
            ? round((float) $facultyRowsWithData->avg('weighted_average'), 2)
            : null;
        $analyticsTopTierCount = $facultyRowsWithData->filter(function (array $row): bool {
            $level = $row['performance_level'] ?? '';

            return in_array($level, ['Excellent', 'Outstanding'], true);
        })->count();
        $analyticsNeedsAttentionCount = $facultyRowsWithData->filter(function (array $row): bool {
            return EvaluationService::qualifiesForPerformanceIntervention(
                $row['performance_level'],
                $row['profile']->evaluationCriteriaPersonnelType()
            );
        })->count();

        return view('analytics.system', compact(
            'facultyRows',
            'allFacultyRows',
            'chartLabels',
            'chartData',
            'departmentSummaries',
            'schoolYear',
            'semester',
            'period',
            'questionAverages',
            'questionAverageChartLabels',
            'questionAverageChartData',
            'personnelOptions',
            'selectedPersonnelProfileId',
            'selectedPersonnel',
            'selectedPersonnelUser',
            'historicalTrend',
            'facultyNameSearch',
            'analyticsFacultyWithDataCount',
            'analyticsAvgGwa',
            'analyticsTopTierCount',
            'analyticsNeedsAttentionCount'
        ));
    }

    private function departmentAnalytics(Request $request): View
    {
        $dean       = auth()->user();
        $period     = EvaluationService::getOpenEvaluationPeriod();
        $schoolYear = $request->input('school_year', $period?->school_year);
        $semester   = $request->input('semester', $period?->semester);

        $allFacultyRows = $this->loadFacultyWithPerformance(
            departmentId: $dean->department_id,
            semester:     $semester,
            schoolYear:   $schoolYear,
            deanUserId:   $dean->id
        );

        $levelCounts   = $allFacultyRows->groupBy('performance_level')->map(fn($r) => $r->count());
        $chartLabels   = EvaluationService::analyticsPerformanceLevelLabels();
        $chartData     = collect($chartLabels)->map(fn($l) => $levelCounts->get($l, 0))->toArray();

        $facultyRowsWithData = $this->facultyRowsWithEvaluationData($allFacultyRows);
        $departmentAvg = $facultyRowsWithData->isNotEmpty()
            ? $facultyRowsWithData->avg('weighted_average')
            : null;
        $departmentTopTierCount = $facultyRowsWithData->filter(function (array $row): bool {
            $level = $row['performance_level'] ?? '';

            return in_array($level, ['Excellent', 'Outstanding'], true);
        })->count();

        $facultyNameSearch = mb_substr(trim((string) $request->input('faculty_name', '')), 0, 255);
        $tableFacultyRows  = $this->filterFacultyRowsByNameSearch($allFacultyRows, $facultyNameSearch);

        $page    = $request->input('page', 1);
        $perPage = 25;
        $facultyRows = new LengthAwarePaginator(
            $tableFacultyRows->forPage($page, $perPage)->values(),
            $tableFacultyRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $facultyRows->setCollection(
            $this->facultyMlPredictionService->attachPredictionsToRows(
                collect($facultyRows->items()),
                $semester,
                $schoolYear
            )
        );

        $mlPeriodReady = filled($semester) && filled($schoolYear);

        return view('analytics.department', compact(
            'facultyRows',
            'allFacultyRows',
            'chartLabels',
            'chartData',
            'departmentAvg',
            'departmentTopTierCount',
            'schoolYear',
            'semester',
            'period',
            'dean',
            'facultyNameSearch',
            'mlPeriodReady'
        ));
    }

    /**
     * Rows where at least one evaluation source (student, dean, self, peer) has data for the period.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function facultyRowsWithEvaluationData(Collection $rows): Collection
    {
        return $rows->filter(function (array $row): bool {
            return ($row['student_avg'] ?? null) !== null
                || ($row['dean_avg'] ?? null) !== null
                || ($row['self_avg'] ?? null) !== null
                || ($row['peer_avg'] ?? null) !== null;
        });
    }

    /**
     * @return array{
     *   labels:list<string>,
     *   student:list<float|null>,
     *   dean:list<float|null>,
     *   self:list<float|null>,
     *   peer:list<float|null>,
     *   weighted:list<float>,
     *   weighted_levels:list<string>
     * }
     */
    private function buildPersonnelHistoricalTrend(int $profileId, string $personnelType): array
    {
        $studentRows = EvaluationFeedback::query()
            ->where('faculty_id', $profileId)
            ->where('evaluator_type', 'student')
            ->selectRaw('school_year, semester, AVG(total_average) as avg_score')
            ->groupBy('school_year', 'semester')
            ->get();

        $deanRows = DeanEvaluationFeedback::query()
            ->where('faculty_id', $profileId)
            ->selectRaw('school_year, semester, AVG(total_average) as avg_score')
            ->groupBy('school_year', 'semester')
            ->get();

        $selfRows = SelfEvaluationResult::query()
            ->where('faculty_id', $profileId)
            ->selectRaw('school_year, semester, AVG(total_average) as avg_score')
            ->groupBy('school_year', 'semester')
            ->get();

        $peerRows = FacultyPeerEvaluationFeedback::query()
            ->where('evaluatee_faculty_id', $profileId)
            ->where('evaluation_type', 'peer')
            ->selectRaw('school_year, semester, AVG(total_average) as avg_score')
            ->groupBy('school_year', 'semester')
            ->get();

        $studentMap = $this->periodScoreMap($studentRows);
        $deanMap    = $this->periodScoreMap($deanRows);
        $selfMap    = $this->periodScoreMap($selfRows);
        $peerMap    = $this->periodScoreMap($peerRows);

        $periodKeys = array_values(array_unique(array_merge(
            array_keys($studentMap),
            array_keys($deanMap),
            array_keys($selfMap),
            array_keys($peerMap)
        )));

        usort($periodKeys, fn (string $a, string $b) => $this->comparePeriodKeys($a, $b));

        $labels = [];
        $student = [];
        $dean = [];
        $self = [];
        $peer = [];
        $weighted = [];
        $weightedLevels = [];

        foreach ($periodKeys as $key) {
            [$schoolYear, $semester] = explode('|', $key);
            $labels[] = trim($schoolYear . ' - ' . $semester);

            $studentAvg = $studentMap[$key] ?? null;
            $deanAvg    = $deanMap[$key] ?? null;
            $selfAvg    = $selfMap[$key] ?? null;
            $peerAvg    = $peerMap[$key] ?? null;

            $student[] = $studentAvg;
            $dean[]    = $deanAvg;
            $self[]    = $selfAvg;
            $peer[]    = $peerAvg;

            $weightedResult = EvaluationService::computeWeightedResult(
                $studentAvg,
                $deanAvg,
                $selfAvg,
                $peerAvg,
                $personnelType
            );

            $weighted[] = (float) $weightedResult['weighted_average'];
            $weightedLevels[] = (string) ($weightedResult['performance_level'] ?? '');
        }

        return [
            'labels' => $labels,
            'student' => $student,
            'dean' => $dean,
            'self' => $self,
            'peer' => $peer,
            'weighted' => $weighted,
            'weighted_levels' => $weightedLevels,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $rows
     * @return array<string, float>
     */
    private function periodScoreMap($rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $schoolYear = trim((string) ($row->school_year ?? ''));
            $semester = trim((string) ($row->semester ?? ''));
            if ($schoolYear === '' || $semester === '') {
                continue;
            }

            $key = $schoolYear . '|' . $semester;
            $result[$key] = round((float) ($row->avg_score ?? 0), 2);
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function filterFacultyRowsByNameSearch($rows, string $search)
    {
        if ($search === '') {
            return $rows->values();
        }

        $needle = mb_strtolower($search);

        return $rows->filter(function (array $row) use ($needle) {
            $name = mb_strtolower((string) ($row['user']->name ?? ''));

            return str_contains($name, $needle);
        })->values();
    }

    private function comparePeriodKeys(string $left, string $right): int
    {
        [$leftYear, $leftSemester] = explode('|', $left);
        [$rightYear, $rightSemester] = explode('|', $right);

        preg_match('/\d{4}/', $leftYear, $leftMatch);
        preg_match('/\d{4}/', $rightYear, $rightMatch);
        $leftStartYear = isset($leftMatch[0]) ? (int) $leftMatch[0] : 0;
        $rightStartYear = isset($rightMatch[0]) ? (int) $rightMatch[0] : 0;

        if ($leftStartYear !== $rightStartYear) {
            return $leftStartYear <=> $rightStartYear;
        }

        return $this->semesterSortOrder($leftSemester) <=> $this->semesterSortOrder($rightSemester);
    }

    private function semesterSortOrder(string $semester): int
    {
        $value = mb_strtolower(trim($semester));

        return match ($value) {
            '1st', '1st semester', 'first', 'first semester' => 1,
            '2nd', '2nd semester', 'second', 'second semester' => 2,
            'summer' => 3,
            default => 9,
        };
    }
}
