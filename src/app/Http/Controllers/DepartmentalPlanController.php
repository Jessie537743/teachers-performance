<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentalPlan;
use App\Models\DepartmentalPlanItem;
use App\Models\EvaluationPeriod;
use App\Services\DepartmentalPlanGenerator;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Departmental Plan.
 *
 *   Department-scoped users (dean / head) are locked to their own department.
 *   Institution-wide users (admin / HR / school_president / VP / vp_acad)
 *   can pick any department via the `department_id` query/form field and
 *   will see/manage every dean's plan for that department.
 *
 *   index            List & view the current period's plan for the chosen dept.
 *   generate         Build a fresh plan from the latest evaluation results
 *                    (archives any prior draft for the same period).
 *   updateStatus     Move plan along the draft → active → completed lifecycle.
 *   updateItemStatus Mark individual action items pending → in_progress → done.
 */
class DepartmentalPlanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('submit-dean-evaluation');
        $user = $request->user();

        $isScoped     = $this->isDepartmentScoped($user);
        $departments  = $isScoped
            ? Department::where('id', $user->department_id)->get()
            : Department::orderBy('name')->get();

        $departmentId = $isScoped
            ? (int) $user->department_id
            : (int) $request->query('department_id', $departments->first()?->id);

        $department = $departmentId
            ? Department::find($departmentId)
            : null;

        $periods = EvaluationPeriod::query()
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $open = EvaluationService::getOpenEvaluationPeriod();
        $schoolYear = $request->query('school_year', $open?->school_year);
        $semester   = $request->query('semester',    $open?->semester);

        $plan = null;
        if ($department && $schoolYear && $semester) {
            $planQuery = DepartmentalPlan::with(['items.facultyProfile.user', 'department', 'dean'])
                ->where('department_id', $department->id)
                ->where('school_year', $schoolYear)
                ->where('semester', $semester)
                ->whereIn('status', ['draft', 'active', 'completed']);

            // Department-scoped users see only their own plan; institution-wide
            // viewers see the latest plan for the chosen dept regardless of dean.
            if ($isScoped) {
                $planQuery->where('dean_user_id', $user->id);
            }

            $plan = $planQuery->latest('id')->first();
        }

        return view('departmental-plan.index', [
            'department'    => $department,
            'departments'   => $departments,
            'periods'       => $periods,
            'schoolYear'    => $schoolYear,
            'semester'      => $semester,
            'plan'          => $plan,
            'canGenerate'   => $department && $schoolYear && $semester,
            'canPickDept'   => !$isScoped,
            'canEditPlan'   => $this->canEditPlan($user, $plan, $isScoped),
        ]);
    }

    public function generate(Request $request, DepartmentalPlanGenerator $generator): RedirectResponse
    {
        Gate::authorize('submit-dean-evaluation');
        $user = $request->user();
        $isScoped = $this->isDepartmentScoped($user);

        $validated = $request->validate([
            'school_year'   => ['required', 'string', 'max:16'],
            'semester'      => ['required', 'string', 'max:32'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $departmentId = $isScoped
            ? (int) $user->department_id
            : (int) ($validated['department_id'] ?? 0);

        if (!$departmentId) {
            return redirect()->route('departmental-plan.index')
                ->with('error', 'Select a department before generating a plan.');
        }

        $department = Department::findOrFail($departmentId);

        $plan = $generator->generate(
            $department,
            $user,
            $validated['school_year'],
            $validated['semester']
        );

        return redirect()->route('departmental-plan.index', [
            'department_id' => $plan->department_id,
            'school_year'   => $plan->school_year,
            'semester'      => $plan->semester,
        ])->with('success', 'Departmental plan generated with ' . $plan->items()->count() . ' action item(s).');
    }

    public function updateStatus(Request $request, DepartmentalPlan $plan): RedirectResponse
    {
        Gate::authorize('submit-dean-evaluation');
        $user = $request->user();
        abort_unless(
            $this->canEditPlan($user, $plan, $this->isDepartmentScoped($user)),
            403,
            'You may only update plans you own (or any plan if you are admin/HR).'
        );

        $validated = $request->validate([
            'status' => ['required', 'in:draft,active,completed,archived'],
            'notes'  => ['nullable', 'string', 'max:5000'],
        ]);

        $plan->status = $validated['status'];
        if (array_key_exists('notes', $validated)) {
            $plan->notes = $validated['notes'];
        }
        if ($validated['status'] === 'completed' && !$plan->completed_at) {
            $plan->completed_at = now();
        }
        $plan->save();

        return redirect()->route('departmental-plan.index', [
            'department_id' => $plan->department_id,
            'school_year'   => $plan->school_year,
            'semester'      => $plan->semester,
        ])->with('success', 'Plan status updated.');
    }

    public function updateItemStatus(Request $request, DepartmentalPlanItem $item): RedirectResponse
    {
        Gate::authorize('submit-dean-evaluation');
        $user = $request->user();
        abort_unless(
            $this->canEditPlan($user, $item->plan, $this->isDepartmentScoped($user)),
            403,
            'You may only update items on plans you own (or any plan if you are admin/HR).'
        );

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $item->status = $validated['status'];
        if (array_key_exists('notes', $validated)) {
            $item->notes = $validated['notes'];
        }
        if ($validated['status'] === 'completed' && !$item->completed_at) {
            $item->completed_at = now();
        }
        $item->save();

        return redirect()->route('departmental-plan.index', [
            'department_id' => $item->plan->department_id,
            'school_year'   => $item->plan->school_year,
            'semester'      => $item->plan->semester,
        ])->with('success', 'Action item updated.');
    }

    /**
     * A user is "department-scoped" when they manage a single department
     * (dean / head). Everyone else with the gate permission — admin, HR,
     * school_president, VPs — can pick any department.
     */
    private function isDepartmentScoped($user): bool
    {
        if (!$user) {
            return false;
        }
        $roles = $user->roles ?? [];
        $isOnlyDeanOrHead = collect($roles)->every(fn ($r) => in_array($r, ['dean', 'head'], true));
        return $isOnlyDeanOrHead && (bool) $user->department_id;
    }

    private function canEditPlan($user, ?DepartmentalPlan $plan, bool $isScoped): bool
    {
        if (!$plan || !$user) {
            return false;
        }
        // Scoped deans/heads can only edit their own plan
        if ($isScoped) {
            return $plan->dean_user_id === $user->id;
        }
        // Institution-wide roles can edit any plan
        return true;
    }
}
