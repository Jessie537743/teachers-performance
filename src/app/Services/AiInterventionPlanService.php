<?php

namespace App\Services;

use App\Models\FacultyProfile;
use App\Models\Intervention;
use App\Models\InterventionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * AI-driven intervention plan generator.
 *
 * Combines three signals to produce a personalized, prioritized action plan
 * for a low-performing faculty member:
 *
 *   1. InterventionSuggestionService — multi-source weighted analysis of
 *      every Likert question (student, dean, self, peer) and which are in
 *      the at-risk band.
 *   2. MlApiService — Random Forest classifier provides a counterfactual:
 *      "if avg_score moved from X → Y, predicted performance level is Z."
 *      Used to quantify the *expected lift* of doing the plan.
 *   3. Intervention library — admin-curated mapping of question → recommended
 *      intervention text. Used as the seed material the AI personalises and
 *      sequences across a 30/60/90-day timeline.
 *
 * The output is deterministic given the same inputs (no external LLM call).
 * Severity, clustering, and counterfactual lift are computed; the narrative
 * is templated. Replace the templating with a real LLM call later by
 * implementing a `narrate()` adapter — the structured plan stays.
 */
class AiInterventionPlanService
{
    public const MODEL_VERSION = 'plan-v1';

    public function __construct(
        private readonly InterventionSuggestionService $analyzer,
        private readonly MlApiService $mlApi,
    ) {}

