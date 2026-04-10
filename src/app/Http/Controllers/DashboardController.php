<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Http\Controllers\Traits\NormalizesComparableValues;
use App\Models\DeanEvaluationFeedback;
use App\Models\Department;
use App\Models\EvaluationFeedback;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use LoadsFacultyPerformance, NormalizesComparableValues;

    public function index(): View
    {
        $user = auth()->user();

        return match (true) {
            $user->can('view-admin-dashboard')   => $this->adminDashboard(),
            $user->can('view-dean-dashboard')    => $this->deanDashboard(),
            $user->can('view-student-dashboard') => $this->studentDashboard(),
            $user->can('view-hr-dashboard')      => $this->hrDashboard(),
            $user->can('view-faculty-dashboard') => $this->facultyDashboard(),
            default                              => $this->defaultDashboard(),
        };
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

        // No open evaluation period or no student profile → nothing to show.
        if (! $period || ! $profile) {
            return view('dashboard.student', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
            ]);
        }

        // Show subjects scheduled for the open evaluation period that match
        // the student's course AND year level. Section is intentionally NOT
        // enforced — section data is inconsistent across the dataset and the
        // course + year combination is sufficient to scope the cohort.
        $subjects = Subject::with('department')
            ->where(function ($q) use ($period) {
                $q->where('semester', $period->semester)
                  ->orWhereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim((string) $period->semester))]);
            })
            ->where('school_year', $period->school_year)
            ->whereRaw('LOWER(TRIM(course)) = ?', [strtolower(trim((string) $profile->course))])
            ->whereRaw('TRIM(year_level) = ?', [trim((string) $profile->year_level)])
            ->orderBy('code')
            ->get();

        if ($subjects->isEmpty()) {
            return view('dashboard.student', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
            ]);
        }

        $subjectIds = $subjects->pluck('id');

        $facultyAssignmentsBySubject = SubjectAssignment::with('faculty.user')
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy('subject_id');

        $evaluatedKeys = EvaluationFeedback::where('student_id', $student->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('CONCAT(faculty_id, ":", subject_id) as lookup_key')
            ->pluck('lookup_key')
            ->flip();

        $subjectItems = $subjects->map(function (Subject $subject) use ($facultyAssignmentsBySubject, $evaluatedKeys) {
            $subjectFAs = $facultyAssignmentsBySubject->get($subject->id, collect());

            $facultyList = $subjectFAs
                ->filter(fn ($fa) => $fa->faculty !== null)
                ->map(function ($fa) use ($subject, $evaluatedKeys) {
                    $facultyProfile = $fa->faculty;
                    $lookupKey      = $facultyProfile->id . ':' . $subject->id;

                    return [
                        'faculty_profile' => $facultyProfile,
                        'faculty_user'    => $facultyProfile->user,
                        'has_evaluated'   => $evaluatedKeys->has($lookupKey),
                    ];
                })
                ->values();

            return [
                'subject'      => $subject,
                'faculty_list' => $facultyList,
            ];
        })
        // Only surface subjects that actually have at least one faculty
        // assigned — otherwise there is nothing for the student to evaluate.
        ->filter(fn (array $item) => $item['faculty_list']->isNotEmpty())
        ->values();

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
