<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LowPerformancePersonnelReportController extends Controller
{
    use LoadsFacultyPerformance;

    public function index(Request $request): View
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
            'semester'      => ['nullable', 'string', 'max:30'],
            'school_year'   => ['nullable', 'string', 'max:30'],
            'print'         => ['nullable', 'boolean'],
            'generate'      => ['nullable', 'boolean'],
        ]);

        $semester = $validated['semester'] ?? null;
        $schoolYear = $validated['school_year'] ?? null;
        if ($semester === '') {
            $semester = null;
        }
        if ($schoolYear === '') {
            $schoolYear = null;
        }

        $departmentId = isset($validated['department_id']) ? (int) $validated['department_id'] : null;
        $selectedDepartment = $departmentId ? Department::query()->whereKey($departmentId)->where('is_active', true)->first() : null;

        $shouldLoad = $request->boolean('generate')
            || $request->filled('semester')
            || $request->filled('school_year')
            || $request->filled('department_id');

        $lowPerformanceRows = collect();
        $allFacultyCount = 0;

        if ($shouldLoad) {
            $allRows = $this->loadFacultyWithPerformance($departmentId, $semester, $schoolYear);
            $allFacultyCount = $allRows->count();

            $lowPerformanceRows = $allRows
                ->filter(function (array $row): bool {
                    $personnelType = $row['profile']->evaluationCriteriaPersonnelType();

                    return EvaluationService::qualifiesForPerformanceIntervention(
                        $row['performance_level'],
                        $personnelType
                    );
                })
                ->sortBy(function (array $row): string {
                    $level = $row['performance_level'] ?? '';
                    $tier = match ($level) {
                        'Poor', 'Unsatisfactory' => '0',
                        'Fair', 'Below Average' => '1',
                        default => '2',
                    };

                    return $tier.'-'.mb_strtolower($row['user']->name ?? '');
                })
                ->values();
        }

        $printMode = (bool) ($validated['print'] ?? false) && $shouldLoad;
        $hasData = $lowPerformanceRows->isNotEmpty();

        return view('admin.low-performance-personnel-report', [
            'departments'          => $departments,
            'periods'              => $periods,
            'openPeriod'           => $openPeriod,
            'selectedDepartment'   => $selectedDepartment,
            'lowPerformanceRows'   => $lowPerformanceRows,
            'allFacultyCount'      => $allFacultyCount,
            'semester'             => $semester,
            'schoolYear'           => $schoolYear,
            'shouldLoad'           => $shouldLoad,
            'printMode'            => $printMode,
            'hasData'              => $hasData,
        ]);
    }
}
