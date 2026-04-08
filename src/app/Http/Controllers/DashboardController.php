<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
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
    use LoadsFacultyPerformance;

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

        if (!$profile) {
            return view('dashboard.student', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
            ]);
        }

        $assignments = $profile->subjectAssignments()
            ->with(['subject.department'])
            ->get()
            ->filter(function ($assignment) use ($profile) {
                if (! $assignment->subject) {
                    return false;
                }

                return $this->subjectMatchesStudentProfile($assignment->subject, $profile);
            })
            ->values();

        if ($assignments->isEmpty()) {
            return view('dashboard.student', [
                'subjectItems' => collect(),
                'period'       => $period,
                'student'      => $student,
            ]);
        }

        $subjectIds = $assignments->pluck('subject_id')->unique()->values();

        $facultyAssignmentsBySubject = SubjectAssignment::with('faculty.user')
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy('subject_id');

        $evaluatedKeys = collect();
        if ($period) {
            $evaluatedKeys = EvaluationFeedback::where('student_id', $student->id)
                ->where('semester', $period->semester)
                ->where('school_year', $period->school_year)
                ->whereIn('subject_id', $subjectIds)
                ->selectRaw('CONCAT(faculty_id, ":", subject_id) as lookup_key')
                ->pluck('lookup_key')
                ->flip();
        }

        $subjectItems = $assignments->map(function ($assignment) use ($facultyAssignmentsBySubject, $evaluatedKeys) {
            $subject     = $assignment->subject;
            $subjectFAs  = $facultyAssignmentsBySubject->get($subject->id, collect());

            $facultyList = $subjectFAs
                ->filter(fn($fa) => $fa->faculty !== null)
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
        });

        return view('dashboard.student', compact('subjectItems', 'period', 'student'));
    }

    private function subjectMatchesStudentProfile(Subject $subject, object $studentProfile): bool
    {
        $subjectCourse = $this->normalizeComparableValue((string) ($subject->course ?? ''));
        $profileCourse = $this->normalizeComparableValue((string) ($studentProfile->course ?? ''));

        if ($subjectCourse === '' || $profileCourse === '' || $subjectCourse !== $profileCourse) {
            return false;
        }

        $subjectYearLevel = trim((string) ($subject->year_level ?? ''));
        $profileYearLevel = trim((string) ($studentProfile->year_level ?? ''));
        if ($subjectYearLevel === '' || $profileYearLevel === '' || $subjectYearLevel !== $profileYearLevel) {
            return false;
        }

        return $this->sectionValuesOverlap(
            (string) ($subject->section ?? ''),
            (string) ($studentProfile->section ?? '')
        );
    }

    private function normalizeComparableValue(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Supports section values like "1", "2", and grouped values like "1,2".
     */
    private function sectionValuesOverlap(string $left, string $right): bool
    {
        $leftNormalized = $this->normalizeComparableValue($left);
        $rightNormalized = $this->normalizeComparableValue($right);

        if ($leftNormalized === '' || $rightNormalized === '') {
            return false;
        }

        $leftParts = $this->splitSectionParts($leftNormalized);
        $rightParts = $this->splitSectionParts($rightNormalized);

        if ($leftParts === [] || $rightParts === []) {
            return $leftNormalized === $rightNormalized;
        }

        return count(array_intersect($leftParts, $rightParts)) > 0;
    }

    /**
     * @return list<string>
     */
    private function splitSectionParts(string $value): array
    {
        $parts = preg_split('/[\s,\/;&|]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $part): string => $this->normalizeSectionToken($part),
            $parts
        ))));
    }

    private function normalizeSectionToken(string $token): string
    {
        $value = $this->normalizeComparableValue($token);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^section\s*([0-9]+)$/i', $value, $matches)) {
            return (string) ((int) $matches[1]);
        }

        if (preg_match('/^[0-9]+$/', $value)) {
            return (string) ((int) $value);
        }

        if (preg_match('/^[a-z]$/', $value)) {
            return (string) (ord(strtoupper($value)) - ord('A') + 1);
        }

        return $value;
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
