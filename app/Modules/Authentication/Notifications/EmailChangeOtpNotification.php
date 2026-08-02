<?php

namespace App\Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otpCode,
        public string $newEmail,
        public int $expiresInMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('EDAMS security code — confirm your email change')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.email-change-otp', [
                'name' => $notifiable->name,
                'otpCode' => $this->otpCode,
                'newEmail' => $this->newEmail,
                'expiresInMinutes' => $this->expiresInMinutes,
                'profileUrl' => url('/profile'),
            ]);
    }
}
