<?php

namespace App\Http\Controllers\Traits;

trait NormalizesComparableValues
{
    protected function normalizeComparableValue(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Supports section values like "1", "2", and grouped values like "1,2".
     */
    protected function sectionValuesOverlap(string $left, string $right): bool
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
    protected function splitSectionParts(string $value): array
    {
        $parts = preg_split('/[\s,\/;&|]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $part): string => $this->normalizeSectionToken($part),
            $parts
        ))));
    }

    protected function normalizeSectionToken(string $token): string
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
}
