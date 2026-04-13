<?php

namespace Database\Seeders;

use App\Models\DeanEvaluationAnswer;
use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationFeedback;
use App\Models\EvaluationPeriod;
use App\Models\FacultyPeerEvaluationAnswer;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\FacultyProfile;
use App\Models\Question;
use App\Models\SelfEvaluationResult;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Populates synthetic evaluation scores for testing: student, dean, self, and peer components,
 * for every active faculty/head profile and every {@see EvaluationPeriod} in the database.
 *
 * Teaching, non-teaching, and Dean/Head personnel types use the correct question sets (Likert 1–5;
 * dean recommendation 1–3 where applicable).
 *
 * Idempotent for re-runs: removes rows previously created by this seeder (comment prefix).
 *
 *   php artisan db:seed --class="Database\\Seeders\\SampleEvaluationDataSeeder"
 */
class SampleEvaluationDataSeeder extends Seeder
{
    private const COMMENT = '[SAMPLE-EVAL]';

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, Question>> */
    private array $questionsByEvaluatorAndPersonnel = [];

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, Question>> */
    private array $deanQuestionsByPersonnel = [];

    /** @var array<int, Collection<int, object{student_id: int, subject_id: int}>> */
    private array $studentSubjectLinksCache = [];

    /** @var array<string, User|null> */
    private array $deanUserByDepartmentCache = [];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $this->purgeSampleData();

        $periods = EvaluationPeriod::query()
            ->orderBy('school_year')
            ->orderByRaw("CASE semester WHEN '1st' THEN 1 WHEN '2nd' THEN 2 WHEN 'Summer' THEN 3 ELSE 9 END")
            ->get()
            ->values();

        if ($periods->isEmpty()) {
            $this->command->warn('No evaluation periods found. Add periods first.');

            return;
        }

        $profiles = FacultyProfile::query()
            ->with(['user', 'department'])
            ->whereHas('user', function ($q) {
                $q->where(function ($qq) {
                        $qq->whereJsonContains('roles', 'faculty')
                           ->orWhereJsonContains('roles', 'head');
                    })
                    ->where('is_active', true);
            })
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            $this->command->warn('No faculty profiles found.');

