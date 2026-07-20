<?php

namespace App\Mail;

use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $expiresMinutes = config('store.password_reset_otp_ttl');
        $rendered = app(EmailTemplateService::class)->render(
            'password_reset',
            ['otp' => $this->otp, 'expires_minutes' => $expiresMinutes],
            'Your verification code',
            '<h1>{{ otp }}</h1>'
        );

        return new Envelope(
            subject: $rendered['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $expiresMinutes = config('store.password_reset_otp_ttl');
        $rendered = app(EmailTemplateService::class)->render('password_reset', [
            'otp' => $this->otp,
            'expires_minutes' => $expiresMinutes,
        ], 'Your verification code', '<h1>{{ otp }}</h1><p>This code expires in {{ expires_minutes }} minutes.</p>');

        return new Content(
            view: 'mail.dynamic',
            with: ['html' => $rendered['html']],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
