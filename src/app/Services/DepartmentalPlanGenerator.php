<?php

namespace App\Services;

use App\Http\Controllers\Traits\LoadsFacultyPerformance;
use App\Models\Department;
use App\Models\DeanEvaluationFeedback;
use App\Models\DepartmentalPlan;
use App\Models\DepartmentalPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds a Departmental Plan for a (department, period, dean) using the
 * existing performance pipeline and intervention mapping.
 *
 *   - Reuses LoadsFacultyPerformance to compute per-faculty weighted result
 *     (student / dean / self / peer averages → weighted_average + level).
 *   - Maps each faculty's level → InterventionRecommendationMapper output
 *     (intervention, priority, suggested programs).
 *   - Layers in the dean's own per-faculty recommendation choice from
 *     DeanEvaluationFeedback (retention / promotion / reassignment) so the
 *     plan reflects what the dean actually said, not just performance bucket.
 *   - Emits one item per faculty plus a department-wide synthesis item.
 *
 * Persists a new plan (supersedes the dean's prior draft for the same period).
 */
class DepartmentalPlanGenerator
{
    use LoadsFacultyPerformance;

    public function generate(
        Department $department,
        User $dean,
        string $schoolYear,
        string $semester
    ): DepartmentalPlan {
        $rows = $this->loadFacultyWithPerformance(
            departmentId: $department->id,
            semester:     $semester,
            schoolYear:   $schoolYear,
            deanUserId:   $dean->id,
        );

        // Dean's per-faculty recommendation choices for this period
        $deanRecs = DeanEvaluationFeedback::where('dean_user_id', $dean->id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->pluck('recommendation', 'faculty_id');

        return DB::transaction(function () use ($rows, $deanRecs, $department, $dean, $schoolYear, $semester) {
            // Supersede any prior draft for this dean+dept+period so the dean
            // sees the latest plan as canonical without losing history.
            DepartmentalPlan::where('department_id', $department->id)
                ->where('dean_user_id', $dean->id)
                ->where('school_year', $schoolYear)
                ->where('semester', $semester)
                ->where('status', 'draft')
                ->update(['status' => 'archived']);

            $rollUp = $this->buildRollUp($rows, $deanRecs);

            $plan = DepartmentalPlan::create([
                'department_id'  => $department->id,
                'dean_user_id'   => $dean->id,
                'school_year'    => $schoolYear,
                'semester'       => $semester,
                'summary'        => $this->buildSummary($department, $rollUp),
                'roll_up'        => $rollUp,
                'generated_from' => [
                    'period'         => ['school_year' => $schoolYear, 'semester' => $semester],
                    'faculty_count'  => $rows->count(),
                    'evaluated_count'=> $rollUp['evaluated'],
                ],
                'model_version'  => 'dept-plan-v1',
                'status'         => 'draft',
                'generated_by'   => $dean->id,
            ]);

            foreach ($rows as $row) {
                $profileId = $row['profile']->id;
                $level     = $row['performance_level'] ?? null;
                $rec       = InterventionRecommendationMapper::recommend($level);
                $deanChoice = $deanRecs[$profileId] ?? null;

                [$category, $title, $description, $priority, $programs] =
                    $this->deriveItem($row, $rec, $deanChoice);

                DepartmentalPlanItem::create([
                    'departmental_plan_id' => $plan->id,
                    'faculty_profile_id'   => $profileId,
                    'category'             => $category,
                    'priority'             => strtolower($priority),
                    'title'                => $title,
                    'description'          => $description,
                    'programs'             => $programs,
                    'source'               => [
                        'faculty_name'        => $row['user']->name,
                        'performance_level'   => $level,
                        'weighted_average'    => $row['weighted_average'] ?? null,
                        'dean_recommendation' => $deanChoice,
                        'components'          => $row['components'] ?? null,
                    ],
                    'status' => 'pending',
                ]);
            }

            // Department-wide synthesis item — always last, never tied to a single faculty
            DepartmentalPlanItem::create([
                'departmental_plan_id' => $plan->id,
                'faculty_profile_id'   => null,
                'category'             => 'dept_wide',
                'priority'             => $rollUp['by_priority']['high'] > 0 ? 'high'
                    : ($rollUp['by_priority']['medium'] > 0 ? 'medium' : 'low'),
                'title'                => 'Department-wide development theme',
                'description'          => $this->buildDeptWideDescription($rollUp),
                'programs'             => $this->buildDeptWidePrograms($rollUp),
                'source'               => ['roll_up' => $rollUp],
                'status'               => 'pending',
            ]);

            return $plan->fresh('items');
        });
    }

    /**
     * Map one faculty row + intervention recommendation + dean's stated choice
     * into a single plan item. Dean's explicit recommendation (when present)
     * takes precedence over the performance-level default.
     *
     * @return array{0:string,1:string,2:string,3:string,4:array}
     */
    private function deriveItem(array $row, array $rec, ?string $deanChoice): array
    {
        $name = $row['user']->name;
        $level = $row['performance_level'] ?? '—';

        // Dean's explicit choice overrides the mapper's default action.
        if ($deanChoice === 'promotion') {
            return [
                'promotion',
                "Promotion recommendation: {$name}",
                "Dean recommended promotion. Current performance level: {$level}. " .
                "Forward dossier to HR for promotion review; pair with sustained excellence programs to maintain trajectory.",
                'low',
                ['HR promotion dossier preparation', 'Sustained excellence enrolment'],
            ];
        }

        if ($deanChoice === 'reassignment') {
            return [
                'reassignment',
                "Reassignment recommendation: {$name}",
                "Dean recommended reassignment. Current performance level: {$level}. " .
                "Schedule conference with HR to review fit and identify a better-aligned role or department.",
                'high',
                ['HR reassignment conference', 'Role-fit assessment'],
            ];
        }

        if ($deanChoice === 'retention') {
            // Retention is the neutral baseline — let the performance bucket
            // decide the supporting action, but keep the dean's signal in the
            // title so the plan reflects the dean's stated position.
            return [
                $this->categoryFromIntervention($rec['intervention']),
                "Retention with development: {$name}",
                "Dean recommended retention. {$rec['description']}",
                $rec['priority'],
                $rec['programs'],
            ];
        }

        // No dean recommendation yet — fall back to the performance mapping.
        return [
            $this->categoryFromIntervention($rec['intervention']),
            "{$rec['intervention']}: {$name}",
            "Performance level: {$level}. {$rec['description']}",
            $rec['priority'],
            $rec['programs'],
        ];
    }

    private function categoryFromIntervention(string $intervention): string
    {
        return match (true) {
            str_contains($intervention, 'Recognition')          => 'recognition',
            str_contains($intervention, 'Sustained Excellence') => 'sustained_excellence',
            str_contains($intervention, 'Skills Enhancement')   => 'training',
            str_contains($intervention, 'Coaching')             => 'coaching',
            str_contains($intervention, 'Performance Improvement') => 'pip',
            default                                              => 'training',
        };
    }

    private function buildRollUp($rows, $deanRecs): array
    {
        $byLevel = ['excellent' => 0, 'very_good' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0, 'unknown' => 0];
        $byPriority = ['high' => 0, 'medium' => 0, 'low' => 0];
        $byRec = ['retention' => 0, 'promotion' => 0, 'reassignment' => 0, 'none' => 0];
        $evaluated = 0;

        foreach ($rows as $row) {
            $rec = InterventionRecommendationMapper::recommend($row['performance_level'] ?? null);
            $key = $this->levelKey($row['performance_level'] ?? null);
            $byLevel[$key] = ($byLevel[$key] ?? 0) + 1;

            $p = strtolower($rec['priority']);
            if (isset($byPriority[$p])) {
                $byPriority[$p]++;
            }

            if (($row['weighted_average'] ?? 0) > 0) {
                $evaluated++;
            }

            $choice = $deanRecs[$row['profile']->id] ?? null;
            if ($choice && isset($byRec[$choice])) {
                $byRec[$choice]++;
            } else {
                $byRec['none']++;
            }
        }

        return [
            'total'           => $rows->count(),
            'evaluated'       => $evaluated,
            'by_level'        => $byLevel,
            'by_priority'     => $byPriority,
            'by_recommendation' => $byRec,
        ];
    }

    private function levelKey(?string $level): string
    {
        if (!$level) return 'unknown';
        $l = strtolower($level);
        return match (true) {
            str_contains($l, 'excellent') || str_contains($l, 'outstanding')   => 'excellent',
            str_contains($l, 'very good') || str_contains($l, 'very satisfact') => 'very_good',
            str_contains($l, 'good') || str_contains($l, 'satisfact')          => 'good',
            str_contains($l, 'needs improvement') || str_contains($l, 'fair')   => 'fair',
            str_contains($l, 'poor') || str_contains($l, 'at risk') || str_contains($l, 'unsatisfact') => 'poor',
            default => 'unknown',
        };
    }

    private function buildSummary(Department $department, array $rollUp): string
    {
        $deptName = $department->name;
        $total = $rollUp['total'];
        $evaluated = $rollUp['evaluated'];
        $high = $rollUp['by_priority']['high'];
        $medium = $rollUp['by_priority']['medium'];
        $low = $rollUp['by_priority']['low'];

        return "Plan for {$deptName}: {$total} faculty in scope ({$evaluated} with evaluation data). " .
               "{$high} high-priority action(s), {$medium} medium, {$low} low.";
    }

    private function buildDeptWideDescription(array $rollUp): string
    {
        $weakCount = ($rollUp['by_level']['fair'] ?? 0) + ($rollUp['by_level']['poor'] ?? 0);
        $strongCount = ($rollUp['by_level']['excellent'] ?? 0) + ($rollUp['by_level']['very_good'] ?? 0);

        if ($weakCount > 0 && $weakCount >= $strongCount) {
            return "More than half the department falls below the good band. Recommend running a department-wide " .
                   "pedagogical clinic this term and forming peer-learning circles that pair stronger faculty with those " .
                   "in coaching/PIP tracks.";
        }

        if ($strongCount > 0 && $strongCount >= $weakCount) {
            return "Most of the department is performing at the good band or above. Recommend formalising knowledge transfer: " .
                   "have top performers lead a department-wide workshop on their strongest criterion to lift the cohort overall.";
        }

        return "Department performance is mixed across the bands. Recommend a department-wide review of criterion-level " .
               "scores to identify the one or two areas where targeted training would have the broadest lift.";
    }

    private function buildDeptWidePrograms(array $rollUp): array
    {
        $weak = ($rollUp['by_level']['fair'] ?? 0) + ($rollUp['by_level']['poor'] ?? 0);
        $strong = ($rollUp['by_level']['excellent'] ?? 0) + ($rollUp['by_level']['very_good'] ?? 0);

        if ($weak >= $strong && $weak > 0) {
            return [
                'Department pedagogical clinic (term)',
                'Cross-faculty peer-learning circles',
                'Criterion-level deep-dive review',
            ];
        }

        return [
            'Top-performer led workshop',
            'Peer-learning circles (monthly)',
            'Criterion-level deep-dive review',
        ];
    }
}
