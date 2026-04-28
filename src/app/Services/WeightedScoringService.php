<?php

namespace App\Services;

/**
 * Weighted-mean computation for criteria + questions.
 *
 * Weights are stored as configurable percentages on `criteria.weight` and
 * `questions.weight`. They don't have to sum to exactly 100 — this service
 * normalizes-and-divides at compute time, so partial coverage (e.g. only a
 * subset of criteria apply to a particular evaluator group + personnel type)
 * still produces a coherent average.
 *
 * Behavior summary:
 *   - All weights ≤ 0 OR all entries missing → falls back to a plain mean
 *     (same numbers the app produced before weights existed).
 *   - Some weights = 0, some > 0 → 0-weight entries excluded from the mean.
 *   - Entries with null values → excluded from both weight sum and total.
 */
class WeightedScoringService
{
    /**
     * Compute the weighted mean of [{value, weight}] pairs.
     *
     *   $entries = [
     *       ['value' => 4.2, 'weight' => 30],
     *       ['value' => 3.8, 'weight' => 70],
     *       ['value' => null, 'weight' => 50], // skipped
     *   ];
     *
     * @param  list<array{value: float|int|null, weight: float|int|null}>  $entries
     */
    public static function weightedMean(array $entries): ?float
    {
        $valid = array_values(array_filter(
            $entries,
            fn ($e) => isset($e['value']) && is_numeric($e['value']),
        ));

        if ($valid === []) {
            return null;
        }

        $weightSum = 0.0;
        $weightedSum = 0.0;

        foreach ($valid as $e) {
            $w = (float) ($e['weight'] ?? 0);
            if ($w <= 0) {
                continue;
            }
            $weightedSum += ((float) $e['value']) * $w;
            $weightSum   += $w;
        }

        if ($weightSum > 0) {
            return $weightedSum / $weightSum;
        }

        // No usable weights — degrade to plain mean of available values
        $values = array_map(fn ($e) => (float) $e['value'], $valid);
        return array_sum($values) / count($values);
    }

    /**
     * Normalize a list of weights so they sum to 100. Returns the same list
     * unmodified if all-zero (caller can decide how to handle).
     *
     * @param  list<float|int>  $weights
     * @return list<float>
     */
    public static function normalize(array $weights): array
    {
        $sum = array_sum(array_map('floatval', $weights));
        if ($sum <= 0) {
            return array_map('floatval', $weights);
        }
        return array_map(fn ($w) => round(((float) $w / $sum) * 100, 4), $weights);
    }

    /**
     * Return [valueA*wA + valueB*wB + ...] / sum(weights), where the value
     * collection is keyed by some id and the weight collection mirrors that.
     * Convenience for the common "I have ratings keyed by question_id and
     * weights keyed by question_id" pattern.
     *
     * @param  array<int|string, float|int|null>  $valuesById
     * @param  array<int|string, float|int|null>  $weightsById
     */
    public static function weightedMeanByKeys(array $valuesById, array $weightsById): ?float
    {
        $entries = [];
        foreach ($valuesById as $id => $value) {
            $entries[] = [
                'value'  => $value,
                'weight' => $weightsById[$id] ?? 0,
            ];
        }
        return self::weightedMean($entries);
    }
}
