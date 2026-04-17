<?php

namespace App\Policies;

use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\StudentEvaluationSubjectService;

class EvaluationPolicy
{
    /**
     * Student can evaluate a faculty member for a specific subject if:
     * 1. Evaluation period is open
     * 2. Subject matches the open period’s semester and school year, student course/year level/section (same as the student subject list)
     * 3. Faculty is assigned to teach that subject
     * 4. Student hasn't already submitted this evaluation
     */
    public function submitStudentEvaluation(User $student, FacultyProfile $faculty, Subject $subject): bool
    {
        if (! EvaluationService::isEvaluationOpen()) {
            return false;
        }

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (! $period) {
            return false;
        }

        $studentProfile = $student->studentProfile;
        if (! $studentProfile) {
            return false;
        }

        if (! StudentEvaluationSubjectService::subjectAppliesToStudent($subject, $studentProfile, $period)) {
            return false;
        }

        $teaches = SubjectAssignment::where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->exists();
        if (! $teaches) {
            return false;
        }

        $alreadyEvaluated = EvaluationFeedback::where('student_id', $student->id)
            ->where('faculty_id', $faculty->id)
            ->where('subject_id', $subject->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return ! $alreadyEvaluated;
    }

    /**
     * Dean/Head can evaluate faculty in their department. VP Academic and School President
     * evaluate all Dean/Head personnel institution-wide (teaching deans and non-teaching administrators).
     */
    public function submitDeanEvaluation(User $evaluator, FacultyProfile $faculty): bool
    {
        if (! EvaluationService::isEvaluationOpen()) {
            return false;
        }

        $period = EvaluationService::getOpenEvaluationPeriod();
        if (! $period) {
            return false;
        }

        $faculty->loadMissing('user');
        $evaluateeUser = $faculty->user;
        if (! $evaluateeUser) {
            return false;
        }

        if ($evaluator->id === $evaluateeUser->id) {
            return false;
        }

        if (EvaluationService::isInstitutionLeaderDeanEvaluator($evaluator)) {
            $isLeaderByPosition = $faculty->isDeanOrDepartmentHead();
            $isLeaderByRole = $evaluateeUser->hasRole(['dean', 'head']);
            if (! $isLeaderByPosition && ! $isLeaderByRole) {
                return false;
            }
        } elseif ($faculty->department_id !== $evaluator->department_id) {
            return false;
        }

        $alreadyEvaluated = DeanEvaluationFeedback::where('dean_user_id', $evaluator->id)
            ->where('faculty_id', $faculty->id)
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->exists();

        return ! $alreadyEvaluated;
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
