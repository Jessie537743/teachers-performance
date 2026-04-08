<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MlApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ml_api.url', 'http://ml-api:8000');
    }

    /**
     * Request a performance prediction from the ML API.
     *
     * @param  float  $avgScore        Average evaluation score for the faculty member.
     * @param  int    $responseCount   Number of evaluation responses collected.
     * @param  float  $previousScore   Average score from the previous evaluation period.
     * @param  float  $improvementRate Rate of score change between periods (decimal).
     * @return array<string, mixed>    Decoded JSON response or an error array.
     */
    public function predict(
        float $avgScore,
        int   $responseCount,
        float $previousScore   = 0.0,
        float $improvementRate = 0.0,
    ): array {
        $response = Http::timeout(10)->post("{$this->baseUrl}/predict", [
            'avg_score'        => $avgScore,
            'response_count'   => $responseCount,
            'previous_score'   => $previousScore,
            'improvement_rate' => $improvementRate,
        ]);

        if ($response->failed()) {
            return [
                'error' => $response->json('detail') ?? 'ML API unavailable',
            ];
        }

        return $response->json();
    }

    /**
     * Trigger the ML API to train using historical data.
     * Optional term filters can scope training to a specific semester/school year.
     *
     * @param  string|null  $semester
     * @param  string|null  $schoolYear
     * @return array<string, mixed> Decoded JSON response or an error array.
     */
    public function trainCurrentTerm(?string $semester = null, ?string $schoolYear = null): array
    {
        $query = array_filter([
            'semester'    => $semester,
            'school_year' => $schoolYear,
        ], fn ($value) => filled($value));

        $response = Http::timeout(30)->get("{$this->baseUrl}/train-current-term", $query);

        if ($response->failed()) {
            return [
                'error' => $response->json('detail') ?? 'ML API unavailable',
            ];
        }

        return $response->json();
    }
}
