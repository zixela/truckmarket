<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\EmailVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth.code_mail_subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verification-code',
            with: [
                'code' => $this->code,
                'minutes' => EmailVerificationService::TTL_MINUTES,
            ],
        );
    }
}
