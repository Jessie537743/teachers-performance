<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Services\EvaluationService;
use App\Services\InterventionSuggestionService;
use Illuminate\Http\Request;
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

        return view('admin.faculty-intervention-suggestions', [
            'profile'      => $faculty_profile,
            'periods'      => $periods,
            'schoolYear'   => $schoolYear,
            'semester'     => $semester,
            'analysis'     => $analysis,
            'tierHint'     => $tierHint,
        ]);
    }
}
