<?php

namespace App\Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otpCode,
        public int $expiresInMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('EDAMS recovery code — reset your password')
            ->view('emails.password-reset-otp', [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'otpCode' => $this->otpCode,
                'expiresInMinutes' => $this->expiresInMinutes,
                'resetUrl' => url('/forgot-password?email='.urlencode((string) $notifiable->email)),
            ]);
    }
}
