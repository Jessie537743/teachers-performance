<?php

namespace App\Services;

use App\Models\EvaluationFeedback;
use App\Models\EvaluationPeriod;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lists subjects from the catalog for a student using course, year level, and section.
 * Does not require student_subject_assignments rows.
 */
class StudentEvaluationSubjectService
{
    public static function subjectAppliesToStudent(Subject $subject, StudentProfile $profile, ?EvaluationPeriod $period): bool
    {
        if ($period === null) {
            return false;
        }

        if (! self::coursesMatch((string) ($subject->course ?? ''), (string) ($profile->course ?? ''))) {
            return false;
        }

        $subjectYear = trim((string) ($subject->year_level ?? ''));
        $profileYear = trim((string) ($profile->year_level ?? ''));
        if ($subjectYear !== '' && $profileYear !== '') {
            if (! self::yearLevelsMatch($subjectYear, $profileYear)) {
                return false;
            }
        }

        return self::sectionValuesOverlap((string) ($subject->section ?? ''), (string) ($profile->section ?? ''));
    }

    public static function coursesMatch(string $subjectCourse, string $profileCourse): bool
    {
        $a = self::normalizeComparableValue($subjectCourse);
        $b = self::normalizeComparableValue($profileCourse);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }

        return str_contains($a, $b) || str_contains($b, $a);
    }

    /**
     * @return Collection<int, Subject>
     */
    public static function matchingSubjects(StudentProfile $profile, EvaluationPeriod $period): Collection
    {
        $course = trim((string) $profile->course);
        if ($course === '') {
            return collect();
        }

        $needle = mb_strtolower($course);

        $q = Subject::query()
            ->with('department')
            ->whereNotNull('course')
            ->where('course', '!=', '');

        if ($profile->department_id) {
            $q->where('department_id', $profile->department_id);
        } else {
            $q->where(function ($w) use ($needle) {
                $w->whereRaw('LOWER(TRIM(course)) = ?', [$needle])
                    ->orWhereRaw('LOWER(TRIM(course)) LIKE ?', ['%' . $needle . '%']);
            });
        }

        return $q->get()
            ->filter(function (Subject $s) use ($profile, $period) {
                if (! self::coursesMatch((string) ($s->course ?? ''), (string) ($profile->course ?? ''))) {
                    return false;
                }

                return self::subjectAppliesToStudent($s, $profile, $period);
            })
            ->sortBy(fn (Subject $s) => [(string) $s->code, (string) $s->title])
            ->values();
    }

    /**
     * @return Collection<int, array{subject: Subject, faculty_list: \Illuminate\Support\Collection<int, array<string, mixed>>}>
     */
    public static function buildSubjectItemsForStudent(User $student, StudentProfile $profile, ?EvaluationPeriod $period): Collection
    {
        if ($period === null) {
            return collect();
        }

        $subjects = self::matchingSubjects($profile, $period);
        if ($subjects->isEmpty()) {
            return collect();
        }

        $subjectIds = $subjects->pluck('id');

        $facultyAssignmentsBySubject = SubjectAssignment::with('faculty.user')
            ->whereIn('subject_id', $subjectIds->all())
            ->get()
            ->groupBy('subject_id');

        $evaluatedKeys = EvaluationFeedback::where('student_id', $student->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('CONCAT(faculty_id, ":", subject_id) as lookup_key')
            ->pluck('lookup_key')
            ->flip();

        return $subjects->map(function (Subject $subject) use ($facultyAssignmentsBySubject, $evaluatedKeys) {
            $subjectFAs = $facultyAssignmentsBySubject->get($subject->id, collect());

            $facultyList = $subjectFAs
                ->filter(fn ($fa) => $fa->faculty !== null)
                ->map(function ($fa) use ($subject, $evaluatedKeys) {
                    $facultyProfile = $fa->faculty;
                    $lookupKey = $facultyProfile->id . ':' . $subject->id;

                    return [
                        'faculty_profile' => $facultyProfile,
                        'faculty_user' => $facultyProfile->user,
                        'has_evaluated' => $evaluatedKeys->has($lookupKey),
                    ];
                })
                ->values();

            return [
                'subject' => $subject,
                'faculty_list' => $facultyList,
            ];
        })->values();
    }

    public static function yearLevelsMatch(string $subjectYearLevel, string $profileYearLevel): bool
    {
        $a = self::normalizeYearLevelKey($subjectYearLevel);
        $b = self::normalizeYearLevelKey($profileYearLevel);

        if ($a !== null && $b !== null) {
            return $a === $b;
        }

        return trim($subjectYearLevel) !== ''
            && trim($profileYearLevel) !== ''
            && self::normalizeComparableValue($subjectYearLevel) === self::normalizeComparableValue($profileYearLevel);
    }

    private static function normalizeYearLevelKey(string $raw): ?string
    {
        $v = mb_strtolower(trim($raw));
        if ($v === '') {
            return null;
        }

        if (preg_match('/\b([1-4])\b/', $v, $m)) {
            return $m[1];
        }
        if (preg_match('/\b(first|1st)\b/', $v)) {
            return '1';
        }
        if (preg_match('/\b(second|2nd|sophomore)\b/', $v)) {
            return '2';
        }
        if (preg_match('/\b(third|3rd|junior)\b/', $v)) {
            return '3';
        }
        if (preg_match('/\b(fourth|4th|senior)\b/', $v)) {
            return '4';
        }

        return null;
    }

    private static function normalizeComparableValue(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private static function sectionValuesOverlap(string $left, string $right): bool
    {
        $leftNormalized = self::normalizeComparableValue($left);
        $rightNormalized = self::normalizeComparableValue($right);

        if ($leftNormalized === '' || $rightNormalized === '') {
            return false;
        }

        $leftParts = self::splitSectionParts($leftNormalized);
        $rightParts = self::splitSectionParts($rightNormalized);

        if ($leftParts === [] || $rightParts === []) {
            return $leftNormalized === $rightNormalized;
        }

        return count(array_intersect($leftParts, $rightParts)) > 0;
    }

    /**
     * @return list<string>
     */
    private static function splitSectionParts(string $value): array
    {
        $parts = preg_split('/[\s,\/;&|]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $part): string => self::normalizeSectionToken($part),
            $parts
        ))));
    }

    private static function normalizeSectionToken(string $token): string
    {
        $value = self::normalizeComparableValue($token);
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
