<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationLink extends Notification
{
    use Queueable;

    protected $verificationUrl;
    protected $firstName;

    public function __construct($verificationUrl, $firstName)
    {
        $this->verificationUrl = $verificationUrl;
        $this->firstName = $firstName;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address - ElevateGS')
            ->greeting('Hello ' . $this->firstName . '!')
            ->line('Thank you for starting the registration process with ElevateGS.')
            ->line('Please click the button below to verify your email address:')
            ->action('Verify Email Address', $this->verificationUrl)
            ->line('This link will expire in 30 minutes.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, The ElevateGS Team');
    }
}
