<?php

namespace App\Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?string $temporaryPassword = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to EDAMS — your account is ready')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.welcome', [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => url('/login'),
            ]);
    }
}
