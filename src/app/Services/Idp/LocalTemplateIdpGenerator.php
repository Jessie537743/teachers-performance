<?php

namespace App\Services\Idp;

use App\Contracts\IdpGenerator;
use App\Models\FacultyProfile;
use App\Services\EvaluationService;
use App\Services\IndividualEvaluationItemizedReportService;
use Carbon\CarbonImmutable;

/**
 * Deterministic IDP generator backed by templated language.
 *
 * Pipeline:
 *   1. Pull per-evaluator-group itemized reports (student / dean / self / peer)
 *      that apply for the faculty's personnel type.
 *   2. Aggregate criterion-level averages across the groups (simple mean of
 *      whichever groups produced data — keeps the math transparent).
 *   3. Pick the top-N strengths and top-N growth areas.
 *   4. Compose SMART goals + 30/60/90-day action items per growth area.
 *
 * The output shape mirrors the IdpGenerator contract so a future LLM-backed
 * driver can replace this 1:1 without touching the orchestrator.
 */
class LocalTemplateIdpGenerator implements IdpGenerator
{
    public const ENGINE         = 'local-template-v1';
    public const MODEL_VERSION  = 'idp-v1';

    public function __construct(
        private readonly IndividualEvaluationItemizedReportService $reports,
    ) {}

    public function generate(FacultyProfile $profile, string $semester, string $schoolYear): array
    {
        $profile->loadMissing('user', 'department');

        $personnelType = $profile->evaluationCriteriaPersonnelType();

        $sources = $this->buildSourceReports($profile, $semester, $schoolYear);
        [$criteriaAvgs, $criteriaEvidence] = $this->aggregateCriteriaScores($sources);

        $strengths    = $this->pickStrengths($criteriaAvgs, $criteriaEvidence, $personnelType);
        $growthAreas  = $this->pickGrowthAreas($criteriaAvgs, $criteriaEvidence, $personnelType);

        $goals        = $this->buildGoals($growthAreas, $semester, $schoolYear);
        $actionItems  = $this->buildActionItems($growthAreas);
        $outcomes     = $this->buildExpectedOutcomes($growthAreas, $personnelType);
        $resources    = $this->buildRecommendedResources($growthAreas);

        $overallAvg   = $this->overallAverage($sources);
        $overallLevel = $overallAvg !== null
            ? EvaluationService::getPerformanceLevel($overallAvg, $personnelType)
            : '—';

        $summary = $this->composeSummary(
            $profile,
            $overallAvg,
            $overallLevel,
            $strengths,
            $growthAreas,
            $sources,
        );

        return [
            'summary'              => $summary,
            'strengths'            => $strengths,
            'growth_areas'         => $growthAreas,
            'goals'                => $goals,
            'action_items'         => $actionItems,
            'expected_outcomes'    => $outcomes,
            'recommended_resources' => $resources,
            'generated_from'       => [
                'overall_avg'    => $overallAvg,
                'overall_level'  => $overallLevel,
                'personnel_type' => $personnelType,
                'sources_used'   => array_keys(array_filter(
                    $sources,
                    fn ($r) => ($r['has_likert_data'] ?? false) === true,
                )),
                'period'         => ['school_year' => $schoolYear, 'semester' => $semester],
            ],
            'engine'        => self::ENGINE,
            'model_version' => self::MODEL_VERSION,
        ];
    }

    /**
     * Pull each evaluator-group's itemized report. Groups that yielded no
     * data are still kept in the array so callers can see what was checked.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildSourceReports(FacultyProfile $profile, string $semester, string $schoolYear): array
    {
        $groups = ['student', 'dean', 'self', 'peer'];

        $out = [];
        foreach ($groups as $g) {
            $out[$g] = $this->reports->build($profile, $g, $semester, $schoolYear);
        }

        return $out;
    }

    /**
     * Mean of category_avg across whichever sources produced data, per criterion.
     *
     * @param  array<string, array<string, mixed>>  $sources
     * @return array{0: array<string, float>, 1: array<string, list<string>>}
     */
    private function aggregateCriteriaScores(array $sources): array
    {
        $bucket = [];     // criterion_name => [scores...]
        $evidence = [];   // criterion_name => ["student: 3.45", ...]

        foreach ($sources as $group => $report) {
            if (! ($report['has_likert_data'] ?? false)) {
                continue;
            }
            foreach (($report['sections'] ?? []) as $section) {
                $name = (string) ($section['name'] ?? '');
                $val  = $section['category_avg'] ?? null;
                if ($name === '' || $val === null) {
                    continue;
                }

                $bucket[$name][]   = (float) $val;
                $evidence[$name][] = sprintf('%s avg %.2f', ucfirst((string) $group), (float) $val);
            }
        }

        $avgs = [];
        foreach ($bucket as $name => $vals) {
            $avgs[$name] = round(array_sum($vals) / count($vals), 2);
        }

        return [$avgs, $evidence];
    }

