<?php

namespace App\Mail;

use App\Queue\QueueRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, QueueRetryPolicy, SerializesModels;

    public function __construct(
        private readonly string $mailSubject,
        private readonly string $mailHtml
    ) {}

    public function build()
    {
        return $this->subject($this->mailSubject)->html($this->mailHtml);
    }
}
