<?php

namespace App\Jobs;

use App\Mail\EvaluationPeriodOpenedMail;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fans out the "evaluation period opened" email to every active user with a
 * role that participates in evaluations (faculty, dean, head, student).
 * Sends are queued individually so a transient SMTP failure on one address
 * doesn't stall the rest.
 */
class NotifyEvaluationPeriodOpenedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $evaluationPeriodId) {}

    public function handle(): void
    {
        $period = EvaluationPeriod::find($this->evaluationPeriodId);
        if (! $period || ! $period->is_open) {
            return;
        }

        $evaluateUrl = url('/evaluate');

        User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where(function ($q) {
                $q->whereJsonContains('roles', 'faculty')
                  ->orWhereJsonContains('roles', 'dean')
                  ->orWhereJsonContains('roles', 'head')
                  ->orWhereJsonContains('roles', 'student');
            })
            ->select(['id', 'name', 'email', 'roles'])
            ->chunkById(200, function ($users) use ($period, $evaluateUrl) {
                foreach ($users as $user) {
                    try {
                        Mail::to($user->email)->queue(
                            new EvaluationPeriodOpenedMail($user, $period, $evaluateUrl)
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Failed to queue evaluation period notification', [
                            'user_id'   => $user->id,
                            'period_id' => $period->id,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
