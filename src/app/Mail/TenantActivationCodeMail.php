<?php

namespace App\Mail;

use App\Models\ActivationCode;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantActivationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public ActivationCode $code,
        public string $tenantUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your activation code for ' . $this->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-activation-code',
            with: [
                'tenant'      => $this->tenant,
                'code'        => $this->code,
                'tenantUrl'   => $this->tenantUrl,
                'activateUrl' => rtrim(config('app.url'), '/') . '/activate?code=' . $this->code->code,
            ],
        );
    }
}
