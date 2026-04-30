<?php

namespace App\Mail;

use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluationPeriodOpenedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public EvaluationPeriod $period,
        public string $evaluateUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Evaluation period {$this->period->school_year} – {$this->period->semester} is now open",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluation-period-opened',
            with: [
                'user'        => $this->user,
                'period'      => $this->period,
                'evaluateUrl' => $this->evaluateUrl,
            ],
        );
    }
}
