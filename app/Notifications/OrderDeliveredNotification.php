<?php

namespace App\Notifications;

use App\Models\Orders;
use App\Queue\QueueRetryPolicy;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable, QueueRetryPolicy;

    public function __construct(private readonly Orders $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rendered = app(EmailTemplateService::class)->render('order_delivered', [
            'order_number' => $this->order->order_number,
        ], "Order {$this->order->order_number} delivered", '<p>Order {{ order_number }} was delivered.</p>');

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('mail.dynamic', ['html' => $rendered['html']]);
    }
}
