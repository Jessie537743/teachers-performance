<?php

namespace App\Services;

use App\Models\EvaluationPeriod;
use Illuminate\Support\Collection;

/**
 * Identifies active faculty who have three consecutive evaluation periods where
 * overall weighted performance falls in the low bands from
 * EvaluationService::qualifiesForPerformanceIntervention(): teaching — Fair, Poor;
 * non-teaching — Below Average, Poor; Dean/Head/Administrator — Below Average, Unsatisfactory.
 *
 * A period counts only if at least one evaluation component (student, dean, self, peer)
 * has data for that period; periods with no evaluation data break a streak.
 */
class ConsecutiveLowPerformanceService
{
    /**
     * @param  callable(string $schoolYear, string $semester): Collection<int, array<string, mixed>>  $loadPerformanceRows
     * @return list<array{
     *   profile: \App\Models\FacultyProfile,
     *   user: \App\Models\User,
     *   department: string,
     *   streak: list<array{school_year: string, semester: string, weighted_average: float, performance_level: string, badge_class: string}>,
     * }>
     */
    public function findThreeConsecutiveLowPerformers(callable $loadPerformanceRows): array
    {
        $periods = $this->orderedPeriods();
        if ($periods->isEmpty()) {
            return [];
        }

        /** @var array<int, array<int, array{school_year: string, semester: string, weighted_average: float, performance_level: string, badge_class: string, has_data: bool, personnel_type: string}>> $byProfile */
        $byProfile = [];

        foreach ($periods as $period) {
            $rows = $loadPerformanceRows($period['school_year'], $period['semester']);
            foreach ($rows as $row) {
                $profile = $row['profile'];
                $profileId = $profile->id;

                $hasData = ($row['student_avg'] ?? null) !== null
                    || ($row['dean_avg'] ?? null) !== null
                    || ($row['self_avg'] ?? null) !== null
                    || ($row['peer_avg'] ?? null) !== null;

                $personnelType = $profile->evaluationCriteriaPersonnelType();
                $level = $row['performance_level'] ?? null;

                $lowBand = $hasData && EvaluationService::qualifiesForPerformanceIntervention($level, $personnelType);

                if (! isset($byProfile[$profileId])) {
                    $byProfile[$profileId] = [];
                }

                $byProfile[$profileId][] = [
                    'school_year'        => $period['school_year'],
                    'semester'           => $period['semester'],
                    'weighted_average'   => (float) ($row['weighted_average'] ?? 0),
                    'performance_level'  => (string) $level,
                    'badge_class'        => (string) ($row['badge_class'] ?? ''),
                    'has_data'           => $hasData,
                    'low_band'           => $lowBand,
                    'personnel_type'     => $personnelType,
                    'user'               => $row['user'],
                    'profile'            => $profile,
                    'department'         => $row['department'] ?? '—',
                ];
            }
        }

        $flagged = [];

        foreach ($byProfile as $entries) {
            if (count($entries) < 3) {
                continue;
            }

            $n = count($entries);
            $lastMatchIndex = null;
            for ($i = 0; $i <= $n - 3; $i++) {
                $a = $entries[$i];
                $b = $entries[$i + 1];
                $c = $entries[$i + 2];

                if (! $a['has_data'] || ! $b['has_data'] || ! $c['has_data']) {
                    continue;
                }

                if (! $a['low_band'] || ! $b['low_band'] || ! $c['low_band']) {
                    continue;
                }

                $lastMatchIndex = $i;
            }

            if ($lastMatchIndex === null) {
                continue;
            }

            $a = $entries[$lastMatchIndex];
            $b = $entries[$lastMatchIndex + 1];
            $c = $entries[$lastMatchIndex + 2];
            $first = $entries[0];

            $flagged[] = [
                'profile'    => $first['profile'],
                'user'       => $first['user'],
                'department' => $first['department'],
                'streak'     => [
                    $this->streakSlice($a),
                    $this->streakSlice($b),
                    $this->streakSlice($c),
                ],
            ];
        }

        usort($flagged, fn ($x, $y) => strcmp(
            mb_strtolower($x['user']->name ?? ''),
            mb_strtolower($y['user']->name ?? '')
        ));

        return $flagged;
    }

    /**
     * @return Collection<int, array{school_year: string, semester: string}>
     */
    public function orderedPeriods(): Collection
    {
        $rows = EvaluationPeriod::query()
            ->select(['school_year', 'semester', 'start_date'])
            ->orderBy('start_date')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->unique(fn ($p) => $p->school_year.'|'.$p->semester)
            ->values()
            ->map(fn ($p) => [
                'school_year' => (string) $p->school_year,
                'semester'    => (string) $p->semester,
            ]);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array{school_year: string, semester: string, weighted_average: float, performance_level: string, badge_class: string}
     */
    private function streakSlice(array $entry): array
    {
        return [
            'school_year'       => $entry['school_year'],
            'semester'          => $entry['semester'],
            'weighted_average'  => $entry['weighted_average'],
            'performance_level' => $entry['performance_level'],
            'badge_class'       => $entry['badge_class'],
        ];
    }
}
