<?php

namespace App\Notifications;

use App\Models\Orders;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Orders $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shipment = $this->order->shipment;
        $rendered = app(EmailTemplateService::class)->render('order_shipped', [
            'order_number' => $this->order->order_number,
            'tracking_number' => $shipment?->tracking_number ?: 'Pending',
        ], "Order {$this->order->order_number} shipped", '<p>Order {{ order_number }} shipped. Tracking: {{ tracking_number }}</p>');

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('mail.dynamic', ['html' => $rendered['html']]);
    }
}