    /**
     * @param  array<string, float>  $avgs
     * @param  array<string, list<string>>  $evidence
     * @return list<array{area: string, evidence: string, score: float|null}>
     */
    private function pickStrengths(array $avgs, array $evidence, string $personnelType): array
    {
        $threshold = (float) config('idp.thresholds.strength_min', 4.5);
        $topN      = (int) config('idp.thresholds.top_n_strengths', 3);

        $candidates = array_filter($avgs, fn ($v) => $v >= $threshold);
        arsort($candidates);
        $candidates = array_slice($candidates, 0, $topN, true);

        $out = [];
        foreach ($candidates as $name => $score) {
            $out[] = [
                'area'     => $this->humanize($name),
                'evidence' => implode(' • ', $evidence[$name] ?? []),
                'score'    => $score,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, float>  $avgs
     * @param  array<string, list<string>>  $evidence
     * @return list<array{area: string, current_level: string, target_level: string, gap: float|null, evidence: string}>
     */
    private function pickGrowthAreas(array $avgs, array $evidence, string $personnelType): array
    {
        $threshold = (float) config('idp.thresholds.growth_max', 4.0);
        $topN      = (int) config('idp.thresholds.top_n_growth', 3);

        $candidates = array_filter($avgs, fn ($v) => $v < $threshold);
        asort($candidates);
        $candidates = array_slice($candidates, 0, $topN, true);

        $out = [];
        foreach ($candidates as $name => $score) {
            $currentLevel = EvaluationService::getPerformanceLevel($score, $personnelType);
            $targetScore  = min(5.0, $score + 0.75);
            $targetLevel  = EvaluationService::getPerformanceLevel($targetScore, $personnelType);

            $out[] = [
                'area'          => $this->humanize($name),
                'current_level' => $currentLevel,
                'target_level'  => $targetLevel,
                'gap'           => round($targetScore - $score, 2),
                'evidence'      => implode(' • ', $evidence[$name] ?? []),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $growthAreas
     * @return list<array{title: string, specific: string, measurable: string, achievable: string, relevant: string, time_bound: string, target_date: string|null}>
     */
    private function buildGoals(array $growthAreas, string $semester, string $schoolYear): array
    {
        $targetDate = $this->endOfNextQuarter()->toDateString();

        $out = [];
        foreach ($growthAreas as $g) {
            $area    = (string) $g['area'];
            $current = (string) $g['current_level'];
            $target  = (string) $g['target_level'];

            $out[] = [
                'title'       => "Raise performance in {$area}",
                'specific'    => "Strengthen {$area} through structured development activities aligned with the rubric used in this evaluation.",
                'measurable'  => "Move category-level rating from {$current} to {$target} in the next evaluation cycle.",
                'achievable'  => "A {$g['gap']}-point lift over one cycle is realistic when paired with mentoring and the action items below.",
                'relevant'    => "Directly addresses the lowest-scoring criterion across evaluator groups for {$schoolYear} · {$semester}.",
                'time_bound'  => "Achieve target rating by the close of the next evaluation period (target {$targetDate}).",
                'target_date' => $targetDate,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $growthAreas
     * @return list<array{phase: string, action: string, resources: string, owner: string, due: string|null}>
     */
    private function buildActionItems(array $growthAreas): array
    {
        $now    = CarbonImmutable::now();
        $d30    = $now->addDays(30)->toDateString();
        $d60    = $now->addDays(60)->toDateString();
        $d90    = $now->addDays(90)->toDateString();

        $out = [];
        foreach ($growthAreas as $g) {
            $area = (string) $g['area'];

            $out[] = [
                'phase'     => '0–30 days',
                'action'    => "Self-audit recent practice in {$area}; identify two specific behaviors to change.",
                'resources' => 'Rubric used in this evaluation; current itemized report.',
                'owner'     => 'Faculty member',
                'due'       => $d30,
            ];
            $out[] = [
                'phase'     => '30–60 days',
                'action'    => "Attend a focused workshop or peer-mentoring session on {$area}; document one measurable change applied.",
                'resources' => 'HR-curated training catalog; assigned peer mentor (Dean/Head to identify).',
                'owner'     => 'Faculty member + Dean/Head',
                'due'       => $d60,
            ];
            $out[] = [
                'phase'     => '60–90 days',
                'action'    => "Demonstrate improved {$area} via classroom observation or work sample; reflect on outcomes.",
                'resources' => 'Observation rubric; reflection template.',
                'owner'     => 'Faculty member + Dean/Head',
                'due'       => $d90,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $growthAreas
     * @return list<string>
     */
    private function buildExpectedOutcomes(array $growthAreas, string $personnelType): array
    {
        if ($growthAreas === []) {
            return [
                'Sustained performance at the current level across all rubric dimensions.',
                'Continued contribution to peer development and departmental initiatives.',
            ];
        }

        $out = [];
        foreach ($growthAreas as $g) {
            $out[] = sprintf(
                'Lift in "%s" rating from %s to %s within the next evaluation cycle.',
                $g['area'],
                $g['current_level'],
                $g['target_level'],
            );
        }
        $out[] = 'Higher overall GWA reflected in the next departmental performance summary.';

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $growthAreas
     * @return list<string>
     */
    private function buildRecommendedResources(array $growthAreas): array
    {
        if ($growthAreas === []) {
            return ['Existing institutional CPD calendar.'];
        }

        $out = [
            'Department-curated training catalog aligned with the evaluation rubric.',
            'Peer-mentoring pairing facilitated by the Dean/Head.',
            'Reflective journal template (HR forms repository).',
        ];
        foreach ($growthAreas as $g) {
            $out[] = sprintf('Targeted reading or external course on "%s".', $g['area']);
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, array<string, mixed>>  $sources
     */
    private function overallAverage(array $sources): ?float
    {
        $vals = [];
        foreach ($sources as $r) {
            if (($r['has_likert_data'] ?? false) && ($r['overall_avg'] ?? null) !== null) {
                $vals[] = (float) $r['overall_avg'];
            }
        }

        return $vals === [] ? null : round(array_sum($vals) / count($vals), 2);
    }

    /**
     * @param  list<array<string, mixed>>  $strengths
     * @param  list<array<string, mixed>>  $growthAreas
     * @param  array<string, array<string, mixed>>  $sources
     */
    private function composeSummary(
        FacultyProfile $profile,
        ?float $overallAvg,
        string $overallLevel,
        array $strengths,
        array $growthAreas,
        array $sources,
    ): string {
        $name = $profile->user?->name ?? 'The faculty member';

        $sourceUsed = [];
        foreach ($sources as $g => $r) {
            if (($r['has_likert_data'] ?? false)) {
                $sourceUsed[] = ucfirst((string) $g);
            }
        }
        $sourceLine = $sourceUsed === []
            ? 'No evaluator group produced quantitative data for this period.'
            : 'Drawn from: ' . implode(', ', $sourceUsed) . '.';

        $strengthLine = $strengths === []
            ? 'No criterion crossed the strength threshold this cycle.'
            : 'Strongest: ' . implode('; ', array_map(fn ($s) => $s['area'], $strengths)) . '.';

        $growthLine = $growthAreas === []
            ? 'All rubric dimensions are at or above the development threshold — focus this cycle is on maintenance and stretch goals.'
            : 'Top areas to develop: ' . implode('; ', array_map(fn ($g) => $g['area'], $growthAreas)) . '.';

        $avgLine = $overallAvg === null
            ? 'Overall rating is unavailable for this period.'
            : sprintf('Overall rating: %.2f (%s).', $overallAvg, $overallLevel);

        return implode(' ', [
            "{$name}'s Individual Development Plan covers the upcoming evaluation cycle.",
            $sourceLine,
            $avgLine,
            $strengthLine,
            $growthLine,
        ]);
    }

    private function humanize(string $criterionName): string
    {
        // Criteria are stored mb_strtoupper'd in the report sections; rendering
        // them in title-case reads better in narrative prose.
        return mb_convert_case(mb_strtolower($criterionName), MB_CASE_TITLE, 'UTF-8');
    }

    private function endOfNextQuarter(): CarbonImmutable
    {
        $now     = CarbonImmutable::now();
        $quarter = (int) ceil($now->month / 3);
        $endMonth = $quarter * 3;
        $end      = $now->setDate($now->year, $endMonth, 1)->endOfMonth()->addQuarter();

        return $end->endOfMonth();
    }
}
