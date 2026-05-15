<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\EvaluationPeriod;
use App\Models\FacultyProfile;
use App\Models\Setting;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Top Performer of the Department — one award per (department, period).
 *
 *   index        Lists each department's #1 faculty by weighted_average for the
 *                selected period. Dean/head viewers are scoped to their own
 *                department; admin/HR/VPs see every department.
 *
 *   certificate  Printable certificate (separate template from the existing
 *                Excellent/Outstanding cert — this one names the recipient as
 *                'Top Performer of the Department'). Only the actual dept #1
 *                may be issued; lower-ranked faculty get a 403.
 */
class TopPerformerController extends Controller
{
    use LoadsFacultyPerformance;

    public function index(Request $request): View
    {
        Gate::authorize('view-analytics');
        $user = $request->user();

        $periods = EvaluationPeriod::query()
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $open = EvaluationService::getOpenEvaluationPeriod();
        $schoolYear = $request->query('school_year', $open?->school_year);
        $semester   = $request->query('semester',    $open?->semester);

        $departmentScope = $this->departmentScope($user);

        $topPerformers = collect();
        $hasPeriodFilter = filled($schoolYear) && filled($semester);

        if ($hasPeriodFilter) {
            $rows = $this->loadFacultyWithPerformance(
                departmentId: $departmentScope,
                semester:     $semester,
                schoolYear:   $schoolYear,
            );

            // One winner per department: highest weighted_average with data.
            $topPerformers = $rows
                ->filter(fn (array $r) => $r['weighted_average'] !== null && $r['weighted_average'] > 0)
                ->groupBy(fn (array $r) => $r['profile']->department?->id ?? 0)
                ->map(fn ($group) => $group->sortByDesc('weighted_average')->first())
                ->sortBy(fn (array $r) => strtolower($r['department']))
                ->values();
        }

        return view('top-performers.index', [
            'topPerformers'   => $topPerformers,
            'periods'         => $periods,
            'schoolYear'      => $schoolYear,
            'semester'        => $semester,
            'hasPeriodFilter' => $hasPeriodFilter,
            'departmentScoped'=> $departmentScope !== null,
        ]);
    }

    public function certificate(Request $request, FacultyProfile $faculty_profile): View
    {
        Gate::authorize('view-analytics');
        $user = $request->user();

        $schoolYear = $request->query('school_year');
        $semester   = $request->query('semester');

        abort_unless(filled($schoolYear) && filled($semester), 404, 'School year and semester are required.');

        $faculty_profile->loadMissing(['user', 'department']);

        // Dean/head viewers may only generate certs for their own department.
        $departmentScope = $this->departmentScope($user);
        if ($departmentScope !== null) {
            abort_unless(
                (int) $faculty_profile->department?->id === $departmentScope,
                403,
                'You may only issue certificates for your own department.'
            );
        }

        $rows = $this->loadFacultyWithPerformance(
            departmentId: $faculty_profile->department?->id,
            semester:     $semester,
            schoolYear:   $schoolYear,
        );

        $row = $rows->first(fn (array $r) => $r['profile']->id === $faculty_profile->id);
        abort_if(!$row, 404, 'No performance record for this faculty member in the selected period.');

        // Confirm this faculty is actually the dept #1 for this period.
        $top = $rows
            ->filter(fn (array $r) => $r['weighted_average'] !== null && $r['weighted_average'] > 0)
            ->sortByDesc('weighted_average')
            ->first();

        abort_unless(
            $top && $top['profile']->id === $faculty_profile->id,
            403,
            'Top Performer certificates may only be issued to the highest-ranked faculty in the department for the selected period.'
        );

        return view('certificates.top-performer', [
            'fullName'         => $row['user']->name,
            'department'       => $row['department'],
            'gwa'              => $row['weighted_average'],
            'performanceLevel' => $row['performance_level'],
            'schoolYear'       => $schoolYear,
            'semester'         => $semester,
            'institutionName'  => Setting::get('app_name', config('app.name', 'Institution')),
            'awardedDate'      => now()->format('F j, Y'),
        ]);
    }

    /**
     * Returns the department_id a dept-scoped user is restricted to, or null
     * for institution-wide roles (admin / HR / VPs). Matches the convention
     * used in InterventionRecommendationController.
     */
    private function departmentScope($user): ?int
    {
        if (!$user) {
            return null;
        }
        $isOnlyDeanOrHead = collect($user->roles ?? [])->isNotEmpty()
            && collect($user->roles ?? [])->every(fn ($r) => in_array($r, ['dean', 'head'], true));
        return ($isOnlyDeanOrHead && $user->department_id) ? (int) $user->department_id : null;
    }
}
