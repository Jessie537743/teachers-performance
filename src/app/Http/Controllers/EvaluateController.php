<?php

namespace App\Http\Controllers;

use App\Enums\FacultyDepartmentPosition;
use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\Criterion;
use App\Models\DeanEvaluationAnswer;
use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationAnswer;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\SelfEvaluationResult;
use App\Models\Subject;
use App\Models\User;
use App\Policies\EvaluationPolicy;
use App\Services\EvaluationService;
use App\Services\StudentEvaluationSubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EvaluateController extends Controller
{
    use LoadsFacultyPerformance;

    /**
     * List evaluations available to the current user.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->can('submit-dean-evaluation') || $user->can('monitor-not-evaluated')) {
            return $this->deanIndex($request);
        }

        if ($user->can('view-admin-dashboard')) {
            return $this->adminExcellentCertificatesIndex($request);
        }

        if ($user->can('submit-self-evaluation') || $user->can('submit-peer-evaluation')) {
            return $this->facultyIndex();
        }

        if ($user->can('submit-student-evaluation')) {
            return $this->studentIndex();
        }

        abort(403, 'You do not have permission to access evaluations.');
    }

    /**
     * Administrators: list personnel at the highest performance tier (Excellent / Outstanding)
     * for the selected period and open printable certificates.
     */
    private function adminExcellentCertificatesIndex(Request $request): View
    {
        Gate::authorize('view-admin-dashboard');

        $period = EvaluationService::getOpenEvaluationPeriod();

        $schoolYear = $request->input('school_year', $period?->school_year);
        $semester   = $request->input('semester', $period?->semester);

        $allRows = $this->loadFacultyWithPerformance(
            departmentId: null,
            semester:     $semester ?: null,
            schoolYear:   $schoolYear ?: null,
        );

        $topTiers = ['Excellent', 'Outstanding'];

        $excellentFaculty = $allRows
            ->filter(function (array $r) use ($topTiers) {
                return $r['weighted_average'] !== null
                    && in_array($r['performance_level'], $topTiers, true);
            })
            ->sortByDesc('weighted_average')
            ->values();

        $hasPeriodFilter = filled($schoolYear) && filled($semester);

        return view('evaluate.admin-excellent-certificates', [
            'excellentFaculty' => $excellentFaculty,
            'schoolYear'       => $schoolYear,
            'semester'         => $semester,
            'period'           => $period,
            'hasPeriodFilter'  => $hasPeriodFilter,
        ]);
    }

    /**
     * Show the evaluation form for a specific type.
     */
    public function show(Request $request, string $type, int $facultyId, ?int $subjectId = null): View
    {
        return match ($type) {
            'dean'    => $this->deanShow($facultyId),
            'student' => $this->studentShow($facultyId, $subjectId),
            'self', 'peer' => $this->facultyShow($facultyId, $type),
            default   => abort(404, 'Invalid evaluation type.'),
        };
    }

    /**
     * Store an evaluation submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        return match ($type) {
            'dean'    => $this->deanStore($request),
            'student' => $this->studentStore($request),
            'self', 'peer' => $this->facultyStore($request),
            default   => abort(404, 'Invalid evaluation type.'),
        };
    }

    // -------------------------------------------------------------------------
    // Dean Evaluation
    // -------------------------------------------------------------------------

    private function deanIndex(Request $request): View
    {
        abort_unless(
            auth()->user()?->can('submit-dean-evaluation') || auth()->user()?->can('monitor-not-evaluated'),
            403,
            'You do not have permission to access dean evaluations.'
        );

        $dean   = auth()->user();
        $period = EvaluationService::getOpenEvaluationPeriod();
        $dean->loadMissing('facultyProfile.department');
        $deanProfile = $dean->facultyProfile;

        $evaluatesInstitutionLeaders = EvaluationService::isInstitutionLeaderDeanEvaluator($dean);

        $isHrWideMonitor = $dean->can('view-hr-dashboard') && $dean->can('monitor-not-evaluated');

        $hasSelfEvaluated = false;
        $canSelfEvaluate = false;
        if ($deanProfile && $period) {
            $hasSelfEvaluated = SelfEvaluationResult::where('faculty_id', $deanProfile->id)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists();

            $canSelfEvaluate = app(EvaluationPolicy::class)->submitSelfEvaluation($dean);
        }

        $facultyStatusCounts = ['all' => 0, 'evaluated' => 0, 'pending' => 0];
        $selectedFacultyStatus = 'all';

        if ($isHrWideMonitor) {
            $facultyUsers = User::with(['facultyProfile.department'])
                ->whereHasRole('faculty')
                ->where('is_active', true)
                ->whereHas('facultyProfile')
                ->orderBy('name')
                ->get();

            $selfCompleteIds = collect();
            $peerCompleteIds = collect();
            $supervisorCompleteIds = collect();

            if ($period && $facultyUsers->isNotEmpty()) {
                $profileIds = $facultyUsers->pluck('facultyProfile.id')->filter()->values();

                $selfCompleteIds = SelfEvaluationResult::query()
                    ->whereIn('faculty_id', $profileIds)
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->pluck('faculty_id')
                    ->unique()
                    ->flip();

                $peerCompleteIds = FacultyPeerEvaluationFeedback::query()
                    ->whereIn('evaluatee_faculty_id', $profileIds)
                    ->where('evaluation_type', 'peer')
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->pluck('evaluatee_faculty_id')
                    ->unique()
                    ->flip();

                $supervisorCompleteIds = DeanEvaluationFeedback::query()
                    ->whereIn('faculty_id', $profileIds)
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->pluck('faculty_id')
                    ->unique()
                    ->flip();
            }

            $faculty = $facultyUsers->map(function (User $user) use ($selfCompleteIds, $peerCompleteIds, $supervisorCompleteIds) {
                $profile = $user->facultyProfile;
                $pid = $profile?->id;

                $hasSelf = $pid !== null && $selfCompleteIds->has($pid);
                $hasPeer = $pid !== null && $peerCompleteIds->has($pid);
                $hasSupervisor = $pid !== null && $supervisorCompleteIds->has($pid);

                return [
                    'user'             => $user,
                    'profile'          => $profile,
                    'has_self'         => $hasSelf,
                    'has_peer'         => $hasPeer,
                    'has_supervisor'   => $hasSupervisor,
                    'has_evaluated'    => $hasSelf && $hasPeer && $hasSupervisor,
                ];
            });

            $faculty = $faculty
                ->sortBy(function (array $item) {
                    $dept = $item['profile']?->department?->name ?? '';

                    return Str::lower($dept).'|'.Str::lower($item['user']->name);
                })
                ->values();

            $facultyStatusCounts = [
                'all'       => $faculty->count(),
                'evaluated' => $faculty->where('has_evaluated', true)->count(),
                'pending'   => $faculty->where('has_evaluated', false)->count(),
            ];

            $selectedFacultyStatus = (string) $request->query('faculty_status', 'all');
            if (! in_array($selectedFacultyStatus, ['all', 'evaluated', 'pending'], true)) {
                $selectedFacultyStatus = 'all';
            }

            $faculty = $faculty->filter(function (array $item) use ($selectedFacultyStatus): bool {
                return match ($selectedFacultyStatus) {
                    'evaluated' => $item['has_evaluated'] === true,
                    'pending'   => $item['has_evaluated'] === false,
                    default     => true,
                };
            })->values();

            $facultyPendingCount = $facultyStatusCounts['pending'];
        } elseif ($evaluatesInstitutionLeaders) {
            // All academic leaders by position: Dean/Head (teaching) and Administrator/Head (non-teaching).
            $facultyUsers = User::with(['facultyProfile.department'])
                ->whereHas('facultyProfile', function ($q) {
                    $q->where('department_position', FacultyDepartmentPosition::DeanHead);
                })
                ->where('is_active', true)
                ->where('id', '!=', $dean->id)
                ->orderBy('name')
                ->get();

            $evaluatedProfileIds = collect();
            if ($period && $facultyUsers->isNotEmpty()) {
                $profileIds = $facultyUsers->pluck('facultyProfile.id')->filter();

                $evaluatedProfileIds = DeanEvaluationFeedback::whereIn('faculty_id', $profileIds)
                    ->where('dean_user_id', $dean->id)
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->pluck('faculty_id')
                    ->flip();
            }

            $faculty = $facultyUsers->map(function (User $user) use ($evaluatedProfileIds) {
                $profile = $user->facultyProfile;

                return [
                    'user'          => $user,
                    'profile'       => $profile,
                    'has_evaluated' => $profile && $evaluatedProfileIds->has($profile->id),
                ];
            });

            $faculty = $faculty
                ->sortBy(function (array $item) {
                    $dept = $item['profile']?->department?->name ?? '';

                    return Str::lower($dept).'|'.Str::lower($item['user']->name);
                })
                ->values();
            $facultyPendingCount = $faculty->where('has_evaluated', false)->count();
        } else {
            $facultyUsers = User::with('facultyProfile')
                ->whereHasRole('faculty')
                ->where('department_id', $dean->department_id)
                ->where('id', '!=', $dean->id)
                ->where('is_active', true)
                ->get();

            $evaluatedProfileIds = collect();
            if ($period && $facultyUsers->isNotEmpty()) {
                $profileIds = $facultyUsers->pluck('facultyProfile.id')->filter();

                $evaluatedProfileIds = DeanEvaluationFeedback::whereIn('faculty_id', $profileIds)
                    ->where('dean_user_id', $dean->id)
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->pluck('faculty_id')
                    ->flip();
            }

            $faculty = $facultyUsers->map(function (User $user) use ($evaluatedProfileIds) {
                $profile = $user->facultyProfile;

                return [
                    'user'          => $user,
                    'profile'       => $profile,
                    'has_evaluated' => $profile && $evaluatedProfileIds->has($profile->id),
                ];
            });

            $faculty = $faculty->filter(fn (array $item) => $item['has_evaluated'] === false)->values();
            $facultyPendingCount = $faculty->count();
        }

        $selectedStudentStatus = (string) $request->query('student_status', 'non_evaluative');
        if (! in_array($selectedStudentStatus, ['evaluated', 'non_evaluative'], true)) {
            $selectedStudentStatus = 'non_evaluative';
        }

        if ($evaluatesInstitutionLeaders || $isHrWideMonitor) {
            $studentItems = collect();
            $studentStatusCounts = ['evaluated' => 0, 'non_evaluative' => 0];
        } else {
            $studentUsers = User::with(['studentProfile.department', 'department'])
                ->whereHasRole('student')
                ->where('department_id', $dean->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $studentSubmissionCounts = collect();
            if ($period && $studentUsers->isNotEmpty()) {
                $studentIds = $studentUsers->pluck('id')->values()->all();

                $studentSubmissionCounts = EvaluationFeedback::query()
                    ->select('student_id', DB::raw('COUNT(*) as total_submissions'))
                    ->whereIn('student_id', $studentIds)
                    ->where('evaluator_type', 'student')
                    ->where('semester', $period->semester)
                    ->where('school_year', $period->school_year)
                    ->groupBy('student_id')
                    ->pluck('total_submissions', 'student_id');
            }

            $allStudentItems = $studentUsers->map(function (User $student) use ($studentSubmissionCounts) {
                $submissionCount = (int) ($studentSubmissionCounts->get($student->id) ?? 0);
                $profile = $student->studentProfile;

                $departmentName = $profile?->department?->name
                    ?? $student->department?->name
                    ?? '-';

                return [
                    'user'             => $student,
                    'profile'          => $profile,
                    'department_name'  => $departmentName,
                    'submission_count' => $submissionCount,
                    'has_evaluated'    => $submissionCount > 0,
                ];
            })->values();

            $studentStatusCounts = [
                'evaluated'      => $allStudentItems->where('has_evaluated', true)->count(),
                'non_evaluative' => $allStudentItems->where('has_evaluated', false)->count(),
            ];

            $studentItems = $allStudentItems->filter(function (array $item) use ($selectedStudentStatus): bool {
                if ($selectedStudentStatus === 'evaluated') {
                    return $item['has_evaluated'] === true;
                }

                return $item['has_evaluated'] === false;
            })->values();
        }

        return view('evaluate.dean-index', compact(
            'faculty',
            'facultyPendingCount',
            'period',
            'dean',
            'deanProfile',
            'hasSelfEvaluated',
            'canSelfEvaluate',
            'studentItems',
            'selectedStudentStatus',
            'studentStatusCounts',
            'evaluatesInstitutionLeaders',
            'isHrWideMonitor',
            'facultyStatusCounts',
            'selectedFacultyStatus'
        ));
    }

    private function deanShow(int $facultyId): View
    {
        $dean        = auth()->user();
        $period      = EvaluationService::getOpenEvaluationPeriod();
        $facultyUser = User::with('facultyProfile.department')->findOrFail($facultyId);
        $profile     = $facultyUser->facultyProfile;

        abort_if(!$profile, 404, 'Faculty profile not found.');

        $policy = app(EvaluationPolicy::class);
        abort_unless(
            $policy->submitDeanEvaluation($dean, $profile),
            403,
            'Cannot evaluate: either already evaluated, period closed, or faculty not in your department.'
        );

        if ($period) {
            $alreadyDone = DeanEvaluationFeedback::where('faculty_id', $profile->id)
                ->where('dean_user_id', $dean->id)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists();

            if ($alreadyDone) {
                return redirect()->route('evaluate.index')
                    ->with('info', 'You have already evaluated this faculty member for the current period.');
            }
        }

        $personnelType = $profile->evaluationCriteriaPersonnelType();

        $criteria = Criterion::with(['questions.criterion'])
            ->forEvaluatorGroup('dean')
            ->forPersonnelType($personnelType)
            ->orderBy('name')
            ->get();

        $deanRecommendationQuestions = collect();
        foreach ($criteria as $criterion) {
            foreach ($criterion->questions as $q) {
                if (($q->response_type ?? 'likert') === 'dean_recommendation') {
                    $deanRecommendationQuestions->push($q);
                }
            }
        }
        if ($deanRecommendationQuestions->isEmpty()) {
            $fallback = Question::query()
                ->with('criterion')
                ->where('response_type', 'dean_recommendation')
                ->whereHas(
                    'criterion',
                    fn ($q) => $q->forEvaluatorGroup('dean')->forPersonnelType($personnelType)
                )
                ->orderBy('id')
                ->get();
            $deanRecommendationQuestions = $fallback;
        }

        // Keep exactly one recommendation question for academic administrators.
        $deanRecommendationQuestions = $deanRecommendationQuestions
            ->sortBy('id')
            ->take(1)
            ->values();

        return view('evaluate.dean-show', compact(
            'facultyUser',
            'profile',
            'criteria',
            'period',
            'dean',
            'personnelType',
            'deanRecommendationQuestions'
        ));
    }

    private function deanStore(Request $request): RedirectResponse
    {
        $dean   = auth()->user();
        $period = EvaluationService::getOpenEvaluationPeriod();

        abort_if(!$period, 403, 'No evaluation period is currently open.');

        $validated = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculty_profiles,id'],
            'ratings'    => ['required', 'array'],
            'comment'    => ['nullable', 'string', 'max:2000'],
            'recommendation_choice' => ['nullable', 'integer', 'in:1,2,3'],
        ]);

        $facultyId = $validated['faculty_id'];

        $facultyProfile = FacultyProfile::with('department')->findOrFail($facultyId);
        $policy = app(EvaluationPolicy::class);
        abort_unless(
            $policy->submitDeanEvaluation($dean, $facultyProfile),
            403,
            'Cannot evaluate: either already evaluated, period closed, or faculty not in your department.'
        );

        $alreadyDone = DeanEvaluationFeedback::where('faculty_id', $facultyId)
            ->where('dean_user_id', $dean->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        if ($alreadyDone) {
            return redirect()->route('evaluate.index')
                ->with('info', 'You have already submitted this evaluation.');
        }

        $evaluateePersonnel = $facultyProfile->evaluationCriteriaPersonnelType();

        $deanQuestionIds = Question::query()
            ->whereHas(
                'criterion',
                fn ($q) => $q->forEvaluatorGroup('dean')->forPersonnelType($evaluateePersonnel)
            )
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $deanQuestionIds = $this->keepSingleDeanRecommendationQuestion($deanQuestionIds);

        $norm = [];
        foreach ($validated['ratings'] as $k => $v) {
            $norm[(int) $k] = (int) $v;
        }

        foreach ($deanQuestionIds as $qid) {
            if (! array_key_exists($qid, $norm)) {
                return redirect()->back()
                    ->withErrors(['ratings' => 'Please answer all questions.'])
                    ->withInput();
            }
        }

        if (count($norm) !== count($deanQuestionIds)) {
            return redirect()->back()
                ->withErrors(['ratings' => 'Invalid or duplicate question responses.'])
                ->withInput();
        }

        $questionsById = Question::whereIn('id', $deanQuestionIds)->get()->keyBy('id');

        $recommendation = null;
        $likertSum      = 0;
        $likertCount    = 0;

        foreach ($deanQuestionIds as $qid) {
            $val = $norm[$qid];
            $question = $questionsById->get($qid);
            if (! $question) {
                return redirect()->back()
                    ->withErrors(['ratings' => 'Invalid question.'])
                    ->withInput();
            }

            $type = $question->response_type ?? 'likert';

            if ($type === 'dean_recommendation') {
                if ($val < 1 || $val > 3) {
                    return redirect()->back()
                        ->withErrors(['ratings' => 'Select a valid recommendation.'])
                        ->withInput();
                }
                $recommendation = match ($val) {
                    1       => 'retention',
                    2       => 'promotion',
                    default => 'reassignment',
                };
            } else {
                if ($val < 1 || $val > 5) {
                    return redirect()->back()
                        ->withErrors(['ratings' => 'Ratings must be between 1 and 5.'])
                        ->withInput();
                }
                $likertSum += $val;
                $likertCount++;
            }
        }

        if ($recommendation === null && isset($validated['recommendation_choice'])) {
            $recommendation = match ((int) $validated['recommendation_choice']) {
                1       => 'retention',
                2       => 'promotion',
                default => 'reassignment',
            };
        }

        if (EvaluationService::isDeanHeadEvaluateePersonnelType($evaluateePersonnel) && $recommendation === null) {
            return redirect()->back()
                ->withErrors(['ratings' => 'Please select a recommendation for this academic administrator.'])
                ->withInput();
        }

        $avg = $likertCount > 0 ? round($likertSum / $likertCount, 2) : 0;
        $level = EvaluationService::getPerformanceLevel($avg, $evaluateePersonnel);

        DB::transaction(function () use ($norm, $validated, $dean, $period, $facultyId, $avg, $level, $recommendation) {
            foreach ($norm as $questionId => $rating) {
                $question = Question::find($questionId);
                if (! $question) {
                    continue;
                }

                DeanEvaluationAnswer::create([
                    'dean_user_id' => $dean->id,
                    'faculty_id'   => $facultyId,
                    'criteria_id'  => $question->criteria_id,
                    'question_id'  => $questionId,
                    'rating'       => $rating,
                    'semester'     => $period->semester,
                    'school_year'  => $period->school_year,
                    'created_at'   => now(),
                ]);
            }

            DeanEvaluationFeedback::create([
                'dean_user_id'        => $dean->id,
                'faculty_id'          => $facultyId,
                'semester'            => $period->semester,
                'school_year'         => $period->school_year,
                'comment'             => $validated['comment'] ?? null,
                'recommendation'      => $recommendation,
                'total_average'       => $avg,
                'performance_level'   => $level,
                'weighted_percentage' => round($avg * 0.40, 2),
                'created_at'          => now(),
            ]);
        });

        AuditLog::log('submitted', "Evaluation submitted (dean) for faculty #{$facultyId}", null);

        return redirect()->route('evaluate.index')
            ->with('success', 'Evaluation submitted successfully.');
    }

    // -------------------------------------------------------------------------
    // Faculty (Self & Peer) Evaluation
    // -------------------------------------------------------------------------

    private function facultyIndex(): View
    {
        $faculty = auth()->user();
        $period  = EvaluationService::getOpenEvaluationPeriod();
        $faculty->loadMissing('facultyProfile.department');
        $profile = $faculty->facultyProfile;

        abort_if(!$profile, 404, 'Faculty profile not found.');

        $hasSelfEvaluated = $period
            ? SelfEvaluationResult::where('faculty_id', $profile->id)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists()
            : false;

        $peerUsers = User::with('facultyProfile.department')
            ->whereHasRole('faculty')
            ->where('department_id', $faculty->department_id)
            ->where('id', '!=', $faculty->id)
            ->where('is_active', true)
            ->get();

        $evaluatedPeerProfileIds = collect();
        if ($period && $peerUsers->isNotEmpty()) {
            $peerProfileIds = $peerUsers->pluck('facultyProfile.id')->filter();

            $evaluatedPeerProfileIds = FacultyPeerEvaluationFeedback::whereIn('evaluatee_faculty_id', $peerProfileIds)
                ->where('evaluator_faculty_id', $profile->id)
                ->where('evaluation_type', 'peer')
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->pluck('evaluatee_faculty_id')
                ->flip();
        }

        $peers = $peerUsers->map(function (User $peer) use ($evaluatedPeerProfileIds) {
            $peerProfile = $peer->facultyProfile;

            return [
                'user'               => $peer,
                'profile'            => $peerProfile,
                'has_peer_evaluated' => $peerProfile && $evaluatedPeerProfileIds->has($peerProfile->id),
            ];
        });

        return view('evaluate.faculty-index', compact(
            'faculty',
            'profile',
            'period',
            'hasSelfEvaluated',
            'peers'
        ));
    }

    private function facultyShow(int $facultyId, string $type): View
    {
        $evaluator = auth()->user();
        $period    = EvaluationService::getOpenEvaluationPeriod();

        abort_if(!$period, 403, 'Evaluation is not currently open.');

        $evaluator->loadMissing('facultyProfile.department');
        $evaluatorProfile = $evaluator->facultyProfile;
        abort_if(!$evaluatorProfile, 404, 'Your faculty profile was not found.');

        $targetUser    = User::findOrFail($facultyId);
        $targetProfile = FacultyProfile::with('department')->where('user_id', $facultyId)->firstOrFail();

        $policy = app(EvaluationPolicy::class);

        if ($type === 'self') {
            abort_if($evaluator->id !== $facultyId, 403, 'You may only perform your own self-evaluation.');

            abort_unless(
                $policy->submitSelfEvaluation($evaluator),
                403,
                'Cannot self-evaluate: already done or period closed.'
            );

            $alreadyDone = SelfEvaluationResult::where('faculty_id', $evaluatorProfile->id)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists();

            if ($alreadyDone) {
                return redirect()->route('evaluate.index')
                    ->with('info', 'You have already completed your self-evaluation for this period.');
            }
        }

        if ($type === 'peer') {
            abort_unless(
                $policy->submitPeerEvaluation($evaluator, $targetProfile),
                403,
                'Cannot peer-evaluate: same person, different department, already done, or period closed.'
            );

            $alreadyDone = FacultyPeerEvaluationFeedback::where('evaluator_faculty_id', $evaluatorProfile->id)
                ->where('evaluatee_faculty_id', $targetProfile->id)
                ->where('evaluation_type', 'peer')
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists();

            if ($alreadyDone) {
                return redirect()->route('evaluate.index')
                    ->with('info', 'You have already evaluated this peer for the current period.');
            }
        }

        $evaluatorGroup = $type === 'self' ? 'self' : 'peer';

        $personnelType = $type === 'self'
            ? $evaluatorProfile->evaluationCriteriaPersonnelType()
            : $targetProfile->evaluationCriteriaPersonnelType();

        $criteria = Criterion::with(['questions.criterion'])
            ->forEvaluatorGroup($evaluatorGroup)
            ->forPersonnelType($personnelType)
            ->orderBy('name')
            ->get();

        $deanRecommendationQuestions = collect();
        $loadAcademicAdministratorRecommendation = $type === 'self'
            || EvaluationService::isDeanHeadEvaluateePersonnelType($personnelType);

        if ($loadAcademicAdministratorRecommendation) {
            foreach ($criteria as $criterion) {
                foreach ($criterion->questions as $q) {
                    if (($q->response_type ?? 'likert') === 'dean_recommendation') {
                        $deanRecommendationQuestions->push($q);
                    }
                }
            }
            if ($deanRecommendationQuestions->isEmpty()) {
                $deanRecommendationQuestions = Question::query()
                    ->with('criterion')
                    ->where('response_type', 'dean_recommendation')
                    ->whereHas(
                        'criterion',
                        fn ($q) => $q->forEvaluatorGroup($evaluatorGroup)->forPersonnelType($personnelType)
                    )
                    ->orderBy('id')
                    ->get();
            }
            $deanRecommendationQuestions = $deanRecommendationQuestions
                ->sortBy('id')
                ->take(1)
                ->values();
        }

        return view('evaluate.faculty-show', compact(
            'targetUser',
            'targetProfile',
            'evaluatorProfile',
            'criteria',
            'period',
            'type',
            'personnelType',
            'deanRecommendationQuestions',
        ));
    }

    private function facultyStore(Request $request): RedirectResponse
    {
        $evaluator = auth()->user();
        $period    = EvaluationService::getOpenEvaluationPeriod();

        abort_if(!$period, 403, 'Evaluation is not currently open.');

        $validated = $request->validate([
            'evaluatee_faculty_id' => ['required', 'integer', 'exists:faculty_profiles,id'],
            'type'                 => ['required', 'string', 'in:self,peer'],
            'ratings'              => ['required', 'array'],
            'comment'              => ['nullable', 'string', 'max:2000'],
        ]);

        $evaluator->loadMissing('facultyProfile.department');
        $evaluatorProfile = $evaluator->facultyProfile;
        abort_if(!$evaluatorProfile, 404, 'Your faculty profile was not found.');

        $evaluateeId    = $validated['evaluatee_faculty_id'];
        $type           = $validated['type'];
        $evaluateeProfile = FacultyProfile::with('department')->findOrFail($evaluateeId);

        $policy = app(EvaluationPolicy::class);
        if ($type === 'self') {
            abort_unless(
                $policy->submitSelfEvaluation($evaluator),
                403,
                'Cannot self-evaluate: already done or period closed.'
            );
        } else {
            abort_unless(
                $policy->submitPeerEvaluation($evaluator, $evaluateeProfile),
                403,
                'Cannot peer-evaluate: same person, different department, already done, or period closed.'
            );
        }

        $evaluatorGroup = $type === 'self' ? 'self' : 'peer';
        $evaluateePersonnel = $type === 'self'
            ? $evaluatorProfile->evaluationCriteriaPersonnelType()
            : $evaluateeProfile->evaluationCriteriaPersonnelType();

        $questionIds = Question::query()
            ->whereHas(
                'criterion',
                fn ($q) => $q->forEvaluatorGroup($evaluatorGroup)->forPersonnelType($evaluateePersonnel)
            )
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $questionIds = $this->keepSingleDeanRecommendationQuestion($questionIds);

        // Non–Dean/Head peer evaluations: Likert only (no academic-administrator recommendation).
        // Self-evaluations for teaching/non-teaching include Section E when configured in criteria.
        if (! EvaluationService::isDeanHeadEvaluateePersonnelType($evaluateePersonnel) && $type === 'peer') {
            $questionIds = Question::query()
                ->whereIn('id', $questionIds)
                ->where(function ($query) {
                    $query->whereNull('response_type')
                        ->orWhere('response_type', 'likert');
                })
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $norm = [];
        foreach ($validated['ratings'] as $k => $v) {
            $norm[(int) $k] = (int) $v;
        }

        foreach ($questionIds as $qid) {
            if (! array_key_exists($qid, $norm)) {
                return redirect()->back()
                    ->withErrors(['ratings' => 'Please answer all questions.'])
                    ->withInput();
            }
        }

        if (count($norm) !== count($questionIds)) {
            return redirect()->back()
                ->withErrors(['ratings' => 'Invalid or duplicate question responses.'])
                ->withInput();
        }

        $questionsById = Question::whereIn('id', $questionIds)->get()->keyBy('id');
        $likertSum = 0;
        $likertCount = 0;

        foreach ($questionIds as $qid) {
            $val = $norm[$qid];
            $question = $questionsById->get($qid);
            if (! $question) {
                return redirect()->back()
                    ->withErrors(['ratings' => 'Invalid question.'])
                    ->withInput();
            }

            $responseType = $question->response_type ?? 'likert';
            if ($responseType === 'dean_recommendation') {
                if ($val < 1 || $val > 3) {
                    return redirect()->back()
                        ->withErrors(['ratings' => 'Select a valid recommendation.'])
                        ->withInput();
                }
            } else {
                if ($val < 1 || $val > 5) {
                    return redirect()->back()
                        ->withErrors(['ratings' => 'Ratings must be between 1 and 5.'])
                        ->withInput();
                }
                $likertSum += $val;
                $likertCount++;
            }
        }

        $avg = $likertCount > 0 ? round($likertSum / $likertCount, 2) : 0;
        $level = EvaluationService::getPerformanceLevel($avg, $evaluateePersonnel);

        DB::transaction(function () use ($norm, $validated, $evaluator, $evaluatorProfile, $evaluateeProfile, $period, $evaluateeId, $type, $avg, $level, $evaluateePersonnel) {
            foreach ($norm as $questionId => $rating) {
                $question = Question::find($questionId);
                if (! $question) {
                    continue;
                }

                FacultyPeerEvaluationAnswer::create([
                    'evaluator_faculty_id' => $evaluatorProfile->id,
                    'evaluatee_faculty_id' => $evaluateeId,
                    'evaluation_type'      => $type,
                    'criteria_id'          => $question->criteria_id,
                    'question_id'          => $questionId,
                    'rating'               => $rating,
                    'semester'             => $period->semester,
                    'school_year'          => $period->school_year,
                    'created_at'           => now(),
                ]);
            }

            if ($type === 'self') {
                SelfEvaluationResult::create([
                    'faculty_id'        => $evaluatorProfile->id,
                    'department_id'     => $evaluator->department_id,
                    'semester'          => $period->semester,
                    'school_year'       => $period->school_year,
                    'total_average'     => $avg,
                    'performance_level' => $level,
                    'comment'           => EvaluationService::isDeanHeadEvaluateePersonnelType($evaluateePersonnel)
                        ? ($validated['comment'] ?? null)
                        : null,
                    'created_at'        => now(),
                ]);
            } else {
                FacultyPeerEvaluationFeedback::create([
                    'evaluator_faculty_id' => $evaluatorProfile->id,
                    'evaluatee_faculty_id' => $evaluateeId,
                    'evaluation_type'      => 'peer',
                    'semester'             => $period->semester,
                    'school_year'          => $period->school_year,
                    'comment'             => $validated['comment'] ?? null,
                    'total_average'        => $avg,
                    'performance_level'    => $level,
                    'weighted_percentage'  => round($avg * 0.10, 2),
                    'created_at'           => now(),
                ]);
            }
        });

        AuditLog::log('submitted', "Evaluation submitted ({$type}) for faculty #{$evaluateeId}", null);

        return redirect()->route('evaluate.index')
            ->with('success', 'Evaluation submitted successfully.');
    }

    // -------------------------------------------------------------------------
    // Student Evaluation
    // -------------------------------------------------------------------------

    private function studentIndex(): View
    {
        Gate::authorize('submit-student-evaluation');

        $student = auth()->user();
        $period  = EvaluationService::getOpenEvaluationPeriod();
        $profile = $student->studentProfile;

        if (! $profile) {
            return view('evaluate.student-index', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
                'profile'      => null,
            ]);
        }

        $subjectItems = StudentEvaluationSubjectService::buildSubjectItemsForStudent($student, $profile, $period);

        return view('evaluate.student-index', compact('subjectItems', 'period', 'student', 'profile'));
    }

    /**
     * @param  list<int>  $questionIds
     * @return list<int>
     */
    private function keepSingleDeanRecommendationQuestion(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }

        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->select(['id', 'response_type'])
            ->orderBy('id')
            ->get();

        $firstRecommendationId = $questions
            ->first(fn (Question $question) => ($question->response_type ?? 'likert') === 'dean_recommendation')
            ?->id;

        $filtered = $questions
            ->filter(function (Question $question) use ($firstRecommendationId): bool {
                $responseType = $question->response_type ?? 'likert';
                if ($responseType !== 'dean_recommendation') {
                    return true;
                }

                return $firstRecommendationId !== null && $question->id === $firstRecommendationId;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return $filtered;
    }

    private function studentShow(int $facultyId, ?int $subjectId): View
    {
        abort_if(!$subjectId, 404, 'Subject ID is required.');

        $student = auth()->user();
        $period  = EvaluationService::getOpenEvaluationPeriod();

        $facultyUser    = User::findOrFail($facultyId);
        $facultyProfile = FacultyProfile::with('department')->where('user_id', $facultyId)->firstOrFail();
        $subject        = Subject::findOrFail($subjectId);

        $policy = app(EvaluationPolicy::class);
        abort_unless(
            $policy->submitStudentEvaluation($student, $facultyProfile, $subject),
            403,
            'Cannot evaluate: not enrolled, already submitted, or evaluation period closed.'
        );

        if ($period) {
            $alreadyDone = EvaluationFeedback::where('student_id', $student->id)
                ->where('faculty_id', $facultyProfile->id)
                ->where('subject_id', $subjectId)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->exists();

            if ($alreadyDone) {
                return redirect()->route('dashboard')
                    ->with('info', 'You have already evaluated this faculty for this subject.');
            }
        }

        $personnelType = $facultyProfile->evaluationCriteriaPersonnelType();

        $criteria = Criterion::with('questions')
            ->forEvaluatorGroup('student')
            ->forPersonnelType($personnelType)
            ->orderBy('name')
            ->get();

        return view('evaluate.student-show', compact(
            'facultyUser',
            'facultyProfile',
            'subject',
            'criteria',
            'period',
            'student',
            'personnelType'
        ));
    }

    private function studentStore(Request $request): RedirectResponse
    {
        $student = auth()->user();
        $period  = EvaluationService::getOpenEvaluationPeriod();

        abort_if(!$period, 403, 'Evaluation is not currently open.');

        $validated = $request->validate([
            'faculty_id'   => ['required', 'integer', 'exists:faculty_profiles,id'],
            'subject_id'   => ['required', 'integer', 'exists:subjects,id'],
            'ratings'      => ['required', 'array'],
            'ratings.*'    => ['required', 'integer', 'min:1', 'max:5'],
            'comment'      => ['nullable', 'string', 'max:2000'],
        ]);

        $facultyId = $validated['faculty_id'];
        $subjectId = $validated['subject_id'];

        $facultyProfile = FacultyProfile::with('department')->findOrFail($facultyId);
        $subject        = Subject::findOrFail($subjectId);
        $policy         = app(EvaluationPolicy::class);
        abort_unless(
            $policy->submitStudentEvaluation($student, $facultyProfile, $subject),
            403,
            'Cannot evaluate: not enrolled, already submitted, or evaluation period closed.'
        );

        $alreadyDone = EvaluationFeedback::where('student_id', $student->id)
            ->where('faculty_id', $facultyId)
            ->where('subject_id', $subjectId)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        if ($alreadyDone) {
            return redirect()->route('dashboard')
                ->with('info', 'Evaluation already submitted.');
        }

        DB::transaction(function () use ($validated, $student, $period, $facultyId, $subjectId, $facultyProfile) {
            $ratings = $validated['ratings'];

            foreach ($ratings as $questionId => $rating) {
                EvaluationAnswer::create([
                    'student_id'  => $student->id,
                    'faculty_id'  => $facultyId,
                    'subject_id'  => $subjectId,
                    'question_id' => $questionId,
                    'rating'      => $rating,
                    'created_at'  => now(),
                ]);
            }

            $avg   = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
            $evaluateePersonnel = $facultyProfile->evaluationCriteriaPersonnelType();
            $level = EvaluationService::getPerformanceLevel($avg, $evaluateePersonnel);

            EvaluationFeedback::create([
                'student_id'        => $student->id,
                'faculty_id'        => $facultyId,
                'subject_id'        => $subjectId,
                'evaluator_type'    => 'student',
                'school_year'       => $period->school_year,
                'semester'          => $period->semester,
                'comment'           => $validated['comment'] ?? null,
                'total_average'     => $avg,
                'performance_level' => $level,
                'created_at'        => now(),
            ]);
        });

        AuditLog::log('submitted', "Evaluation submitted (student) for faculty #{$facultyId}", null);

        return redirect()->route('evaluate.index')
            ->with('success', 'Evaluation submitted successfully. Thank you!');
    }
}
