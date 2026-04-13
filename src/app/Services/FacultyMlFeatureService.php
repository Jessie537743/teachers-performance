<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the same feature vector as the ML API training script (self/dean/peer rows
 * aggregated per faculty per term, with previous-term score and improvement rate).
 */
class FacultyMlFeatureService
{
    /**
     * @param  list<int>  $profileIds  Faculty profile IDs (evaluatee_faculty_id / faculty_id).
     * @return array<int, array{avg_score: float, response_count: int, previous_score: float, improvement_rate: float}|null>
     */
    public function featuresForTerm(array $profileIds, ?string $semester, ?string $schoolYear): array
    {
        $profileIds = array_values(array_unique(array_filter(array_map('intval', $profileIds))));

        if ($profileIds === [] || ! filled($semester) || ! filled($schoolYear)) {
            return array_fill_keys($profileIds, null);
        }

        $raw = $this->loadUnionRows($profileIds);
        $out = [];

        foreach ($profileIds as $id) {
            $out[$id] = $this->featuresForFacultyFromRows($raw, $id, $semester, $schoolYear);
        }

        return $out;
    }

    /**
     * @return Collection<int, object>
     */
    private function loadUnionRows(array $profileIds): Collection
    {
        $in = implode(',', array_fill(0, count($profileIds), '?'));
        $bindings = array_merge($profileIds, $profileIds, $profileIds);

        $sql = "
            SELECT faculty_id, semester, school_year, total_average, created_at
            FROM self_evaluation_results
            WHERE faculty_id IN ($in) AND total_average IS NOT NULL
            UNION ALL
            SELECT faculty_id, semester, school_year, total_average, created_at
            FROM dean_evaluation_feedback
            WHERE faculty_id IN ($in) AND total_average IS NOT NULL
            UNION ALL
            SELECT evaluatee_faculty_id AS faculty_id, semester, school_year, total_average, created_at
            FROM faculty_peer_evaluation_feedback
            WHERE evaluatee_faculty_id IN ($in) AND evaluation_type = 'peer' AND total_average IS NOT NULL
        ";

        return collect(DB::select($sql, $bindings));
    }

    /**
     * @param  Collection<int, object>  $allRows
     * @return array{avg_score: float, response_count: int, previous_score: float, improvement_rate: float}|null
     */
    private function featuresForFacultyFromRows(Collection $allRows, int $facultyId, string $semester, string $schoolYear): ?array
    {
        $rows = $allRows->where('faculty_id', $facultyId);
        if ($rows->isEmpty()) {
            return null;
        }

        $grouped = $rows->groupBy(function (object $r): string {
            $sy = mb_strtolower(trim((string) ($r->school_year ?? '')));
            $sem = mb_strtolower(trim((string) ($r->semester ?? '')));

            return $sy.'|'.$sem;
        })->map(function (Collection $group) {
            $scores = $group->pluck('total_average')->map(fn ($v) => (float) $v);
            $mean = (float) $scores->avg();

            $createdTimes = $group->map(function ($r) {
                $c = $r->created_at ?? null;

                return $c ? strtotime((string) $c) : 0;
            });

            return [
                'school_year'      => (string) ($group->first()->school_year ?? ''),
                'semester'         => (string) ($group->first()->semester ?? ''),
                'avg_score'        => $mean,
                'response_count'   => $group->count(),
                'created_at_max'   => (int) $createdTimes->max(),
            ];
        })->values();

        $sorted = $grouped->sortBy('created_at_max')->values();

        $targetKey = mb_strtolower(trim($schoolYear)).'|'.mb_strtolower(trim($semester));
        $idx = $sorted->search(fn (array $g): bool => mb_strtolower(trim((string) $g['school_year'])).'|'.mb_strtolower(trim((string) $g['semester'])) === $targetKey);

        if ($idx === false) {
            return null;
        }

        $current = $sorted[$idx];
        $avg = min(5.0, max(1.0, (float) $current['avg_score']));
        $responseCount = max(0, min(10_000, (int) $current['response_count']));

        $prevAvg = $avg;
        if ($idx > 0) {
            $prevAvg = min(5.0, max(1.0, (float) $sorted[$idx - 1]['avg_score']));
        }

        $previousScore = $prevAvg;
        $improvement = 0.0;
        if ($previousScore > 0) {
            $improvement = ($avg - $previousScore) / $previousScore;
        }
        $improvement = min(1.0, max(-1.0, $improvement));

        return [
            'avg_score'         => $avg,
            'response_count'    => $responseCount,
            'previous_score'    => $previousScore,
            'improvement_rate' => $improvement,
        ];
    }
}
