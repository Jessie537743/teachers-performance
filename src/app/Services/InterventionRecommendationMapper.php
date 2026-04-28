<?php

namespace App\Services;

/**
 * Maps a faculty's predicted performance level to:
 *   - a recommended-intervention name & description
 *   - a priority level (Low / Medium / High)
 *   - a suggested professional-development program list
 *
 * Single source of truth for the Intervention Recommendation Module roster.
 * Each performance level maps to a deliberate HR/development response so
 * deans and admins see consistent, defensible recommendations.
 */
class InterventionRecommendationMapper
{
    /**
     * @return array{
     *   intervention: string,
     *   description: string,
     *   priority: string,
     *   priority_class: string,
     *   level_class: string,
     *   programs: list<string>,
     * }
     */
    public static function recommend(?string $performanceLevel): array
    {
        $key = self::normalize($performanceLevel);

        return match ($key) {
            'excellent' => [
                'intervention' => 'Recognition and Incentive Program',
                'description'  => 'The faculty member is performing exceptionally well based on the predicted result. It is recommended that the faculty be recognised through formal awards, incentives, and inclusion in mentorship and faculty-development panels to sustain excellence and uplift colleagues.',
                'priority'     => 'Low',
                'priority_class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                'level_class'  => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                'programs'     => [
                    'Faculty Recognition Award (term-end)',
                    'Internal mentor pool enrolment',
                    'Conference / publication sponsorship',
                    'Curriculum-design lead opportunity',
                ],
            ],
            'very_good' => [
                'intervention' => 'Sustained Excellence Program',
                'description'  => 'The faculty member is performing above expectations. To prevent regression and continue growth, recommend ongoing peer-learning enrolment and targeted upskilling in the lowest of their otherwise-strong areas.',
                'priority'     => 'Low',
                'priority_class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                'level_class'  => 'bg-teal-100 text-teal-700 ring-teal-200',
                'programs'     => [
                    'Peer-learning circle (monthly)',
                    'Targeted skill micro-credential',
                    'Cross-department teaching exchange',
                ],
            ],
            'good' => [
                'intervention' => 'Skills Enhancement Training',
                'description'  => 'The faculty member meets baseline expectations. Skills enhancement training is recommended to strengthen identified weaker areas and progress toward the very-good band, while maintaining current strengths.',
                'priority'     => 'Medium',
                'priority_class' => 'bg-amber-100 text-amber-700 ring-amber-200',
                'level_class'  => 'bg-amber-100 text-amber-700 ring-amber-200',
                'programs'     => [
                    'Pedagogical Skills Workshop',
                    'Classroom Engagement Training',
                    'Assessment Design Clinic',
                    'Student Feedback Loop Setup',
                ],
            ],
            'fair' => [
                'intervention' => 'Coaching and Mentoring Program',
                'description'  => 'The faculty member shows performance gaps based on the predicted result. A structured coaching and mentoring program with a senior peer is recommended for one term, followed by re-evaluation against measurable goals.',
                'priority'     => 'High',
                'priority_class' => 'bg-rose-100 text-rose-700 ring-rose-200',
                'level_class'  => 'bg-orange-100 text-orange-700 ring-orange-200',
                'programs'     => [
                    'Senior-faculty mentor pairing',
                    'Weekly classroom observation cycle',
                    'Pedagogical Skills Workshop',
                    'Reflective Teaching Journal',
                ],
            ],
            'poor' => [
                'intervention' => 'Intensive Training and Performance Improvement Plan',
                'description'  => 'The faculty member shows low performance based on the predicted result. It is recommended that the faculty undergo an intensive training program and be included in a performance improvement plan with regular monitoring.',
                'priority'     => 'High',
                'priority_class' => 'bg-rose-100 text-rose-700 ring-rose-200',
                'level_class'  => 'bg-rose-100 text-rose-700 ring-rose-200',
                'programs'     => [
                    'Advanced Teaching Skills Training',
                    'Classroom Management Workshop',
                    'Mentoring Program',
                    'Regular Performance Coaching',
                ],
            ],
            default => [
                'intervention' => 'Awaiting Evaluation Data',
                'description'  => 'No evaluation data is available for the selected period. Once student, dean, peer, and self-evaluations are submitted, an intervention recommendation will be generated automatically.',
                'priority'     => '—',
                'priority_class' => 'bg-slate-100 text-slate-600 ring-slate-200',
                'level_class'  => 'bg-slate-100 text-slate-600 ring-slate-200',
                'programs'     => [],
            ],
        };
    }

    /**
     * Map any of the institutional performance label variants to one of:
     *   excellent | very_good | good | fair | poor | unknown
     */
    private static function normalize(?string $level): string
    {
        if (! is_string($level) || trim($level) === '') {
            return 'unknown';
        }
        $l = strtolower($level);

        return match (true) {
            str_contains($l, 'excellent') || str_contains($l, 'outstanding')   => 'excellent',
            str_contains($l, 'very good') || str_contains($l, 'very satisfact') => 'very_good',
            str_contains($l, 'good') || str_contains($l, 'satisfact')          => 'good',
            str_contains($l, 'needs improvement') || str_contains($l, 'fair')   => 'fair',
            str_contains($l, 'poor') || str_contains($l, 'at risk') || str_contains($l, 'unsatisfact') => 'poor',
            default                                                              => 'unknown',
        };
    }
}
