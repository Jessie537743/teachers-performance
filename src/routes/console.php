<?php

use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('evaluation:promote-for-period {id : Evaluation period ID}', function (string $id): void {
    $period = EvaluationPeriod::query()->findOrFail($id);
    EvaluationService::runStudentPromotionForClosedPeriod($period);
    $this->info('Student promotion rules applied for period '.$period->id.' ('.$period->school_year.' — '.$period->semester.').');
})->purpose('Run year-level promotion for one closed period (same rules as automatic close)');

Artisan::command('evaluation:process-ended-periods', function (): void {
    EvaluationService::getOpenEvaluationPeriod();
    $this->info('Processed overdue open periods (if any).');
})->purpose('Close open periods past end_date and run student promotion');

// Recurring billing — runs daily at 03:00. Charges any tenant whose
// next_charge_at has elapsed (active subscriptions + grace-period retries).
\Illuminate\Support\Facades\Schedule::command('subscriptions:charge-due')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();
