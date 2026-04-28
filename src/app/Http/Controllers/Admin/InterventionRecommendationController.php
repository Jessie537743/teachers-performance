<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use App\Services\InterventionRecommendationMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Intervention Recommendation Module — bulk roster of every faculty's
 * predicted GWA, performance level, mapped intervention, and priority.
 * Companion to the per-faculty AI Intervention Plan.
 */
class InterventionRecommendationController extends Controller
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
        $semester   = $request->query('semester',   $open?->semester);

        // Deans/heads see only their own department's faculty
        $departmentScope = ($user->hasRole(['dean', 'head']) && ! $user->hasRole(['admin', 'human_resource']))
            ? (int) $user->department_id
            : null;

        $rows = $this->loadFacultyWithPerformance(
            departmentId: $departmentScope,
            semester:     $semester,
            schoolYear:   $schoolYear,
        );

        $recommendations = $rows->map(function ($row) {
            $level = $row['performance_level'] ?? null;
            $rec = InterventionRecommendationMapper::recommend($level);

            return [
                'faculty_id'       => $row['user']->id,
                'faculty_name'     => $row['user']->name,
                'department'       => $row['department'] ?? '—',
                'department_code'  => $row['department_code'] ?? '',
                'predicted_gwa'    => $row['weighted_average'] ?? null,
                'performance_level'=> $level ?? '—',
                'recommendation'   => $rec,
                'profile_id'       => $row['profile']->id ?? null,
            ];
        })->sortBy([
            // Sort by priority severity first (High → Medium → Low → —),
            // then by faculty name within the same priority.
            fn ($a, $b) => self::priorityRank($b['recommendation']['priority']) <=> self::priorityRank($a['recommendation']['priority']),
            fn ($a, $b) => strcasecmp((string) $a['faculty_name'], (string) $b['faculty_name']),
        ])->values();

        $counts = [
            'high'   => $recommendations->where('recommendation.priority', 'High')->count(),
            'medium' => $recommendations->where('recommendation.priority', 'Medium')->count(),
            'low'    => $recommendations->where('recommendation.priority', 'Low')->count(),
            'total'  => $recommendations->count(),
        ];

        return view('admin.intervention-recommendations.index', [
            'recommendations' => $recommendations,
            'periods'         => $periods,
            'schoolYear'      => $schoolYear,
            'semester'        => $semester,
            'counts'          => $counts,
            'departmentScoped'=> $departmentScope !== null,
        ]);
    }

    public function show(Request $request, int $faculty_id): JsonResponse
    {
        Gate::authorize('view-analytics');

        $rows = $this->loadFacultyWithPerformance(
            semester:   $request->query('semester'),
            schoolYear: $request->query('school_year'),
        );

        $row = $rows->first(fn ($r) => $r['user']->id === $faculty_id);
        abort_unless($row, 404, 'Faculty not found in current scope.');

        $level = $row['performance_level'] ?? null;
        $rec = InterventionRecommendationMapper::recommend($level);

        return response()->json([
            'faculty_id'        => $row['user']->id,
            'faculty_name'      => $row['user']->name,
            'department'        => $row['department'] ?? '—',
            'predicted_gwa'     => $row['weighted_average'] ?? null,
            'performance_level' => $level ?? '—',
            'components'        => $row['components'] ?? [],
            'recommendation'    => $rec,
            'profile_id'        => $row['profile']->id ?? null,
        ]);
    }

    private static function priorityRank(string $p): int
    {
        return match ($p) { 'High' => 3, 'Medium' => 2, 'Low' => 1, default => 0 };
    }
}
