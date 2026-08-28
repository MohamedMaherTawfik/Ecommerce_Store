<?php

namespace App\Notifications;

use App\Models\Orders;
use App\Queue\QueueRetryPolicy;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable, QueueRetryPolicy;

    public function __construct(private readonly Orders $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rendered = app(EmailTemplateService::class)->render('payment_success', [
            'user_name' => $notifiable->name,
            'order_number' => $this->order->order_number,
            'currency' => $this->order->currency ?? config('checkout.currency'),
            'total' => number_format((float) $this->order->total, 2),
        ], "Payment received for {$this->order->order_number}", '<p>Payment received for {{ order_number }}.</p>');

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('mail.dynamic', ['html' => $rendered['html']]);
    }
}
