<?php

namespace App\Policies;

use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Http\Controllers\Traits\NormalizesComparableValues;
use App\Models\User;
use App\Services\EvaluationService;

class EvaluationPolicy
{
    use NormalizesComparableValues;
    /**
     * Student can evaluate a faculty member for a specific subject if:
     * 1. Evaluation period is open
     * 2. The subject is scheduled for the open evaluation period
     *    (matching semester + school year)
     * 3. The subject's course matches the student's course
     * 4. The subject's year level matches the student's year level
     * 5. Faculty is assigned to teach that subject
     * 6. Student hasn't already submitted this evaluation for this term
     *
     * Section gating is intentionally relaxed because section data is
     * inconsistent across the dataset; course + year level is sufficient
     * to scope the cohort.
     */
    public function submitStudentEvaluation(User $student, FacultyProfile $faculty, Subject $subject): bool
    {
        if (!EvaluationService::isEvaluationOpen()) return false;

        $studentProfile = $student->studentProfile;
        if (!$studentProfile) return false;

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (!$period) return false;

        // Subject must belong to the open evaluation period.
        $subjectSemester = $this->normalizeComparableValue((string) $subject->semester);
        $periodSemester  = $this->normalizeComparableValue((string) $period->semester);
        if ($subjectSemester === '' || $subjectSemester !== $periodSemester) {
            return false;
        }
        if ((string) $subject->school_year !== (string) $period->school_year) {
            return false;
        }

        // Course must match.
        $subjectCourse = $this->normalizeComparableValue((string) $subject->course);
        $studentCourse = $this->normalizeComparableValue((string) $studentProfile->course);
        if ($subjectCourse === '' || $studentCourse === '' || $subjectCourse !== $studentCourse) {
            return false;
        }

        // Year level must match.
        $subjectYear = trim((string) $subject->year_level);
        $studentYear = trim((string) $studentProfile->year_level);
        if ($subjectYear === '' || $studentYear === '' || $subjectYear !== $studentYear) {
            return false;
        }

        $teaches = SubjectAssignment::where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->exists();
        if (!$teaches) return false;

        $alreadyEvaluated = EvaluationFeedback::where('student_id', $student->id)
            ->where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return !$alreadyEvaluated;
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