            return;
        }

        $byDept = $profiles->groupBy('department_id');
        $periodIndex = 0;
        $totalPeriods = $periods->count();

        foreach ($periods as $period) {
            $sy  = $period->school_year;
            $sem = $period->semester;

            $this->command->info('Seeding period '.($periodIndex + 1)."/{$totalPeriods}: {$sy} {$sem}");

            foreach ($profiles as $profile) {
                $user = $profile->user;
                if (! $user) {
                    continue;
                }

                $personnel = $profile->evaluationCriteriaPersonnelType();
                $peer      = $this->pickPeerProfile($profile, $byDept);

                $this->seedStudentEvaluation($profile, $personnel, $sy, $sem, $periodIndex);
                $this->seedDeanEvaluation($profile, $user, $personnel, $sy, $sem);
                $this->seedSelfEvaluation($profile, $personnel, $sy, $sem);
                $this->seedPeerEvaluation($profile, $peer, $personnel, $sy, $sem);
            }

            $periodIndex++;
        }

        $this->studentSubjectLinksCache = [];
        $this->deanUserByDepartmentCache = [];

        EvaluationService::clearCache();

        $this->command->info('Sample evaluation data seeded for '.$profiles->count().' personnel × '.$periods->count().' periods.');
    }

    private function purgeSampleData(): void
    {
        $sampleComment = self::COMMENT.'%';

        EvaluationAnswer::query()->whereExists(function ($q) use ($sampleComment) {
            $q->select(DB::raw('1'))
                ->from('evaluation_feedback as ef')
                ->whereColumn('ef.student_id', 'evaluation_answers.student_id')
                ->whereColumn('ef.faculty_id', 'evaluation_answers.faculty_id')
                ->whereColumn('ef.subject_id', 'evaluation_answers.subject_id')
                ->where('ef.comment', 'like', $sampleComment);
        })->delete();
        EvaluationFeedback::query()->where('comment', 'like', $sampleComment)->delete();

        DeanEvaluationAnswer::query()->whereExists(function ($q) use ($sampleComment) {
            $q->select(DB::raw('1'))
                ->from('dean_evaluation_feedback as def')
                ->whereColumn('def.dean_user_id', 'dean_evaluation_answers.dean_user_id')
                ->whereColumn('def.faculty_id', 'dean_evaluation_answers.faculty_id')
                ->whereColumn('def.semester', 'dean_evaluation_answers.semester')
                ->whereColumn('def.school_year', 'dean_evaluation_answers.school_year')
                ->where('def.comment', 'like', $sampleComment);
        })->delete();
        DeanEvaluationFeedback::query()->where('comment', 'like', $sampleComment)->delete();

        FacultyPeerEvaluationAnswer::query()->whereExists(function ($q) use ($sampleComment) {
            $q->select(DB::raw('1'))
                ->from('faculty_peer_evaluation_feedback as fpf')
                ->whereColumn('fpf.evaluator_faculty_id', 'faculty_peer_evaluation_answers.evaluator_faculty_id')
                ->whereColumn('fpf.evaluatee_faculty_id', 'faculty_peer_evaluation_answers.evaluatee_faculty_id')
                ->whereColumn('fpf.evaluation_type', 'faculty_peer_evaluation_answers.evaluation_type')
                ->whereColumn('fpf.semester', 'faculty_peer_evaluation_answers.semester')
                ->whereColumn('fpf.school_year', 'faculty_peer_evaluation_answers.school_year')
                ->where('fpf.comment', 'like', $sampleComment);
        })->delete();
        FacultyPeerEvaluationFeedback::query()->where('comment', 'like', $sampleComment)->delete();

        FacultyPeerEvaluationAnswer::query()->whereExists(function ($q) use ($sampleComment) {
            $q->select(DB::raw('1'))
                ->from('self_evaluation_results as ser')
                ->whereColumn('ser.faculty_id', 'faculty_peer_evaluation_answers.evaluator_faculty_id')
                ->whereColumn('ser.faculty_id', 'faculty_peer_evaluation_answers.evaluatee_faculty_id')
                ->where('faculty_peer_evaluation_answers.evaluation_type', 'self')
                ->whereColumn('ser.semester', 'faculty_peer_evaluation_answers.semester')
                ->whereColumn('ser.school_year', 'faculty_peer_evaluation_answers.school_year')
                ->where('ser.comment', 'like', $sampleComment);
        })->delete();

        SelfEvaluationResult::query()->where('comment', 'like', $sampleComment)->delete();
    }

    private function pickPeerProfile(FacultyProfile $profile, Collection $byDept): ?FacultyProfile
    {
        $deptId = $profile->department_id;
        if ($deptId === null) {
            return null;
        }

        $others = $byDept->get($deptId, collect())->where('id', '!=', $profile->id)->values();
        if ($others->isEmpty()) {
            return null;
        }

        return $others[$profile->id % $others->count()];
    }

    /**
     * @return Collection<int, object{student_id: int, subject_id: int}>
     */
    private function studentSubjectLinksForFaculty(int $facultyProfileId): Collection
    {
        if (! isset($this->studentSubjectLinksCache[$facultyProfileId])) {
            $this->studentSubjectLinksCache[$facultyProfileId] = DB::table('student_subject_assignments as ssa')
                ->join('student_profiles as sp', 'sp.id', '=', 'ssa.student_profile_id')
                ->join('subjects as sub', 'sub.id', '=', 'ssa.subject_id')
                ->join('subject_assignments as sa', 'sa.subject_id', '=', 'sub.id')
                ->join('users as su', 'su.id', '=', 'sp.user_id')
                ->where('sa.faculty_id', $facultyProfileId)
                ->where('su.is_active', true)
                ->select('su.id as student_id', 'sub.id as subject_id')
                ->distinct()
                ->orderBy('sub.id')
                ->limit(24)
                ->get();
        }

        return $this->studentSubjectLinksCache[$facultyProfileId];
    }

    private function seedStudentEvaluation(
        FacultyProfile $profile,
        string $personnelType,
        string $schoolYear,
        string $semester,
        int $periodIndex
    ): void {
        $links = $this->studentSubjectLinksForFaculty($profile->id);
        if ($links->isEmpty()) {
            return;
        }

        $link = $links[$periodIndex % $links->count()];
        $studentId = (int) $link->student_id;
        $subjectId = (int) $link->subject_id;

        $questions = $this->questionsFor('student', $personnelType);
        if ($questions->isEmpty()) {
            return;
        }

        $existingFb = EvaluationFeedback::query()
            ->where('student_id', $studentId)
            ->where('faculty_id', $profile->id)
            ->where('subject_id', $subjectId)
            ->first();
        if ($existingFb && ! str_starts_with((string) $existingFb->comment, self::COMMENT)) {
            return;
        }

        $ratings = [];
        foreach ($questions as $q) {
            $ratings[$q->id] = $this->pseudoRating(
                $profile->id,
                $schoolYear,
                $semester,
                $q->id,
                'stu'
            );
        }

        $avg = round(array_sum($ratings) / max(count($ratings), 1), 2);
        $level = EvaluationService::getPerformanceLevel($avg, $personnelType);

        EvaluationFeedback::query()->updateOrCreate(
            [
                'student_id' => $studentId,
                'faculty_id' => $profile->id,
                'subject_id' => $subjectId,
            ],
            [
                'evaluator_type'     => 'student',
                'school_year'        => $schoolYear,
                'semester'           => $semester,
                'comment'            => self::COMMENT.' Student evaluation for testing.',
                'sentiment_label'    => null,
                'total_average'      => $avg,
                'performance_level'  => $level,
                'created_at'         => now(),
            ]
        );

        $now = now();
        EvaluationAnswer::query()
            ->where('student_id', $studentId)
            ->where('faculty_id', $profile->id)
            ->where('subject_id', $subjectId)
            ->delete();

        $answerRows = [];
        foreach ($ratings as $questionId => $rating) {
            $answerRows[] = [
                'student_id'  => $studentId,
                'faculty_id'  => $profile->id,
                'subject_id'  => $subjectId,
                'question_id' => $questionId,
                'rating'      => $rating,
                'created_at'  => $now,
            ];
        }
        if ($answerRows !== []) {
            EvaluationAnswer::query()->insert($answerRows);
        }
    }

    private function seedDeanEvaluation(
        FacultyProfile $profile,
        User $facultyUser,
        string $personnelType,
        string $schoolYear,
        string $semester
    ): void {
        $deptId = $facultyUser->department_id;
        $deptKey = $deptId === null ? '' : (string) $deptId;
        if (! array_key_exists($deptKey, $this->deanUserByDepartmentCache)) {
            $this->deanUserByDepartmentCache[$deptKey] = User::query()
                ->where(function ($q) {
                    $q->whereJsonContains('roles', 'dean')
                       ->orWhereJsonContains('roles', 'head');
                })
                ->where('department_id', $deptId)
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN JSON_CONTAINS(roles, '\"dean\"') THEN 1 WHEN JSON_CONTAINS(roles, '\"head\"') THEN 2 ELSE 9 END")
                ->first();
        }

        $deanUser = $this->deanUserByDepartmentCache[$deptKey];

        if (! $deanUser || $deanUser->id === $facultyUser->id) {
            return;
        }

        $questions = $this->deanQuestionsFor($personnelType);

        if ($questions->isEmpty()) {
            return;
        }

        $existingDeanFb = DeanEvaluationFeedback::query()
            ->where('dean_user_id', $deanUser->id)
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->first();
        if ($existingDeanFb && ! str_starts_with((string) $existingDeanFb->comment, self::COMMENT)) {
            return;
        }

        $likertSum = 0;
        $likertN   = 0;
        $recommendation = null;
        $now       = now();
        $deanRows  = [];

        foreach ($questions as $q) {
            $isRec = ($q->response_type ?? 'likert') === 'dean_recommendation';
            $rating = $isRec
                ? (1 + (crc32($profile->id.$schoolYear.$semester.'rec') % 3))
                : $this->pseudoRating($profile->id, $schoolYear, $semester, $q->id, 'dean');

            $deanRows[] = [
                'dean_user_id' => $deanUser->id,
                'faculty_id'   => $profile->id,
                'question_id'  => $q->id,
                'semester'     => $semester,
                'school_year'  => $schoolYear,
                'criteria_id'  => $q->criteria_id,
                'rating'       => $rating,
                'created_at'   => $now,
            ];

            if ($isRec) {
                $recommendation = match ($rating) {
                    1       => 'retention',
                    2       => 'promotion',
                    default => 'reassignment',
                };
            } else {
                $likertSum += $rating;
                $likertN++;
            }
        }

        DeanEvaluationAnswer::query()->upsert(
            $deanRows,
            ['dean_user_id', 'faculty_id', 'question_id', 'semester', 'school_year'],
            ['criteria_id', 'rating', 'created_at']
        );

        $avg = $likertN > 0 ? round($likertSum / $likertN, 2) : 0;
        $level = $likertN > 0
            ? EvaluationService::getPerformanceLevel($avg, $personnelType)
            : EvaluationService::getPerformanceLevel(3.0, $personnelType);

        DeanEvaluationFeedback::query()->updateOrCreate(
            [
                'dean_user_id' => $deanUser->id,
                'faculty_id'   => $profile->id,
                'semester'     => $semester,
                'school_year'  => $schoolYear,
            ],
            [
                'comment'             => self::COMMENT.' Dean evaluation for testing.',
                'recommendation'      => $recommendation,
                'total_average'       => $avg,
                'performance_level'   => $level,
                'weighted_percentage' => round($avg * 0.40, 2),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]
        );
    }

    private function seedSelfEvaluation(
        FacultyProfile $profile,
        string $personnelType,
        string $schoolYear,
        string $semester
    ): void {
        $questions = $this->questionsFor('self', $personnelType);
        if ($questions->isEmpty()) {
            return;
        }

        $existingSelf = SelfEvaluationResult::query()
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->first();
        if ($existingSelf && ! str_starts_with((string) $existingSelf->comment, self::COMMENT)) {
            return;
        }

        $sum  = 0;
        $now  = now();
        $rows = [];
        foreach ($questions as $q) {
            $rating = $this->pseudoRating($profile->id, $schoolYear, $semester, $q->id, 'self');
            $sum += $rating;
            $rows[] = [
                'evaluator_faculty_id'  => $profile->id,
                'evaluatee_faculty_id' => $profile->id,
                'evaluation_type'      => 'self',
                'question_id'          => $q->id,
                'semester'             => $semester,
                'school_year'          => $schoolYear,
                'criteria_id'          => $q->criteria_id,
                'rating'               => $rating,
                'created_at'           => $now,
            ];
        }

        FacultyPeerEvaluationAnswer::query()->upsert(
            $rows,
            ['evaluator_faculty_id', 'evaluatee_faculty_id', 'evaluation_type', 'question_id', 'semester', 'school_year'],
            ['criteria_id', 'rating', 'created_at']
        );

        $avg   = round($sum / $questions->count(), 2);
        $level = EvaluationService::getPerformanceLevel($avg, $personnelType);

        SelfEvaluationResult::query()
            ->where('faculty_id', $profile->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->where('comment', 'like', self::COMMENT.'%')
            ->delete();

        SelfEvaluationResult::query()->create([
            'faculty_id'         => $profile->id,
            'department_id'      => $profile->user?->department_id ?? $profile->department_id,
            'semester'           => $semester,
            'school_year'        => $schoolYear,
            'total_average'      => $avg,
            'performance_level'  => $level,
            'comment'            => self::COMMENT.' Self-evaluation for testing.',
            'created_at'         => now(),
        ]);
    }

    private function seedPeerEvaluation(
        FacultyProfile $evaluatee,
        ?FacultyProfile $evaluator,
        string $personnelType,
        string $schoolYear,
        string $semester
    ): void {
        if (! $evaluator || $evaluator->id === $evaluatee->id) {
            return;
        }

        $questions = $this->questionsFor('peer', $personnelType);
        if ($questions->isEmpty()) {
            return;
        }

        $existingPeerFb = FacultyPeerEvaluationFeedback::query()
            ->where('evaluator_faculty_id', $evaluator->id)
            ->where('evaluatee_faculty_id', $evaluatee->id)
            ->where('evaluation_type', 'peer')
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->first();
        if ($existingPeerFb && ! str_starts_with((string) $existingPeerFb->comment, self::COMMENT)) {
            return;
        }

        $sum  = 0;
        $now  = now();
        $rows = [];
        foreach ($questions as $q) {
            $rating = $this->pseudoRating($evaluatee->id, $schoolYear, $semester, $q->id, 'peer'.$evaluator->id);
            $sum += $rating;
            $rows[] = [
                'evaluator_faculty_id'  => $evaluator->id,
                'evaluatee_faculty_id' => $evaluatee->id,
                'evaluation_type'      => 'peer',
                'question_id'          => $q->id,
                'semester'             => $semester,
                'school_year'          => $schoolYear,
                'criteria_id'          => $q->criteria_id,
                'rating'               => $rating,
                'created_at'           => $now,
            ];
        }

        FacultyPeerEvaluationAnswer::query()->upsert(
            $rows,
            ['evaluator_faculty_id', 'evaluatee_faculty_id', 'evaluation_type', 'question_id', 'semester', 'school_year'],
            ['criteria_id', 'rating', 'created_at']
        );

        $avg   = round($sum / $questions->count(), 2);
        $level = EvaluationService::getPerformanceLevel($avg, $personnelType);

        FacultyPeerEvaluationFeedback::query()->updateOrCreate(
            [
                'evaluator_faculty_id'  => $evaluator->id,
                'evaluatee_faculty_id' => $evaluatee->id,
                'evaluation_type'      => 'peer',
                'semester'             => $semester,
                'school_year'          => $schoolYear,
            ],
            [
                'comment'              => self::COMMENT.' Peer evaluation for testing.',
                'total_average'        => $avg,
                'performance_level'    => $level,
                'weighted_percentage'  => round($avg * 0.10, 2),
                'created_at'           => now(),
            ]
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Question>
     */
    private function questionsFor(string $evaluatorGroup, string $personnelType)
    {
        $key = $evaluatorGroup.'|'.$personnelType;
        if (! isset($this->questionsByEvaluatorAndPersonnel[$key])) {
            $this->questionsByEvaluatorAndPersonnel[$key] = Question::query()
                ->whereHas('criterion', function ($q) use ($evaluatorGroup, $personnelType) {
                    $q->forEvaluatorGroup($evaluatorGroup)->forPersonnelType($personnelType);
                })
                ->where(function ($q) {
                    $q->whereNull('response_type')
                        ->orWhere('response_type', 'likert');
                })
                ->orderBy('id')
                ->get();
        }

        return $this->questionsByEvaluatorAndPersonnel[$key];
    }

    /**
     * Dean evaluations include dean_recommendation (1–3); not filtered to likert-only.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Question>
     */
    private function deanQuestionsFor(string $personnelType)
    {
        if (! isset($this->deanQuestionsByPersonnel[$personnelType])) {
            $this->deanQuestionsByPersonnel[$personnelType] = Question::query()
                ->whereHas('criterion', function ($q) use ($personnelType) {
                    $q->forEvaluatorGroup('dean')->forPersonnelType($personnelType);
                })
                ->orderBy('id')
                ->get();
        }

        return $this->deanQuestionsByPersonnel[$personnelType];
    }

    private function pseudoRating(int $profileId, string $sy, string $sem, int $questionId, string $salt): int
    {
        $h    = crc32($profileId.'|'.$sy.'|'.$sem.'|'.$questionId.'|'.$salt) % 101;
        $base = 2 + (int) floor($h / 25);

        return max(1, min(5, $base));
    }
}
