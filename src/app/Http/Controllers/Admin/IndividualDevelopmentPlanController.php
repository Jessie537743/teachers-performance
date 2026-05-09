<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Models\IndividualDevelopmentPlan;
use App\Services\EvaluationService;
use App\Services\Idp\IdpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndividualDevelopmentPlanController extends Controller
{
    public function __construct(private readonly IdpService $idp) {}

    /**
     * Show the current IDP for a faculty in a period (or a "no plan yet"
     * landing page with a Generate button if none exists).
     */
    public function show(Request $request, FacultyProfile $faculty_profile): View|RedirectResponse
    {
        $this->authorizeAccess($request, $faculty_profile);

        $faculty_profile->loadMissing(['user', 'department']);

        $periods = EvaluationPeriod::query()
            ->orderByDesc('school_year')
            ->orderByDesc('semester')
            ->get();

        $schoolYear = $request->query('school_year', EvaluationService::getOpenEvaluationPeriod()?->school_year);
        $semester   = $request->query('semester',   EvaluationService::getOpenEvaluationPeriod()?->semester);

        abort_if(
            ! $schoolYear || ! $semester,
            404,
            'Select a school year and semester, or open an evaluation period.',
        );

        $plan = $this->idp->findCurrent($faculty_profile, (string) $semester, (string) $schoolYear);

        return view('admin.idp.show', [
            'profile'    => $faculty_profile,
            'plan'       => $plan,
            'periods'    => $periods,
            'schoolYear' => $schoolYear,
            'semester'   => $semester,
            'engine'     => (string) config('idp.engine', 'local'),
        ]);
    }

    /**
     * Generate (or regenerate) an IDP. Supersedes any prior draft/active
     * plan for the same period.
     */
    public function generate(Request $request, FacultyProfile $faculty_profile): RedirectResponse
    {
        $this->authorizeAccess($request, $faculty_profile, requireWriter: true);

        $data = $request->validate([
            'school_year' => ['required', 'string', 'max:16'],
            'semester'    => ['required', 'string', 'max:32'],
        ]);

        $plan = $this->idp->generate(
            profile:     $faculty_profile,
            semester:    $data['semester'],
            schoolYear:  $data['school_year'],
            generatedBy: $request->user()?->id,
        );

        return redirect()
            ->route('faculty.idp.show', [
                'faculty_profile' => $faculty_profile->id,
                'school_year'     => $plan->school_year,
                'semester'        => $plan->semester,
            ])
            ->with('status', 'Individual Development Plan generated.');
    }

    public function updateStatus(Request $request, IndividualDevelopmentPlan $plan): RedirectResponse
    {
        $this->authorizeAccess($request, $plan->faculty()->firstOrFail(), requireWriter: true);

        $data = $request->validate([
            'status' => ['required', 'in:draft,active,completed'],
        ]);

        $plan->update([
            'status'       => $data['status'],
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ]);

        return back()->with('status', "IDP status updated to {$data['status']}.");
    }

    /**
     * Read access: admin or HR or dean of the same department.
     * Write access (generate/update): same set, since dean of same dept owns
     * the development plans for their faculty.
     */
    private function authorizeAccess(Request $request, FacultyProfile $profile, bool $requireWriter = false): void
    {
        $user = $request->user();

        $isAdminOrHr = $user->can('view-admin-dashboard') || $user->can('view-hr-dashboard');

        $isDeanOfSameDept = $user->can('view-dean-dashboard')
            && (int) $profile->department_id === (int) $user->department_id;

        abort_unless($isAdminOrHr || $isDeanOfSameDept, 403);
    }
}
