<?php

namespace App\Services\Idp;

use App\Contracts\IdpGenerator;
use App\Models\FacultyProfile;
use App\Models\IndividualDevelopmentPlan;

/**
 * Orchestrates IDP generation: hands off to the configured generator,
 * supersedes any prior draft/active plan for the same period, and persists
 * the new one. Controllers depend on this; the underlying provider stays
 * abstract behind IdpGenerator.
 */
class IdpService
{
    public function __construct(private readonly IdpGenerator $generator) {}

    public function findCurrent(FacultyProfile $profile, string $semester, string $schoolYear): ?IndividualDevelopmentPlan
    {
        return IndividualDevelopmentPlan::query()
            ->where('faculty_id', $profile->id)
            ->forPeriod($schoolYear, $semester)
            ->whereIn('status', ['draft', 'active', 'completed'])
            ->orderByDesc('id')
            ->first();
    }

    public function generate(
        FacultyProfile $profile,
        string $semester,
        string $schoolYear,
        ?int $generatedBy = null,
    ): IndividualDevelopmentPlan {
        $payload = $this->generator->generate($profile, $semester, $schoolYear);

        // Supersede any prior draft/active plan for this faculty + period.
        IndividualDevelopmentPlan::query()
            ->where('faculty_id', $profile->id)
            ->forPeriod($schoolYear, $semester)
            ->activeOrDraft()
            ->update(['status' => 'superseded']);

        return IndividualDevelopmentPlan::create([
            'faculty_id'            => $profile->id,
            'school_year'           => $schoolYear,
            'semester'              => $semester,
            'summary'               => $payload['summary'],
            'strengths'             => $payload['strengths'],
            'growth_areas'          => $payload['growth_areas'],
            'goals'                 => $payload['goals'],
            'action_items'          => $payload['action_items'],
            'expected_outcomes'     => $payload['expected_outcomes'],
            'recommended_resources' => $payload['recommended_resources'],
            'generated_from'        => $payload['generated_from'],
            'engine'                => $payload['engine'],
            'model_version'         => $payload['model_version'],
            'status'                => 'draft',
            'generated_by'          => $generatedBy,
        ]);
    }
}
