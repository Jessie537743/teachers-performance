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
            ->with('user')
            ->whereHas('user', fn ($q) => $q->where('role', 'faculty')->where('is_active', true))
            ->get()
            ->sortBy(fn (FacultyProfile $profile) => strtolower($profile->user?->name ?? ''))
            ->values();

        $periods = EvaluationPeriod::query()
            ->select(['semester', 'school_year'])
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $validated = $request->validate([
            'faculty_profile_id' => ['nullable', 'integer', 'exists:faculty_profiles,id'],
            'semester' => ['nullable', 'string', 'max:20'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'print' => ['nullable', 'boolean'],
        ]);

        $selectedFaculty = null;
        $comments = collect();
        $commentsByLevel = collect();
        $sourceCounts = collect();
        $sentimentCounts = collect();

        if (!empty($validated['faculty_profile_id'])) {
            $selectedFaculty = FacultyProfile::with(['user', 'department'])->find($validated['faculty_profile_id']);

            if ($selectedFaculty) {
                $comments = $this->loadCommentsForFaculty(
                    facultyProfileId: $selectedFaculty->id,
                    semester: $validated['semester'] ?? null,
                    schoolYear: $validated['school_year'] ?? null,
                    facultyName: $selectedFaculty->user?->name ?? 'Faculty Member',
                    sentimentAnalyzer: $sentimentAnalyzer,
                );

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
            }
        }

        return view('admin.employee-comments-report', [
            'facultyOptions' => $facultyOptions,
            'periods' => $periods,
            'selectedFaculty' => $selectedFaculty,
            'selectedSemester' => $validated['semester'] ?? null,
            'selectedSchoolYear' => $validated['school_year'] ?? null,
            'comments' => $comments,
            'commentsByLevel' => $commentsByLevel,
            'sourceCounts' => $sourceCounts,
            'sentimentCounts' => $sentimentCounts,
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
}
