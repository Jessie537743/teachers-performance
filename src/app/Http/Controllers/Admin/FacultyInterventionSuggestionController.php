<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\EvaluationPeriod;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Services\EvaluationService;
use App\Services\InterventionSuggestionService;
use App\Services\TextSentimentAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FacultyInterventionSuggestionController extends Controller
{
    public function show(Request $request, FacultyProfile $faculty_profile): View
    {
        $user = $request->user();
        abort_unless(
            $user->can('view-admin-dashboard')
                || $user->can('view-hr-dashboard')
                || (
                    $user->can('view-dean-dashboard')
                    && (int) $faculty_profile->department_id === (int) $user->department_id
                ),
            403
        );

        $faculty_profile->loadMissing(['user', 'department']);

        $periods = EvaluationPeriod::query()
            ->orderByDesc('school_year')
            ->orderByDesc('semester')
            ->get();

        $schoolYear = $request->query('school_year', EvaluationService::getOpenEvaluationPeriod()?->school_year);
        $semester   = $request->query('semester', EvaluationService::getOpenEvaluationPeriod()?->semester);

        abort_if(! $schoolYear || ! $semester, 404, 'Select a school year and semester, or open an evaluation period.');

        $analysis = app(InterventionSuggestionService::class)->analyze($faculty_profile, $semester, $schoolYear);

        $tierHint = match (true) {
            EvaluationService::isDeanHeadEvaluateePersonnelType($analysis['personnel_type']) => 'Dean/Head: Below Average or Unsatisfactory.',
            EvaluationService::normalizeEvaluateePersonnelForScoring($analysis['personnel_type']) === 'non-teaching' => 'Non-teaching: Below Average or Poor.',
            default => 'Teaching: Fair or Poor.',
        };

        $comments = $this->collectComments($faculty_profile->id, $schoolYear, $semester);

        return view('admin.faculty-intervention-suggestions', [
            'profile'      => $faculty_profile,
            'periods'      => $periods,
            'schoolYear'   => $schoolYear,
            'semester'     => $semester,
            'analysis'     => $analysis,
            'tierHint'     => $tierHint,
            'comments'     => $comments,
        ]);
    }

    /**
     * Pull free-text comments from all four feedback sources for this faculty,
     * tag with sentiment polarity, and return at most the top 12 most-recent
     * non-empty entries grouped by polarity for the AI suggestions panel.
     *
     * @return array{positive: Collection, negative: Collection}
     */
    private function collectComments(int $facultyId, string $schoolYear, string $semester): array
    {
        $sentiment = app(TextSentimentAnalyzer::class);

        $rows = collect();

        EvaluationFeedback::query()
            ->where('faculty_id', $facultyId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->select(['id', 'comment', 'created_at'])
            ->orderByDesc('id')->limit(20)->get()
            ->each(fn ($r) => $rows->push(['source' => 'student', 'comment' => $r->comment, 'created_at' => $r->created_at]));

        DeanEvaluationFeedback::query()
            ->where('faculty_id', $facultyId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->select(['id', 'comment', 'created_at'])
            ->orderByDesc('id')->limit(10)->get()
            ->each(fn ($r) => $rows->push(['source' => 'dean', 'comment' => $r->comment, 'created_at' => $r->created_at]));

        FacultyPeerEvaluationFeedback::query()
            ->where('evaluatee_faculty_id', $facultyId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->select(['id', 'comment', 'evaluation_type', 'created_at'])
            ->orderByDesc('id')->limit(15)->get()
            ->each(fn ($r) => $rows->push([
                'source' => $r->evaluation_type === 'peer' ? 'peer' : 'self',
                'comment' => $r->comment,
                'created_at' => $r->created_at,
            ]));

        $tagged = $rows->map(function ($r) use ($sentiment) {
            $r['polarity'] = $sentiment->analyze($r['comment'])['label'];
            return $r;
        });

        return [
            'negative' => $tagged->where('polarity', 'negative')->take(8)->values(),
            'positive' => $tagged->where('polarity', 'positive')->take(8)->values(),
        ];
    }
}
