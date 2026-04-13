<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Services\ConsecutiveLowPerformanceService;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChronicLowPerformanceReportController extends Controller
{
    use LoadsFacultyPerformance;

    public function index(Request $request, ConsecutiveLowPerformanceService $analyzer): View
    {
        abort_unless(Gate::any(['generate-report', 'view-generated-report']), 403);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $periods = EvaluationPeriod::query()
            ->select(['semester', 'school_year'])
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $openPeriod = EvaluationService::getOpenEvaluationPeriod();

        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'print'         => ['nullable', 'boolean'],
            'generate'      => ['nullable', 'boolean'],
        ]);

        $departmentId = isset($validated['department_id']) ? (int) $validated['department_id'] : null;
        $selectedDepartment = $departmentId
            ? Department::query()->whereKey($departmentId)->where('is_active', true)->first()
            : null;

        $shouldLoad = $request->boolean('generate') || $request->filled('department_id');

        $streakRows = [];
        $orderedPeriodCount = 0;

        if ($shouldLoad) {
            $orderedPeriodCount = $analyzer->orderedPeriods()->count();
            $streakRows = $analyzer->findThreeConsecutiveLowPerformers(
                fn (string $schoolYear, string $semester) => $this->loadFacultyWithPerformance(
                    $departmentId,
                    $semester,
                    $schoolYear
                )
            );
        }

        $printMode = (bool) ($validated['print'] ?? false) && $shouldLoad;
        $hasData = count($streakRows) > 0;

        return view('admin.chronic-low-performance-report', [
            'departments'          => $departments,
            'periods'              => $periods,
            'openPeriod'           => $openPeriod,
            'selectedDepartment'   => $selectedDepartment,
            'streakRows'           => $streakRows,
            'orderedPeriodCount'   => $orderedPeriodCount,
            'shouldLoad'           => $shouldLoad,
            'printMode'            => $printMode,
            'hasData'              => $hasData,
        ]);
    }
}
