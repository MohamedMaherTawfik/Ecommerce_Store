<?php

namespace App\Mail;

use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $amount;

    public $userName;

    public function __construct($amount, $userName)
    {
        $this->amount = $amount;
        $this->userName = $userName;
    }

    public function build()
    {
        $rendered = app(EmailTemplateService::class)->render('payment_failed', [
            'user_name' => $this->userName,
            'currency' => 'USD',
            'total' => number_format((float) $this->amount, 2),
        ], 'Payment failed', '<h1>Payment failed</h1><p>Please try again.</p>');

        return $this->subject($rendered['subject'])->html($rendered['html']);
    }
}
