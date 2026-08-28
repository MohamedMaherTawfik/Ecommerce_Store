<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Queue\QueueRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable, QueueRetryPolicy;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly TicketMessage $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable->hasPermission('tickets.view')
            ? url("/admin/tickets/{$this->ticket->id}")
            : url("/en/support/{$this->ticket->id}");

        return (new MailMessage)
            ->subject("Support ticket {$this->ticket->ticket_number} updated")
            ->line("A new reply was added to: {$this->ticket->subject}")
            ->line(str($this->message->message)->limit(180))
            ->action('View ticket', $url);
    }
}
