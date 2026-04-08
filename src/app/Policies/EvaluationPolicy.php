<?php

namespace App\Policies;

use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\EvaluationService;

class EvaluationPolicy
{
    /**
     * Student can evaluate a faculty member for a specific subject if:
     * 1. Evaluation period is open
     * 2. Student has the subject assigned (enrolled)
     * 3. Faculty is assigned to teach that subject
     * 4. Student hasn't already submitted this evaluation
     */
    public function submitStudentEvaluation(User $student, FacultyProfile $faculty, Subject $subject): bool
    {
        if (!EvaluationService::isEvaluationOpen()) return false;

        $studentProfile = $student->studentProfile;
        if (!$studentProfile) return false;

        $studentCourse = $this->normalizeComparableValue((string) $studentProfile->course);
        $subjectCourse = $this->normalizeComparableValue((string) $subject->course);
        if ($studentCourse === '' || $subjectCourse === '' || $studentCourse !== $subjectCourse) {
            return false;
        }

        $studentYearLevel = trim((string) $studentProfile->year_level);
        $subjectYearLevel = trim((string) $subject->year_level);
        if ($studentYearLevel === '' || $subjectYearLevel === '' || $studentYearLevel !== $subjectYearLevel) {
            return false;
        }

        if (! $this->sectionValuesOverlap((string) $studentProfile->section, (string) $subject->section)) {
            return false;
        }

        $enrolled = StudentSubjectAssignment::where('student_profile_id', $studentProfile->id)
            ->where('subject_id', $subject->id)
            ->exists();
        if (!$enrolled) return false;

        $teaches = SubjectAssignment::where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->exists();
        if (!$teaches) return false;

        $alreadyEvaluated = EvaluationFeedback::where('student_id', $student->id)
            ->where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->exists();

        return !$alreadyEvaluated;
    }

    private function normalizeComparableValue(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Supports values like "1", "2", and grouped values like "1,2".
     */
    private function sectionValuesOverlap(string $left, string $right): bool
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
    private function splitSectionParts(string $value): array
    {
        $parts = preg_split('/[\s,\/;&|]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $part): string => $this->normalizeSectionToken($part),
            $parts
        ))));
    }

    private function normalizeSectionToken(string $token): string
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

    /**
     * Dean can evaluate a faculty member if:
     * 1. Evaluation period is open
     * 2. Faculty belongs to the dean's department
     * 3. Dean hasn't already evaluated this faculty for this period
     */
    public function submitDeanEvaluation(User $dean, FacultyProfile $faculty): bool
    {
        if (!EvaluationService::isEvaluationOpen()) return false;

        if ($faculty->department_id !== $dean->department_id) return false;

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (!$period) return false;

        $alreadyEvaluated = DeanEvaluationFeedback::where('dean_user_id', $dean->id)
            ->where('faculty_id', $faculty->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return !$alreadyEvaluated;
    }

    /**
     * Faculty can self-evaluate if:
     * 1. Evaluation period is open
     * 2. They have a faculty profile
     * 3. Haven't already self-evaluated this period
     */
    public function submitSelfEvaluation(User $faculty): bool
    {
        if (!EvaluationService::isEvaluationOpen()) return false;

        $profile = $faculty->facultyProfile;
        if (!$profile) return false;

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (!$period) return false;

        $alreadyDone = FacultyPeerEvaluationFeedback::where('evaluator_faculty_id', $profile->id)
            ->where('evaluatee_faculty_id', $profile->id)
            ->where('evaluation_type', 'self')
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return !$alreadyDone;
    }

    /**
     * Faculty can peer-evaluate another faculty if:
     * 1. Evaluation period is open
     * 2. Both have faculty profiles
     * 3. They're in the same department
     * 4. They're not evaluating themselves
     * 5. Haven't already evaluated this peer for this period
     */
    public function submitPeerEvaluation(User $evaluator, FacultyProfile $evaluatee): bool
    {
        if (!EvaluationService::isEvaluationOpen()) return false;

        $evaluatorProfile = $evaluator->facultyProfile;
        if (!$evaluatorProfile) return false;

        if ($evaluatorProfile->id === $evaluatee->id) return false;

        if ($evaluatorProfile->department_id !== $evaluatee->department_id) return false;

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (!$period) return false;

        $alreadyDone = FacultyPeerEvaluationFeedback::where('evaluator_faculty_id', $evaluatorProfile->id)
            ->where('evaluatee_faculty_id', $evaluatee->id)
            ->where('evaluation_type', 'peer')
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return !$alreadyDone;
    }
}