    /**
     * Build (and persist as draft) an AI plan for the faculty member.
     */
    public function generatePlan(
        FacultyProfile $profile,
        string $semester,
        string $schoolYear,
        ?int $createdBy = null,
    ): InterventionPlan {
        $analysis = $this->analyzer->analyze($profile, $semester, $schoolYear);

        $severity = $this->computeSeverity($analysis);
        $clusters = $this->clusterWeakItems(collect($analysis['weak_questions']));
        $actions  = $this->buildActionTimeline($clusters, $analysis);
        $outcome  = $this->predictExpectedOutcome($profile, $analysis, $semester, $schoolYear);
        $summary  = $this->composeSummary($profile, $analysis, $severity, $clusters, $outcome);

        // Supersede any existing draft/active plan for this faculty + period.
        InterventionPlan::query()
            ->where('faculty_id', $profile->id)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereIn('status', ['draft', 'active'])
            ->update(['status' => 'superseded']);

        return InterventionPlan::create([
            'faculty_id'       => $profile->id,
            'school_year'      => $schoolYear,
            'semester'         => $semester,
            'severity'         => $severity,
            'summary'          => $summary,
            'action_items'     => $actions,
            'expected_outcome' => $outcome,
            'signal_clusters'  => $clusters->values()->all(),
            'model_version'    => self::MODEL_VERSION,
            'status'           => 'draft',
            'created_by'       => $createdBy,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Severity — combines weighted GWA + count of at-risk items + score gap
    // ─────────────────────────────────────────────────────────────────────
    private function computeSeverity(array $analysis): string
    {
        $gwa     = $analysis['overall']['weighted_average'];
        $weak    = $analysis['weak_questions'];
        $weakN   = count($weak);

        if ($gwa === null) {
            return 'moderate';
        }

        // Lower GWA AND many weak items → critical. Pure low GWA → high.
        if ($gwa < 2.5 && $weakN >= 5) {
            return 'critical';
        }
        if ($gwa < 2.8 || $weakN >= 8) {
            return 'high';
        }
        return 'moderate';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Theme clustering — group weak questions by criterion. Each cluster
    // gets an aggregate score and a representative title so the action
    // plan can target *themes* rather than 12 isolated questions.
    // ─────────────────────────────────────────────────────────────────────
    private function clusterWeakItems(Collection $weakQuestions): Collection
    {
        return $weakQuestions
            ->groupBy(fn ($q) => $q['criterion_name'] ?: 'Other')
            ->map(function (Collection $group, string $criterion) {
                $avg = round($group->avg('weighted_question_average'), 2);
                $items = $group->map(fn ($q) => [
                    'question_id'     => $q['question_id'],
                    'question_text'   => $q['question_text'],
                    'weighted_score'  => $q['weighted_question_average'],
                    'sources'         => $q['sources'],
                ])->values()->all();

                return [
                    'criterion'    => $criterion,
                    'average'      => $avg,
                    'item_count'   => count($items),
                    'priority'     => $this->priorityFromScore($avg),
                    'theme'        => $this->themeFor($criterion),
                    'items'        => $items,
                ];
            })
            ->sortBy('average')   // lowest scores first → highest priority
            ->values();
    }

    private function priorityFromScore(float $avg): string
    {
        if ($avg < 2.4) return 'P0';
        if ($avg < 2.8) return 'P1';
        return 'P2';
    }

    /**
     * Lightweight rule-based theme inference. A more sophisticated version
     * would embed the criterion text and cluster by similarity; for now
     * a keyword match is sufficient and predictable.
     */
    private function themeFor(string $criterion): string
    {
        $c = strtolower($criterion);
        return match (true) {
            str_contains($c, 'class') || str_contains($c, 'manage')   => 'classroom_management',
            str_contains($c, 'commun') || str_contains($c, 'feedback') => 'communication',
            str_contains($c, 'subject') || str_contains($c, 'mastery') => 'subject_mastery',
            str_contains($c, 'pace')   || str_contains($c, 'time')    => 'pacing',
            str_contains($c, 'engag')                                   => 'student_engagement',
            str_contains($c, 'assess') || str_contains($c, 'evaluat')  => 'assessment_quality',
            str_contains($c, 'attitud') || str_contains($c, 'behav')   => 'professionalism',
            default                                                     => 'general',
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    // 30 / 60 / 90-day action timeline. P0 clusters land in the 30-day
    // window, P1 in 60, P2 in 90. Each action carries the canned
    // intervention text from the curated library and the source question
    // IDs that drove its inclusion.
    // ─────────────────────────────────────────────────────────────────────
    private function buildActionTimeline(Collection $clusters, array $analysis): array
    {
        $items = [];

        foreach ($clusters as $cluster) {
            $phase = match ($cluster['priority']) {
                'P0' => '30_day',
                'P1' => '60_day',
                default => '90_day',
            };

            // Pull the most-relevant canned intervention for this cluster's
            // weakest question. Falls back to a templated action if none.
            $weakestId = $cluster['items'][0]['question_id'] ?? null;
            $library = $weakestId
                ? Intervention::where('question_id', $weakestId)->first()
                : null;

            $recommended = $library?->recommended_intervention
                ?: $this->templateAction($cluster);

            $items[] = [
                'phase'                  => $phase,
                'priority'               => $cluster['priority'],
                'criterion'              => $cluster['criterion'],
                'theme'                  => $cluster['theme'],
                'cluster_avg'            => $cluster['average'],
                'item_count'             => $cluster['item_count'],
                'recommended_intervention' => Str::limit($recommended, 600, '…'),
                'rationale'              => $this->rationaleFor($cluster, $analysis),
                'source_question_ids'    => array_column($cluster['items'], 'question_id'),
                'success_metric'         => 'Lift cluster avg from ' . $cluster['average'] . ' → ≥3.0 in this period.',
            ];
        }

        // Hard-cap to top 6 actions to keep the plan executable.
        return array_slice($items, 0, 6);
    }

    private function templateAction(array $cluster): string
    {
        $themeBlurb = match ($cluster['theme']) {
            'classroom_management' => 'Enroll in a 6-week peer-observed classroom management mentoring loop. Schedule one observation per week with a designated mentor; submit reflective notes after each.',
            'communication'        => 'Run weekly student feedback micro-surveys (5 questions, anonymous) and review with department head. Aim for ≥75% positive shift in the lowest-scoring item by mid-period.',
            'subject_mastery'      => 'Complete one subject-domain refresher course (or proctored online module) and present a 30-minute departmental brown-bag on a misconception you used to make in class.',
            'pacing'               => 'Adopt a fixed weekly pacing template (objectives, formative checks, summative buffer). Submit lesson plan to coordinator weekly for the next 4 weeks.',
            'student_engagement'   => 'Pilot two active-learning techniques (think-pair-share + concept mapping) for 4 consecutive weeks. Track student engagement via short exit-ticket each session.',
            'assessment_quality'   => 'Have department coordinator review and sign off on the next 3 summative assessments before deployment. Calibrate one rubric jointly.',
            'professionalism'      => 'Schedule a structured coaching conversation with the department head; agree on a written 60-day professional standards plan.',
            default                => 'Pair with a senior mentor for a 6-week coaching sprint focused on the cluster items below.',
        };

        return $themeBlurb . ' Re-evaluate at end of phase; if scores remain below 3.0, escalate to a formal performance improvement plan.';
    }

    private function rationaleFor(array $cluster, array $analysis): string
    {
        $worst = $cluster['items'][0] ?? null;
        if (! $worst) return '';

        $sources = array_filter($worst['sources'] ?? [], fn ($v) => $v !== null);
        $worstSource = $sources ? array_keys($sources, min($sources))[0] : null;

        $sourceCopy = match ($worstSource) {
            'student' => 'students rate this lowest',
            'dean'    => 'dean evaluation flagged this lowest',
            'self'    => 'self-evaluation aligns with the gap',
            'peer'    => 'peers rated this lowest',
            default   => 'multi-source data shows a consistent gap',
        };

        return sprintf(
            '%d weak item(s) in "%s" averaging %s — %s.',
            $cluster['item_count'],
            $cluster['criterion'],
            number_format($cluster['average'], 2),
            $sourceCopy,
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // ML counterfactual: ask the trained Random Forest "if avg_score were
    // 0.5 higher, what label would you predict?" — quantifies expected
    // lift from completing the plan.
    // ─────────────────────────────────────────────────────────────────────
    private function predictExpectedOutcome(
        FacultyProfile $profile,
        array $analysis,
        string $semester,
        string $schoolYear,
    ): array {
        $currentAvg = (float) ($analysis['overall']['weighted_average'] ?? 0);
        $currentLvl = $analysis['overall']['performance_level'] ?? 'Unknown';

        // Baseline: what does ML say for the *current* avg?
        $baseline = $this->mlApi->predict(
            avgScore: max(1.0, min(5.0, $currentAvg)),
            responseCount: max(1, count($analysis['weak_questions']) * 4),
            previousScore: max(1.0, min(5.0, $currentAvg)),
            improvementRate: 0.0,
            semester: $semester,
            schoolYear: $schoolYear,
        );

        // Counterfactual: lift avg by +0.5 (target outcome of completing the plan)
        $targetAvg = min(5.0, max(1.0, $currentAvg + 0.5));
        $counterfactual = $this->mlApi->predict(
            avgScore: $targetAvg,
            responseCount: max(1, count($analysis['weak_questions']) * 4),
            previousScore: max(1.0, min(5.0, $currentAvg)),
            improvementRate: ($targetAvg - $currentAvg) / max($currentAvg, 0.01),
            semester: $semester,
            schoolYear: $schoolYear,
        );

        $mlAvailable = ! isset($baseline['error']) && ! isset($counterfactual['error']);

        return [
            'current_avg'          => round($currentAvg, 2),
            'current_level'        => $currentLvl,
            'target_avg'           => round($targetAvg, 2),
            'predicted_level'      => $counterfactual['predicted_performance'] ?? null,
            'baseline_predicted'   => $baseline['predicted_performance'] ?? null,
            'lift_pct'             => $currentAvg > 0
                ? round((($targetAvg - $currentAvg) / $currentAvg) * 100, 1)
                : null,
            'ml_confidence'        => $counterfactual['confidence'] ?? null,
            'ml_available'         => $mlAvailable,
            'ml_error'             => $baseline['error'] ?? $counterfactual['error'] ?? null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Composed summary — short narrative for the plan header. This is the
    // seam where a real LLM (Anthropic / OpenAI) would slot in if you want
    // a more natural narrative; right now it's deterministic templating.
    // ─────────────────────────────────────────────────────────────────────
    private function composeSummary(
        FacultyProfile $profile,
        array $analysis,
        string $severity,
        Collection $clusters,
        array $outcome,
    ): string {
        $name        = $profile->user?->name ?? ('Faculty #' . $profile->id);
        $weakN       = count($analysis['weak_questions']);
        $clusterN    = $clusters->count();
        $gwa         = $analysis['overall']['weighted_average'];
        $level       = $analysis['overall']['performance_level'] ?: 'unrated';
        $topCluster  = $clusters->first();

        $severityVerb = match ($severity) {
            'critical' => 'requires immediate intervention',
            'high'     => 'needs structured intervention',
            default    => 'would benefit from coaching',
        };

        $lines = [];
        $lines[] = "{$name} {$severityVerb}. Overall weighted composite is "
            . ($gwa !== null ? number_format($gwa, 2) : '—')
            . " ({$level}), with {$weakN} at-risk Likert item(s) clustered into {$clusterN} theme(s).";

        if ($topCluster) {
            $lines[] = "Highest-priority theme: \"{$topCluster['criterion']}\" "
                . "(avg {$topCluster['average']}, {$topCluster['item_count']} item(s))."
                . " Tackling this in the first 30 days is expected to yield the largest lift.";
        }

        if ($outcome['ml_available']) {
            $lines[] = "Counterfactual ML analysis: lifting the weighted average from "
                . "{$outcome['current_avg']} to {$outcome['target_avg']} "
                . ($outcome['predicted_level'] && $outcome['baseline_predicted'] && $outcome['predicted_level'] !== $outcome['baseline_predicted']
                    ? "is predicted to move performance from \"{$outcome['baseline_predicted']}\" to \"{$outcome['predicted_level']}\""
                    : "is the recommended target.")
                . " (model confidence " . round(($outcome['ml_confidence'] ?? 0) * 100) . "%).";
        } else {
            $lines[] = "ML counterfactual unavailable for this period — train the model first to enable predicted-lift estimates.";
        }

        $lines[] = "The action plan below is sequenced 30 / 60 / 90-day, prioritised by cluster severity and grounded in the institution's curated intervention library.";

        return implode("\n\n", $lines);
    }
}
