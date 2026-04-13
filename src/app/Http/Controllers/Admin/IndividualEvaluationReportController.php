<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Services\EvaluationService;
use App\Services\IndividualEvaluationItemizedReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IndividualEvaluationReportController extends Controller
{
    public function index(Request $request, IndividualEvaluationItemizedReportService $itemizedService): View
    {
        abort_unless(Gate::any(['generate-report', 'view-generated-report']), 403);

        $facultyOptions = FacultyProfile::query()
            ->with(['user', 'department'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'faculty')->where('is_active', true);
            })
            ->get()
            ->sortBy(function (FacultyProfile $profile) {
                $name = $profile->user !== null ? (string) $profile->user->name : '';

                return mb_strtolower($name);
            })
            ->values();

        $periods = EvaluationPeriod::query()
            ->select(['semester', 'school_year'])
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $openPeriod = EvaluationService::getOpenEvaluationPeriod();

        $rules = [
            'faculty_profile_id' => ['nullable', 'integer', 'exists:faculty_profiles,id'],
            'semester'           => ['nullable', 'string', 'max:30'],
            'school_year'        => ['nullable', 'string', 'max:30'],
            'report_type'        => ['nullable', Rule::in(['student', 'peer', 'dean', 'self'])],
            'print'              => ['nullable', 'boolean'],
            'generate'           => ['nullable', 'boolean'],
        ];
        if ($request->boolean('generate')) {
            $rules['report_type'] = ['required', Rule::in(['student', 'peer', 'dean', 'self'])];
            $rules['faculty_profile_id'] = ['required', 'integer', 'exists:faculty_profiles,id'];
        }

        $validated = $request->validate($rules);

        $reportType = $validated['report_type'] ?? null;
        $semester = $validated['semester'] ?? null;
        $schoolYear = $validated['school_year'] ?? null;

        if ($semester === '') {
            $semester = null;
        }
        if ($schoolYear === '') {
            $schoolYear = null;
        }

        $facultyId = isset($validated['faculty_profile_id']) ? (int) $validated['faculty_profile_id'] : null;

        if ($request->boolean('generate') && $facultyId !== null) {
            $exists = FacultyProfile::query()
                ->whereKey($facultyId)
                ->whereHas('user', function ($q) {
                    $q->where('role', 'faculty')->where('is_active', true);
                })
                ->exists();
            if (! $exists) {
                throw ValidationException::withMessages([
                    'faculty_profile_id' => 'Selected personnel is not valid or is inactive.',
                ]);
            }
        }

        $selectedFaculty = $facultyId ? FacultyProfile::with(['user', 'department'])->find($facultyId) : null;

        $itemized = null;
        $reportTitle = '';
        $documentHeading = '';
        $personnelType = 'teaching';

        if ($selectedFaculty && $reportType) {
            $personnelType = $selectedFaculty->evaluationCriteriaPersonnelType();
            $reportTitle = match ($reportType) {
                'student' => 'Student evaluation',
                'peer'    => 'Peer evaluation',
                'dean'    => 'Dean / supervisor evaluation',
                'self'    => 'Self evaluation',
                default   => 'Evaluation',
            };
            $documentHeading = match ($reportType) {
                'student' => "Teacher's Performance Evaluation Result",
                'peer'    => 'Peer Performance Evaluation Result',
                'dean'    => 'Supervisory Performance Evaluation Result',
                'self'    => 'Self Performance Evaluation Result',
                default   => 'Performance Evaluation Result',
            };

            $itemized = $itemizedService->build($selectedFaculty, $reportType, $semester, $schoolYear);
        }

        $printMode = (bool) ($validated['print'] ?? false);
        $hasData = is_array($itemized)
            && ($itemized['sections'] ?? []) !== [];

        $likertLegend = EvaluationService::likertScaleLabels(
            EvaluationService::normalizeEvaluateePersonnelForScoring($personnelType)
        );

        return view('admin.individual-evaluation-report', [
            'periods'         => $periods,
            'openPeriod'      => $openPeriod,
            'facultyOptions'  => $facultyOptions,
            'selectedFaculty' => $selectedFaculty,
            'reportType'      => $reportType,
            'reportTitle'     => $reportTitle,
            'documentHeading' => $documentHeading,
            'semester'        => $semester,
            'schoolYear'      => $schoolYear,
            'itemized'        => $itemized,
            'personnelType'   => $personnelType,
            'printMode'       => $printMode,
            'hasData'         => $hasData,
            'likertLegend'    => $likertLegend,
        ]);
    }
}
