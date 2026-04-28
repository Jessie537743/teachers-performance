<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationDecidedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegistrationRequest $req,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->req->status === 'approved'
            ? 'Your registration was approved — sign in now'
            : 'Update on your registration request';

        return new Envelope(
            to: [$this->req->email],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-decided',
            with: [
                'req'      => $this->req,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
