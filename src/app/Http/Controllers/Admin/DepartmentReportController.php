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

class DepartmentReportController extends Controller
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

        $rules = [
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'semester'      => ['nullable', 'string', 'max:30'],
            'school_year'   => ['nullable', 'string', 'max:30'],
            'print'         => ['nullable', 'boolean'],
            'generate'      => ['nullable', 'boolean'],
        ];
        if ($request->boolean('generate')) {
            $rules['department_id'] = ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)];
        }

        $validated = $request->validate($rules);

        $semester = $validated['semester'] ?? null;
        $schoolYear = $validated['school_year'] ?? null;
        if ($semester === '') {
            $semester = null;
        }
        if ($schoolYear === '') {
            $schoolYear = null;
        }

        $departmentId = isset($validated['department_id']) ? (int) $validated['department_id'] : null;

        $selectedDepartment = null;
        $facultyRows = collect();
        $departmentAvg = null;
        $excellentCount = 0;

        if ($departmentId !== null) {
            $selectedDepartment = Department::query()->whereKey($departmentId)->where('is_active', true)->first();
            if ($selectedDepartment) {
                $facultyRows = $this->loadFacultyWithPerformance($departmentId, $semester, $schoolYear);
                $vals = $facultyRows->pluck('weighted_average')->filter(static fn ($v) => $v !== null);
                $departmentAvg = $vals->isNotEmpty() ? round((float) $vals->avg(), 2) : null;
                $excellentCount = $facultyRows->where('performance_level', 'Excellent')->count();
            }
        }

        $printMode = (bool) ($validated['print'] ?? false) && $selectedDepartment !== null;
        $hasData = $facultyRows->isNotEmpty();

        return view('admin.department-report', [
            'departments'          => $departments,
            'periods'              => $periods,
            'openPeriod'           => $openPeriod,
            'selectedDepartment'   => $selectedDepartment,
            'facultyRows'          => $facultyRows,
            'departmentAvg'        => $departmentAvg,
            'excellentCount'       => $excellentCount,
            'semester'             => $semester,
            'schoolYear'           => $schoolYear,
            'printMode'            => $printMode,
            'hasData'              => $hasData,
        ]);
    }
}
