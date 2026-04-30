<?php

namespace App\Mail;

use App\Models\PasswordResetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PasswordResetRequest $resetRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your password reset request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-declined',
            with: [
                'request' => $this->resetRequest,
                'user'    => $this->resetRequest->user,
            ],
        );
    }
}
