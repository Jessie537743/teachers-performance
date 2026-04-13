<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\DeanEvaluationFeedback;
use App\Models\Department;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\StudentEvaluationSubjectService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use LoadsFacultyPerformance;

    public function index(): View|\Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();

            return match (true) {
                $user->can('view-admin-dashboard')   => $this->adminDashboard(),
                $user->can('view-dean-dashboard')    => $this->deanDashboard(),
                $user->can('view-student-dashboard') => $this->studentDashboard(),
                $user->can('view-hr-dashboard')      => $this->hrDashboard(),
                $user->can('view-faculty-dashboard') => $this->facultyDashboard(),
                default                              => $this->defaultDashboard(),
            };
        } catch (\Throwable $e) {
            // Temporary debug — remove after diagnosing
            return response()->json([
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => collect($e->getTrace())->take(8)->map(fn($t) => [
                    'file'     => $t['file'] ?? '?',
                    'line'     => $t['line'] ?? '?',
                    'function' => ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? ''),
                ]),
            ], 500);
        }
    }

    private function adminDashboard(): View
    {
        $period = EvaluationService::getOpenEvaluationPeriod();

        $allFacultyData = $this->loadFacultyWithPerformance(
            semester:   $period?->semester,
            schoolYear: $period?->school_year
        );

        $departments   = Department::withCount(['facultyProfiles', 'studentProfiles'])->get();
        $totalFaculty  = User::where('role', 'faculty')->where('is_active', true)->count();
        $totalStudents = User::where('role', 'student')->where('is_active', true)->count();

        $page    = request()->input('page', 1);
        $perPage = 25;
        $facultyData = new LengthAwarePaginator(
            $allFacultyData->forPage($page, $perPage)->values(),
            $allFacultyData->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('dashboard.admin', compact(
            'facultyData',
            'allFacultyData',
            'departments',
            'totalFaculty',
            'totalStudents',
            'period'
        ));
    }

    private function deanDashboard(): View
    {
        $dean       = auth()->user();
        $period     = EvaluationService::getOpenEvaluationPeriod();
        $semester   = $period?->semester;
        $schoolYear = $period?->school_year;

        $allFacultyData = $this->loadFacultyWithPerformance(
            departmentId: $dean->department_id,
            semester:     $semester,
            schoolYear:   $schoolYear,
            deanUserId:   $dean->id
        );

        $evaluatedProfileIds = collect();
        if ($period && $allFacultyData->isNotEmpty()) {
            $profileIds = $allFacultyData->pluck('profile.id')->filter();

            $evaluatedProfileIds = DeanEvaluationFeedback::whereIn('faculty_id', $profileIds)
                ->where('dean_user_id', $dean->id)
                ->where('semester', $semester)
                ->where('school_year', $schoolYear)
                ->pluck('faculty_id')
                ->flip();
        }

        $allFacultyData = $allFacultyData->map(function (array $row) use ($evaluatedProfileIds) {
            $row['has_evaluated'] = $evaluatedProfileIds->has($row['profile']->id);
            return $row;
        });

        $pendingFacultyData = $allFacultyData
            ->filter(fn (array $row) => ($row['has_evaluated'] ?? false) === false)
            ->values();

        $page    = request()->input('page', 1);
        $perPage = 25;
        $facultyData = new LengthAwarePaginator(
            $pendingFacultyData->forPage($page, $perPage)->values(),
            $pendingFacultyData->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('dashboard.dean', compact('facultyData', 'allFacultyData', 'period', 'dean'));
    }

    private function studentDashboard(): View
    {
        $student = auth()->user();
        $period  = EvaluationService::getOpenEvaluationPeriod();
        $profile = $student->studentProfile;

        if (! $profile) {
            return view('dashboard.student', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
            ]);
        }

        $subjectItems = StudentEvaluationSubjectService::buildSubjectItemsForStudent($student, $profile, $period);

        return view('dashboard.student', compact('subjectItems', 'period', 'student'));
    }

    private function hrDashboard(): View
    {
        $hr     = auth()->user();
        $period = EvaluationService::getOpenEvaluationPeriod();

        $totalFaculty  = User::where('role', 'faculty')->where('is_active', true)->count();
        $totalStudents = User::where('role', 'student')->where('is_active', true)->count();
        $totalDeans    = User::whereIn('role', ['dean', 'head'])->where('is_active', true)->count();

        $nonTeachingDepts = Department::where('department_type', 'non-teaching')
            ->where('is_active', true)
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $facultyRows = $this->loadFacultyWithPerformance(
            semester:   $period?->semester,
            schoolYear: $period?->school_year
        );

        $levelCounts = $facultyRows->groupBy('performance_level')->map(fn($rows) => $rows->count());

        return view('dashboard.hr', compact(
            'hr',
            'period',
            'totalFaculty',
            'totalStudents',
            'totalDeans',
            'nonTeachingDepts',
            'facultyRows',
            'levelCounts'
        ));
    }

    private function facultyDashboard(): View
    {
        return view('dashboard.faculty');
    }

    private function defaultDashboard(): View
    {
        return view('dashboard.default');
    }
}
