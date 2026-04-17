<?php

namespace App\Services;

use App\Jobs\RunStudentPromotionForPeriodJob;
use App\Models\EvaluationFeedback;
use App\Models\EvaluationPeriod;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    private const MAX_STUDENT_YEAR_LEVEL = 4;

    public static function isDeanHeadEvaluateePersonnelType(string $personnelType): bool
    {
        return in_array($personnelType, ['dean_head_teaching', 'dean_head_non_teaching'], true);
    }

    /**
     * Map Dean/Head evaluatee pivots to teaching / non-teaching where a single teaching/non-teaching
     * slice is required (e.g. legacy paths). Dean/Head Likert labels and overall performance bands
     * use {@see likertScaleLabels} and {@see getPerformanceLevel} before this normalization applies.
     */
    public static function normalizeEvaluateePersonnelForScoring(string $personnelType): string
    {
        return match ($personnelType) {
            'dean_head_non_teaching' => 'non-teaching',
            'dean_head_teaching' => 'teaching',
            default => $personnelType,
        };
    }

    /**
     * Numeric labels for 1–5 Likert items: derived from the evaluatee's department
     * (non-teaching department → staff scale; teaching department → instruction scale).
     *
     * @return array<int, string>
     */
    public static function likertScaleLabels(string $personnelType = 'teaching'): array
    {
        if (self::isDeanHeadEvaluateePersonnelType($personnelType)) {
            return [
                5 => 'Excellent',
                4 => 'Above Average',
                3 => 'Average',
                2 => 'Below Average',
                1 => 'Unsatisfactory',
            ];
        }

        $personnelType = self::normalizeEvaluateePersonnelForScoring($personnelType);

        if ($personnelType === 'non-teaching') {
            return [
                1 => 'Poor',
                2 => 'Below Average',
                3 => 'Average',
                4 => 'Above Average',
                5 => 'Outstanding',
            ];
        }

        return [
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Satisfactory',
            4 => 'Very Satisfactory',
            5 => 'Excellent',
        ];
    }

    public static function getPerformanceLevel(float $avg, string $evaluateePersonnelType = 'teaching'): string
    {
        if (self::isDeanHeadEvaluateePersonnelType($evaluateePersonnelType)) {
            if ($avg >= 4.5) {
                return 'Excellent';
            }
            if ($avg >= 3.5) {
                return 'Above Average';
            }
            if ($avg >= 2.5) {
                return 'Average';
            }
            if ($avg >= 1.5) {
                return 'Below Average';
            }

            return 'Unsatisfactory';
        }

        $evaluateePersonnelType = self::normalizeEvaluateePersonnelForScoring($evaluateePersonnelType);

        if ($evaluateePersonnelType === 'non-teaching') {
            if ($avg >= 4.5) return 'Outstanding';
            if ($avg >= 3.5) return 'Above Average';
            if ($avg >= 2.5) return 'Average';
            if ($avg >= 1.5) return 'Below Average';

            return 'Poor';
        }

        if ($avg >= 4.5) return 'Excellent';
        if ($avg >= 3.5) return 'Very Satisfactory';
        if ($avg >= 2.5) return 'Satisfactory';
        if ($avg >= 1.5) return 'Fair';

        return 'Poor';
    }

    /**
     * Whether overall (or per-item) performance falls in tiers that trigger intervention planning.
     * Teaching: Fair, Poor. Non-teaching: Below Average, Poor. Dean/Head: Below Average, Unsatisfactory.
     */
    public static function qualifiesForPerformanceIntervention(?string $performanceLevel, string $evaluateePersonnelType): bool
    {
        if ($performanceLevel === null || $performanceLevel === '') {
            return false;
        }

        if (self::isDeanHeadEvaluateePersonnelType($evaluateePersonnelType)) {
            return in_array($performanceLevel, ['Below Average', 'Unsatisfactory'], true);
        }

        if (self::normalizeEvaluateePersonnelForScoring($evaluateePersonnelType) === 'non-teaching') {
            return in_array($performanceLevel, ['Below Average', 'Poor'], true);
        }

        return in_array($performanceLevel, ['Fair', 'Poor'], true);
    }

    /**
     * Labels for HR reports — must stay aligned with {@see qualifiesForPerformanceIntervention()}.
     *
     * @return list<array{category: string, levels: string}>
     */
    public static function interventionLowBandReportLines(): array
    {
        return [
            [
                'category' => 'Teaching (instructional rubric)',
                'levels' => 'Fair, Poor',
            ],
            [
                'category' => 'Non-teaching personnel',
                'levels' => 'Below Average, Poor',
            ],
            [
                'category' => 'Dean, Head, or Administrator (academic administrators rubric)',
                'levels' => 'Below Average, Unsatisfactory',
            ],
        ];
    }

    public static function performanceBadgeClass(string $level): string
    {
        return match ($level) {
            'Excellent'           => 'bg-green-100 text-green-700',
            'Outstanding'         => 'bg-green-100 text-green-700',
            'Very Satisfactory'   => 'bg-blue-100 text-blue-700',
            'Above Average'       => 'bg-blue-100 text-blue-700',
            'Satisfactory'        => 'bg-amber-100 text-amber-700',
            'Average'             => 'bg-amber-100 text-amber-700',
            'Fair'                => 'bg-orange-100 text-orange-700',
            'Below Average'       => 'bg-orange-100 text-orange-700',
            'Poor'                => 'bg-red-100 text-red-700',
            'Unsatisfactory'      => 'bg-red-100 text-red-800',
            default               => 'bg-gray-100 text-gray-700',
        };
    }

    public static function computeWeightedResult(
        ?float $studentAvg,
        ?float $deanAvg,
        ?float $selfAvg,
        ?float $peerAvg,
        string $evaluateePersonnelType = 'teaching'
    ): array {
        $weights = ['student' => 0.40, 'dean' => 0.40, 'self' => 0.10, 'peer' => 0.10];
        $scores  = ['student' => $studentAvg, 'dean' => $deanAvg, 'self' => $selfAvg, 'peer' => $peerAvg];

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($scores as $key => $score) {
            if ($score !== null && $score > 0) {
                $weightedSum += $score * $weights[$key];
                $totalWeight += $weights[$key];
            }
        }

        $weightedAvg = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;

        return [
            'weighted_average'  => $weightedAvg,
            'performance_level' => self::getPerformanceLevel($weightedAvg, $evaluateePersonnelType),
            'components'        => $scores,
        ];
    }

    /**
     * Labels used in analytics distribution charts (teaching + non-teaching scales; Poor appears once).
     *
     * @return list<string>
     */
    public static function analyticsPerformanceLevelLabels(): array
    {
        return [
            'Excellent',
            'Very Satisfactory',
            'Satisfactory',
            'Fair',
            'Outstanding',
            'Above Average',
            'Average',
            'Below Average',
            'Poor',
            'Unsatisfactory',
        ];
    }

    public static function getOpenEvaluationPeriod(): ?EvaluationPeriod
    {
        self::closeEndedOpenPeriodsAndPromoteStudents();

        $id = Cache::remember('open_evaluation_period_id', 60, function () {
            return EvaluationPeriod::where('is_open', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->value('id');
        });

        return $id ? EvaluationPeriod::find($id) : null;
    }

    public static function clearCache(): void
    {
        Cache::forget('open_evaluation_period_id');
    }

    public static function isEvaluationOpen(): bool
    {
        return self::getOpenEvaluationPeriod() !== null;
    }

    /**
     * Roles that evaluate Dean/Head personnel across all departments (VP Academic, School President).
     *
     * @return list<string>
     */
    public static function institutionLeaderDeanEvaluatorRoles(): array
    {
        return ['vp_acad', 'school_president'];
    }

    public static function isInstitutionLeaderDeanEvaluator(?User $user): bool
    {
        return $user !== null
            && $user->hasRole(self::institutionLeaderDeanEvaluatorRoles());
    }

    /**
     * Run year-level promotion when a period is closed (manual close, replaced by another open period, or overdue).
     */
    public static function runStudentPromotionForClosedPeriod(EvaluationPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            self::promoteStudentsForPeriod($period);
        });
    }

    private static function closeEndedOpenPeriodsAndPromoteStudents(): void
    {
        $endedOpenPeriods = EvaluationPeriod::query()
            ->where('is_open', true)
            ->whereDate('end_date', '<', now()->toDateString())
            ->orderBy('end_date')
            ->orderBy('id')
            ->get();

        if ($endedOpenPeriods->isEmpty()) {
            return;
        }

        foreach ($endedOpenPeriods as $period) {
            $periodId = $period->id;
            $period->update([
                'is_open' => false,
            ]);

            if (app()->runningInConsole()) {
                $fresh = EvaluationPeriod::query()->find($periodId);
                if ($fresh !== null) {
                    self::runStudentPromotionForClosedPeriod($fresh);
                }
            } else {
                RunStudentPromotionForPeriodJob::dispatch($periodId)->afterResponse();
            }
        }

        self::clearCache();
    }

    /**
     * Year-level promotion when a period closes (e.g. admin opens a new school year and the prior period is closed).
     *
     * - Evaluations are **per semester** (feedback is stored with that period’s semester + school_year).
     * - **Promotion** advances year level **once per school year**: by default only when the **closed period is 2nd semester**
     *   (end of school year), not after 1st-semester-only closes. Set EVALUATION_PROMOTION_SECOND_SEMESTER_ONLY=false to test.
     * - Eligibility: student must have submitted **more than half** of their **subject–faculty assignment slots**
     *   (same totals as the evaluate UI: each assigned faculty per subject = one slot).
     */
    private static function promoteStudentsForPeriod(EvaluationPeriod $period): void
    {
        if (! self::promotionSemesterGate($period)) {
            return;
        }

        StudentProfile::query()
            ->with('user')
            ->where(function ($query) use ($period) {
                $query->whereNull('last_promoted_school_year')
                    ->orWhere('last_promoted_school_year', '!=', $period->school_year);
            })
            ->orderBy('id')
            ->chunkById(300, function ($profiles) use ($period) {
                foreach ($profiles as $profile) {
                    $user = $profile->user;
                    if ($user === null || ! $user->isStudent()) {
                        continue;
                    }

                    $requiredSlots = StudentEvaluationSubjectService::countEvaluationSlotsForStudent($user, $profile, $period);
                    if ($requiredSlots === 0) {
                        continue;
                    }

                    $completedSlots = self::countCompletedEvaluationSlotsForPeriod($user->id, $period);
                    // Strictly more than half of assignment slots (per-semester counts for this period)
                    if (($completedSlots * 2) <= $requiredSlots) {
                        continue;
                    }

                    $currentYear = (int) trim((string) $profile->year_level);
                    $normalizedYear = $currentYear > 0 ? $currentYear : 1;
                    $nextYear = min($normalizedYear + 1, self::MAX_STUDENT_YEAR_LEVEL);

                    $profile->update([
                        'year_level' => (string) $nextYear,
                        'last_promoted_school_year' => (string) $period->school_year,
                        'last_promoted_semester' => (string) $period->semester,
                    ]);
                }
            });
    }

    /**
     * Distinct subject–faculty pairs with a student evaluation row for this period.
     */
    private static function countCompletedEvaluationSlotsForPeriod(int $studentUserId, EvaluationPeriod $period): int
    {
        return (int) EvaluationFeedback::query()
            ->where('student_id', $studentUserId)
            ->where('evaluator_type', 'student')
            ->where('semester', $period->semester)
            ->where('school_year', $period->school_year)
            ->selectRaw('COUNT(DISTINCT CONCAT(subject_id, ?, faculty_id)) as c', [':'])
            ->value('c');
    }

    private static function promotionSemesterGate(EvaluationPeriod $period): bool
    {
        $onlySecond = filter_var(
            env('EVALUATION_PROMOTION_SECOND_SEMESTER_ONLY', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $onlySecond) {
            return true;
        }

        return self::periodIsSecondSemester($period);
    }

    private static function periodIsSecondSemester(EvaluationPeriod $period): bool
    {
        $v = mb_strtolower(trim((string) $period->semester));

        return $v !== ''
            && (str_contains($v, '2nd') || str_contains($v, 'second'));
    }
}
