<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Models\InterventionPlan;
use App\Services\AiInterventionPlanService;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiInterventionPlanController extends Controller
{
    public function __construct(private readonly AiInterventionPlanService $planner) {}

    /**
     * Show the latest plan for a faculty (auto-generates one if none exists
     * for the requested period and the faculty qualifies for intervention).
     */
    public function show(Request $request, FacultyProfile $faculty_profile): View|RedirectResponse
    {
        $this->authorizeView($request, $faculty_profile);

        $faculty_profile->loadMissing(['user', 'department']);

        $periods = EvaluationPeriod::query()
            ->orderByDesc('school_year')
            ->orderByDesc('semester')
            ->get();

        $schoolYear = $request->query('school_year', EvaluationService::getOpenEvaluationPeriod()?->school_year);
        $semester   = $request->query('semester',   EvaluationService::getOpenEvaluationPeriod()?->semester);

        abort_if(! $schoolYear || ! $semester, 404, 'Select a school year and semester, or open an evaluation period.');

        $plan = InterventionPlan::query()
            ->where('faculty_id', $faculty_profile->id)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereIn('status', ['draft', 'active', 'completed'])
            ->orderByDesc('id')
            ->first();

        return view('admin.ai-intervention-plan', [
            'profile'    => $faculty_profile,
            'plan'       => $plan,
            'periods'    => $periods,
            'schoolYear' => $schoolYear,
            'semester'   => $semester,
        ]);
    }

    /**
     * Generate (or regenerate) an AI plan for a faculty.
     */
    public function generate(Request $request, FacultyProfile $faculty_profile): RedirectResponse
    {
        $this->authorizeView($request, $faculty_profile);

        $data = $request->validate([
            'school_year' => ['required', 'string', 'max:16'],
            'semester'    => ['required', 'string', 'max:32'],
        ]);

        $plan = $this->planner->generatePlan(
            profile:    $faculty_profile,
            semester:   $data['semester'],
            schoolYear: $data['school_year'],
            createdBy:  $request->user()->id,
        );

        return redirect()
            ->route('faculty.ai-intervention-plan.show', [
                'faculty_profile' => $faculty_profile->id,
                'school_year'     => $plan->school_year,
                'semester'        => $plan->semester,
            ])
            ->with('status', 'AI intervention plan generated.');
    }

    public function updateStatus(Request $request, InterventionPlan $plan): RedirectResponse
    {
        $this->authorizeView($request, $plan->faculty()->firstOrFail());

        $data = $request->validate([
            'status' => ['required', 'in:draft,active,completed'],
        ]);

        $plan->update([
            'status'       => $data['status'],
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ]);

        return back()->with('status', "Plan status updated to {$data['status']}.");
    }

    private function authorizeView(Request $request, FacultyProfile $profile): void
    {
        $user = $request->user();
        abort_unless(
            $user->can('view-admin-dashboard')
                || $user->can('view-hr-dashboard')
                || (
                    $user->can('view-dean-dashboard')
                    && (int) $profile->department_id === (int) $user->department_id
                ),
            403
        );
    }
}
