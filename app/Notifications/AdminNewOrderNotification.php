<?php

namespace App\Notifications;

use App\Models\Orders;
use App\Queue\QueueRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable, QueueRetryPolicy;

    public function __construct(private readonly Orders $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order {$this->order->order_number}")
            ->line("A new order was placed by {$this->order->user?->name}.")
            ->line('Total: '.($this->order->currency ?? config('checkout.currency')).' '.number_format((float) $this->order->total, 2));
    }
}
