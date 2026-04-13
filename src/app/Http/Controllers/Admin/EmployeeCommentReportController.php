<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\EvaluationPeriod;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\SelfEvaluationResult;
use App\Services\TextSentimentAnalyzer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeCommentReportController extends Controller
{
    public function index(Request $request, TextSentimentAnalyzer $sentimentAnalyzer): View
    {
        $this->authorizeAccess();

        $facultyOptions = FacultyProfile::query()
            ->with(['user', 'department'])
            ->whereHas('user', fn ($q) => $q->where('role', 'faculty')->where('is_active', true))
            ->get()
            ->sortBy(fn (FacultyProfile $profile) => strtolower($profile->user?->name ?? ''))
            ->values();

        $departmentOptions = $facultyOptions
            ->map(fn (FacultyProfile $profile) => $profile->department?->name)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $periods = EvaluationPeriod::query()
            ->select(['semester', 'school_year'])
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $validated = $request->validate([
            'faculty_profile_id' => ['nullable', 'integer', 'exists:faculty_profiles,id'],
            'employee_name' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'semester' => ['nullable', 'string', 'max:20'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'comment_search' => ['nullable', 'string', 'max:200'],
            'print' => ['nullable', 'boolean'],
            'generate_all' => ['nullable', 'boolean'],
        ]);

        $filterSemester = trim((string) ($validated['semester'] ?? ''));
        $filterSchoolYear = trim((string) ($validated['school_year'] ?? ''));
        $filterSemester = $filterSemester !== '' ? $filterSemester : null;
        $filterSchoolYear = $filterSchoolYear !== '' ? $filterSchoolYear : null;

        $schoolYearOptions = $periods->pluck('school_year')->filter()->unique()->sort()->values();
        if ($filterSchoolYear !== null && ! $schoolYearOptions->contains(fn ($y) => (string) $y === (string) $filterSchoolYear)) {
            $filterSchoolYear = null;
        }

        $semesterOptions = $filterSchoolYear
            ? $periods->where('school_year', $filterSchoolYear)->pluck('semester')->filter()->unique()->sort()->values()
            : $periods->pluck('semester')->filter()->unique()->sort()->values();

        if ($filterSemester !== null && ! $semesterOptions->contains(fn ($s) => (string) $s === (string) $filterSemester)) {
            $filterSemester = null;
        }

        if ($filterSemester !== null && $filterSchoolYear !== null) {
            $pairOk = $periods->contains(
                fn ($p) => (string) $p->semester === (string) $filterSemester
                    && (string) $p->school_year === (string) $filterSchoolYear
            );
            if (! $pairOk) {
                $filterSemester = null;
            }
        }

        $selectedFaculty = null;
        $comments = collect();
        $commentsByLevel = collect();
        $sourceCounts = collect();
        $sentimentCounts = collect();
        $allFacultyReports = collect();
        $commentsPage = null;
        $insights = [
            'strengths' => [],
            'issues' => [],
        ];

        $employeeNameQuery = trim((string) ($validated['employee_name'] ?? ''));
        $selectedDepartment = trim((string) ($validated['department'] ?? ''));
        $commentSearch = trim((string) ($validated['comment_search'] ?? ''));
        $generateAll = (bool) ($validated['generate_all'] ?? false);

        if ($employeeNameQuery !== '') {
            $facultyOptions = $facultyOptions
                ->filter(function (FacultyProfile $profile) use ($employeeNameQuery): bool {
                    $name = strtolower((string) ($profile->user?->name ?? ''));

                    return str_contains($name, strtolower($employeeNameQuery));
                })
                ->values();
        }
        if ($selectedDepartment !== '') {
            $facultyOptions = $facultyOptions
                ->filter(function (FacultyProfile $profile) use ($selectedDepartment): bool {
                    return strcasecmp((string) ($profile->department?->name ?? ''), $selectedDepartment) === 0;
                })
                ->values();
        }

        if ($generateAll) {
            $allFacultyReports = $facultyOptions
                ->map(function (FacultyProfile $faculty) use ($filterSemester, $filterSchoolYear, $sentimentAnalyzer): array {
                    $facultyName = $faculty->user?->name ?? 'Faculty Member';
                    $facultyComments = $this->loadCommentsForFaculty(
                        facultyProfileId: $faculty->id,
                        semester: $filterSemester,
                        schoolYear: $filterSchoolYear,
                        facultyName: $facultyName,
                        sentimentAnalyzer: $sentimentAnalyzer,
                    );

                    $facultyCommentsByLevel = $facultyComments
                        ->groupBy(fn (array $row) => $row['performance_level'] ?: 'Unclassified')
                        ->sortKeys();

                    $facultySourceCounts = $facultyComments
                        ->groupBy(fn (array $row) => $row['source'])
                        ->map(fn (Collection $group) => $group->count())
                        ->sortKeys();

                    $facultySentimentCounts = $facultyComments
                        ->groupBy(fn (array $row) => $row['sentiment'] ?? 'neutral')
                        ->map(fn (Collection $group) => $group->count());

                    return [
                        'faculty' => $faculty,
                        'comments' => $facultyComments,
                        'commentsByLevel' => $facultyCommentsByLevel,
                        'sourceCounts' => $facultySourceCounts,
                        'sentimentCounts' => $facultySentimentCounts,
                    ];
                })
                ->filter(fn (array $row): bool => $row['comments']->isNotEmpty())
                ->values();
        } elseif (!empty($validated['faculty_profile_id'])) {
            $selectedFaculty = FacultyProfile::with(['user', 'department'])->find($validated['faculty_profile_id']);

            if ($selectedFaculty) {
                $comments = $this->loadCommentsForFaculty(
                    facultyProfileId: $selectedFaculty->id,
                    semester: $filterSemester,
                    schoolYear: $filterSchoolYear,
                    facultyName: $selectedFaculty->user?->name ?? 'Faculty Member',
                    sentimentAnalyzer: $sentimentAnalyzer,
                );

                if ($commentSearch !== '') {
                    $searchNeedle = strtolower($commentSearch);
                    $comments = $comments
                        ->filter(function (array $row) use ($searchNeedle): bool {
                            $haystacks = [
                                strtolower((string) ($row['comment'] ?? '')),
                                strtolower((string) ($row['evaluator'] ?? '')),
                                strtolower((string) ($row['source'] ?? '')),
                            ];

                            foreach ($haystacks as $haystack) {
                                if (str_contains($haystack, $searchNeedle)) {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->values();
                }

                $commentsByLevel = $comments
                    ->groupBy(fn (array $row) => $row['performance_level'] ?: 'Unclassified')
                    ->sortKeys();

                $sourceCounts = $comments
                    ->groupBy(fn (array $row) => $row['source'])
                    ->map(fn (Collection $group) => $group->count())
                    ->sortKeys();

                $sentimentCounts = $comments
                    ->groupBy(fn (array $row) => $row['sentiment'] ?? 'neutral')
                    ->map(fn (Collection $group) => $group->count());

                $insights = $this->buildInsights($comments);
                $commentsPage = $this->paginateCollection(
                    items: $comments,
                    perPage: 12,
                    pageName: 'comments_page',
                    currentPage: (int) $request->query('comments_page', 1),
                );
            }
        }

        return view('admin.employee-comments-report', [
            'facultyOptions' => $facultyOptions,
            'periods' => $periods,
            'semesterOptions' => $semesterOptions,
            'schoolYearOptions' => $schoolYearOptions,
            'departmentOptions' => $departmentOptions,
            'selectedFaculty' => $selectedFaculty,
            'selectedEmployeeName' => $employeeNameQuery,
            'selectedDepartment' => $selectedDepartment,
            'selectedSemester' => $filterSemester,
            'selectedSchoolYear' => $filterSchoolYear,
            'commentSearch' => $commentSearch,
            'comments' => $comments,
            'commentsPage' => $commentsPage,
            'commentsByLevel' => $commentsByLevel,
            'sourceCounts' => $sourceCounts,
            'sentimentCounts' => $sentimentCounts,
            'insights' => $insights,
            'allFacultyReports' => $allFacultyReports,
            'generateAll' => $generateAll,
            'printMode' => (bool) ($validated['print'] ?? false),
        ]);
    }

    private function loadCommentsForFaculty(
        int $facultyProfileId,
        ?string $semester,
        ?string $schoolYear,
        string $facultyName,
        TextSentimentAnalyzer $sentimentAnalyzer,
    ): Collection {
        $studentFeedback = EvaluationFeedback::query()
            ->with('student')
            ->where('faculty_id', $facultyProfileId)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->get()
            ->map(function (EvaluationFeedback $row): array {
                return [
                    'source' => 'Student Evaluation',
                    'evaluator' => $row->student?->name ?? 'Student',
                    'comment' => trim((string) $row->comment),
                    'performance_level' => $row->performance_level ?: null,
                    'score' => $row->total_average !== null ? (float) $row->total_average : null,
                    'semester' => $row->semester,
                    'school_year' => $row->school_year,
                    'created_at' => $row->created_at,
                ];
            });

        $deanFeedback = DeanEvaluationFeedback::query()
            ->with('dean')
            ->where('faculty_id', $facultyProfileId)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->get()
            ->map(function (DeanEvaluationFeedback $row): array {
                return [
                    'source' => 'Dean Evaluation',
                    'evaluator' => $row->dean?->name ?? 'Dean',
                    'comment' => trim((string) $row->comment),
                    'performance_level' => $row->performance_level ?: null,
                    'score' => $row->total_average !== null ? (float) $row->total_average : null,
                    'semester' => $row->semester,
                    'school_year' => $row->school_year,
                    'created_at' => $row->created_at,
                ];
            });

        $peerFeedback = FacultyPeerEvaluationFeedback::query()
            ->with('evaluator.user')
            ->where('evaluatee_faculty_id', $facultyProfileId)
            ->where('evaluation_type', 'peer')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->get()
            ->map(function (FacultyPeerEvaluationFeedback $row): array {
                return [
                    'source' => 'Peer Evaluation',
                    'evaluator' => $row->evaluator?->user?->name ?? 'Peer Faculty',
                    'comment' => trim((string) $row->comment),
                    'performance_level' => $row->performance_level ?: null,
                    'score' => $row->total_average !== null ? (float) $row->total_average : null,
                    'semester' => $row->semester,
                    'school_year' => $row->school_year,
                    'created_at' => $row->created_at,
                ];
            });

        $selfFeedback = SelfEvaluationResult::query()
            ->where('faculty_id', $facultyProfileId)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->get()
            ->map(function (SelfEvaluationResult $row) use ($facultyName): array {
                return [
                    'source' => 'Self Evaluation',
                    'evaluator' => $facultyName,
                    'comment' => trim((string) $row->comment),
                    'performance_level' => $row->performance_level ?: null,
                    'score' => $row->total_average !== null ? (float) $row->total_average : null,
                    'semester' => $row->semester,
                    'school_year' => $row->school_year,
                    'created_at' => $row->created_at,
                ];
            });

        return $studentFeedback
            ->concat($deanFeedback)
            ->concat($peerFeedback)
            ->concat($selfFeedback)
            ->filter(fn (array $row) => $row['comment'] !== '')
            ->sortByDesc(fn (array $row) => $row['created_at'] ?? now())
            ->map(function (array $row) use ($sentimentAnalyzer): array {
                $analyzableSources = ['Student Evaluation', 'Peer Evaluation'];
                $sentiment = in_array($row['source'], $analyzableSources, true)
                    ? $sentimentAnalyzer->analyze($row['comment'])
                    : ['label' => 'neutral', 'score' => 0];
                $row['sentiment'] = $sentiment['label'];
                $row['sentiment_score'] = $sentiment['score'];

                return $row;
            })
            ->values();
    }

    private function authorizeAccess(): void
    {
        Gate::authorize('print-or-generate-comment');
    }

    private function paginateCollection(
        Collection $items,
        int $perPage,
        string $pageName,
        int $currentPage
    ): LengthAwarePaginator {
        $page = max(1, $currentPage);
        $total = $items->count();
        $pageItems = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

    private function buildInsights(Collection $comments): array
    {
        $strengths = $this->topKeywords(
            $comments->where('sentiment', 'positive')->pluck('comment')->all(),
            2
        );
        $issues = $this->topKeywords(
            $comments->where('sentiment', 'negative')->pluck('comment')->all(),
            2
        );

        return [
            'strengths' => $strengths,
            'issues' => $issues,
        ];
    }

    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    private function topKeywords(array $texts, int $limit): array
    {
        if ($texts === []) {
            return [];
        }

        $stopwords = [
            'the', 'and', 'for', 'that', 'this', 'with', 'from', 'your', 'you', 'are',
            'was', 'were', 'have', 'has', 'had', 'their', 'they', 'them', 'very', 'good',
            'great', 'nice', 'well', 'just', 'more', 'less', 'also', 'into', 'about',
            'because', 'during', 'after', 'before', 'when', 'where', 'while', 'there',
            'would', 'could', 'should', 'faculty', 'teacher', 'employee', 'evaluation',
            'class', 'student', 'students', 'dean', 'peer', 'self', 'comment',
        ];
        $stopLookup = array_flip($stopwords);
        $counts = [];

        foreach ($texts as $text) {
            $words = preg_split('/[^a-z0-9]+/i', strtolower($text)) ?: [];
            foreach ($words as $word) {
                if ($word === '' || strlen($word) < 4 || isset($stopLookup[$word])) {
                    continue;
                }
                $counts[$word] = ($counts[$word] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return [];
        }

        arsort($counts);
        $topWords = array_slice(array_keys($counts), 0, $limit);

        return array_map(
            fn (string $word): string => ucwords(str_replace('_', ' ', $word)),
            $topWords
        );
    }
}
