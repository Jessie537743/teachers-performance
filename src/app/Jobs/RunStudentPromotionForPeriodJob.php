<?php

namespace App\Jobs;

use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Heavy work: per-student subject matching + feedback counts.
 * Dispatch with ->afterResponse() from HTTP so the browser is not blocked by PHP max_execution_time.
 */
class RunStudentPromotionForPeriodJob
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $evaluationPeriodId) {}

    public function handle(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $period = EvaluationPeriod::query()->find($this->evaluationPeriodId);
        if ($period === null) {
            return;
        }

        EvaluationService::runStudentPromotionForClosedPeriod($period);
    }
}
