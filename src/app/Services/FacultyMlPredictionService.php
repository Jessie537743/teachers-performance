<?php

namespace App\Services;

use App\Models\FacultyPrediction;
use Illuminate\Support\Collection;

class FacultyMlPredictionService
{
    public function __construct(
        private readonly FacultyMlFeatureService $facultyMlFeatures,
        private readonly MlApiService $mlApi,
    ) {}

    /**
     * Attach ML API prediction payloads to paginated faculty rows (same features as model training).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function attachPredictionsToRows(Collection $rows, ?string $semester, ?string $schoolYear): Collection
    {
        if (! filled($semester) || ! filled($schoolYear)) {
            return $rows->map(function (array $row) {
                $row['ml_prediction'] = null;

                return $row;
            });
        }

        $ids = $rows->map(fn (array $r) => (int) $r['profile']->id)->all();
        $featureMap = $this->facultyMlFeatures->featuresForTerm($ids, $semester, $schoolYear);

        return $rows->map(function (array $row) use ($featureMap, $semester, $schoolYear) {
            $pid = (int) $row['profile']->id;
            $feat = $featureMap[$pid] ?? null;

            if ($feat === null) {
                $row['ml_prediction'] = null;

                return $row;
            }

            $result = $this->mlApi->predict(
                $feat['avg_score'],
                $feat['response_count'],
                $feat['previous_score'],
                $feat['improvement_rate'],
                $semester,
                $schoolYear,
            );

            $row['ml_prediction'] = $result;

            if (! isset($result['error'])) {
                $this->persistPrediction($row, $feat, $result, $semester, $schoolYear);
            }

            return $row;
        });
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function persistPrediction(array $row, array $feat, array $result, string $semester, string $schoolYear): void
    {
        $profile = $row['profile'];
        $rec = isset($result['rule_label'], $result['confidence'])
            ? sprintf('Rule baseline: %s. Confidence: %s.', $result['rule_label'], $result['confidence'])
            : null;

        FacultyPrediction::query()->updateOrInsert(
            [
                'faculty_id'  => $profile->id,
                'semester'    => $semester,
                'school_year' => $schoolYear,
            ],
            [
                'department_id'         => (int) ($profile->department_id ?? 0),
                'avg_score'             => $feat['avg_score'],
                'response_count'        => $feat['response_count'],
                'predicted_performance' => $result['predicted_performance'] ?? null,
                'recommendation'        => $rec,
                'model_used'            => is_string($result['model_used'] ?? null) ? $result['model_used'] : 'Random Forest',
                'prediction_date'       => now(),
            ]
        );
    }
}
