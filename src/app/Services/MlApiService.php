<?php

namespace App\Services;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MlApiService
{
    private string $baseUrl;

    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.ml_api.url', 'http://ml-api:8000');
        $this->token   = config('services.ml_api.token');
    }

    /**
     * Build an HTTP client with the shared-secret header attached when configured.
     */
    private function client(int $timeout = 10): PendingRequest
    {
        $client = Http::timeout($timeout)->acceptJson();

        if (filled($this->token)) {
            $client = $client->withHeaders(['X-ML-Token' => $this->token]);
        }

        return $client;
    }

    /**
     * Request a performance prediction from the ML API.
     *
     * @return array<string, mixed>
     */
    public function predict(
        float $avgScore,
        int   $responseCount,
        float $previousScore   = 0.0,
        float $improvementRate = 0.0,
        ?string $semester = null,
        ?string $schoolYear = null,
    ): array {
        $payload = array_merge([
            'avg_score'        => $avgScore,
            'response_count'   => $responseCount,
            'previous_score'   => $previousScore,
            'improvement_rate' => $improvementRate,
        ], array_filter([
            'semester'    => $semester,
            'school_year' => $schoolYear,
        ], fn ($value) => filled($value)));

        try {
            $response = $this->client(10)->post("{$this->baseUrl}/predict", $payload);
        } catch (HttpClientException $e) {
            return [
                'error' => $this->connectionErrorMessage($e),
            ];
        }

        if ($response->failed()) {
            return [
                'error' => $response->json('detail') ?? 'ML API unavailable',
            ];
        }

        return $response->json();
    }

    /**
     * Trigger the ML API to train using historical data.
     *
     * @return array<string, mixed>
     */
    public function trainCurrentTerm(?string $semester = null, ?string $schoolYear = null): array
    {
        $payload = array_filter([
            'semester'    => $semester,
            'school_year' => $schoolYear,
        ], fn ($value) => filled($value));

        try {
            $response = $this->client(60)->post("{$this->baseUrl}/train-current-term", $payload);
        } catch (HttpClientException $e) {
            return [
                'error' => $this->connectionErrorMessage($e),
            ];
        }

        if ($response->failed()) {
            return [
                'error' => $response->json('detail') ?? 'ML API unavailable',
            ];
        }

        return $response->json();
    }

    /**
     * Lightweight health probe.
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        try {
            $response = $this->client(5)->get("{$this->baseUrl}/health");
        } catch (HttpClientException $e) {
            return [
                'status' => 'down',
                'error'  => $this->connectionErrorMessage($e),
            ];
        }

        if ($response->failed()) {
            return ['status' => 'down'];
        }

        return $response->json();
    }

    private function connectionErrorMessage(HttpClientException $e): string
    {
        $base = rtrim($this->baseUrl, '/');

        return sprintf(
            'Cannot reach ML API at %s. Start the ML service (e.g. uvicorn in personnels-performance/ml_api) or fix ML_API_URL in .env. (%s)',
            $base,
            $e->getMessage()
        );
    }
}
