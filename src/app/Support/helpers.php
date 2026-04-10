<?php

if (! function_exists('format_semester')) {
    /**
     * Normalize any semester representation to a single uniform display label.
     *
     * Accepts: '1st', '2nd', 'summer', '1st Semester', '2nd Semest', etc.
     * Returns: '1st Semester', '2nd Semester', 'Summer', or the original
     * (trimmed) value when it cannot be classified.
     */
    function format_semester(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, '1st') || $normalized === '1' || $normalized === 'first') {
            return '1st Semester';
        }

        if (str_starts_with($normalized, '2nd') || $normalized === '2' || $normalized === 'second') {
            return '2nd Semester';
        }

        if (str_contains($normalized, 'summer')) {
            return 'Summer';
        }

        return trim($value);
    }
}

if (! function_exists('canonical_semester')) {
    /**
     * Storage-friendly canonical form of a semester string.
     * Use this when writing to the database to keep values uniform.
     */
    function canonical_semester(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $formatted = format_semester($value);

        return $formatted === '' ? null : $formatted;
    }
}
