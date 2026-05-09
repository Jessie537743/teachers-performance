<?php

namespace App\Contracts;

use App\Models\FacultyProfile;

/**
 * Contract for Individual Development Plan generators.
 *
 * Implementations may produce a plan via local templating, an LLM API, or any
 * other strategy. The shape of the returned array maps directly onto the
 * `individual_development_plans` table columns so the orchestrator can persist
 * it without adapter glue.
 */
interface IdpGenerator
{
    /**
     * Build an IDP for the given faculty + period.
     *
     * @return array{
     *   summary: string,
     *   strengths: list<array{area: string, evidence: string, score: float|null}>,
     *   growth_areas: list<array{area: string, current_level: string, target_level: string, gap: float|null, evidence: string}>,
     *   goals: list<array{title: string, specific: string, measurable: string, achievable: string, relevant: string, time_bound: string, target_date: string|null}>,
     *   action_items: list<array{phase: string, action: string, resources: string, owner: string, due: string|null}>,
     *   expected_outcomes: list<string>,
     *   recommended_resources: list<string>,
     *   generated_from: array<string, mixed>,
     *   engine: string,
     *   model_version: string,
     * }
     */
    public function generate(FacultyProfile $profile, string $semester, string $schoolYear): array;
}
