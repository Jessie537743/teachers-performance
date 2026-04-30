<?php

namespace App\Mail;

use App\Models\PasswordResetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PasswordResetRequest $resetRequest,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your password has been reset — sign in now',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-approved',
            with: [
                'request'  => $this->resetRequest,
                'user'     => $this->resetRequest->user,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
