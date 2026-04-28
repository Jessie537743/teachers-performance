<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RegistrationRequest $req) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->req->email],
            subject: 'We received your registration — pending approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-submitted',
            with: ['req' => $this->req],
        );
    }
}
