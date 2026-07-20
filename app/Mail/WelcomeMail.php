<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        $rendered = app(EmailTemplateService::class)->render('welcome', [
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
        ], 'Welcome to '.config('app.name'), '<h1>Welcome, {{ user_name }}!</h1>');

        return $this->subject($rendered['subject'])->html($rendered['html']);
    }
}
