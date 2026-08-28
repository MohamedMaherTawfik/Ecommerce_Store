<?php

namespace App\Mail;

use App\Queue\QueueRetryPolicy;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable implements ShouldQueue
{
    use Queueable, QueueRetryPolicy, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        $rendered = app(EmailTemplateService::class)->render('payment_success', [
            'order_number' => $this->order->order_number,
            'currency' => $this->order->currency ?? 'USD',
            'total' => number_format((float) $this->order->total, 2),
            'user_name' => $this->order->user?->name ?? 'Customer',
        ], 'Payment received', '<h1>Payment successful</h1>');

        return $this->subject($rendered['subject'])->html($rendered['html']);
    }
}
