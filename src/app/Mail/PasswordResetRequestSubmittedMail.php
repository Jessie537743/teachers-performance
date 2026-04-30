<?php

namespace App\Mail;

use App\Models\PasswordResetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PasswordResetRequest $resetRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password reset request received — pending approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-submitted',
            with: [
                'request' => $this->resetRequest,
                'user'    => $this->resetRequest->user,
            ],
        );
    }
}
