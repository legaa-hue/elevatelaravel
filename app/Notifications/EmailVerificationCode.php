<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    public string $verificationCode;
    public string $userName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $verificationCode, string $userName)
    {
        $this->verificationCode = $verificationCode;
        $this->userName = $userName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your ElevateGS Verification Code')
            ->greeting('Hello ' . $this->userName . '!')
            ->line('Thank you for registering with ElevateGS.')
            ->line('Your email verification code is:')
            ->line('**' . $this->verificationCode . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this code, please ignore this email.')
            ->salutation('Best regards, The ElevateGS Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
