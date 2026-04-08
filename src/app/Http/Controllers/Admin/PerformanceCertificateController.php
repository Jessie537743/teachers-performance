<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\FacultyProfile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class PerformanceCertificateController extends Controller
{
    use LoadsFacultyPerformance;

    private const TOP_LEVELS = ['Excellent', 'Outstanding'];

    public function show(Request $request, FacultyProfile $faculty_profile): View
    {
        Gate::authorize('view-admin-dashboard');

        $schoolYear = $request->query('school_year');
        $semester   = $request->query('semester');

        abort_unless(filled($schoolYear) && filled($semester), 404);

        $faculty_profile->loadMissing(['user', 'department']);

        $row = $this->loadFacultyWithPerformance(
            departmentId: null,
            semester:     $semester,
            schoolYear:   $schoolYear,
        )->first(fn (array $r) => $r['profile']->id === $faculty_profile->id);

        abort_if(! $row, 404, 'No performance record for this faculty member in the selected period.');

        abort_unless(
            $row['weighted_average'] !== null && in_array($row['performance_level'], self::TOP_LEVELS, true),
            403,
            'Certificates are only available for Excellent or Outstanding performance for this period.'
        );

        $institutionName = Setting::get('app_name', config('app.name', 'Institution'));

        return view('certificates.performance-excellent', [
            'fullName'          => $row['user']->name,
            'department'        => $row['department'],
            'gwa'               => $row['weighted_average'],
            'performanceLevel'  => $row['performance_level'],
            'schoolYear'        => $schoolYear,
            'semester'          => $semester,
            'institutionName'   => $institutionName,
            'awardedDate'       => now()->format('F j, Y'),
        ]);
    }
}
