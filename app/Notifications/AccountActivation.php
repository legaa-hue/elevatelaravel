<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivation extends Notification implements ShouldQueue
{
    use Queueable;

    public string $activationToken;
    public string $userName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $activationToken, string $userName)
    {
        $this->activationToken = $activationToken;
        $this->userName = $userName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $activationUrl = route('account.activate', ['token' => $this->activationToken]);
        
        return (new MailMessage)
            ->subject('Activate Your ElevateGS Account')
            ->greeting('Welcome to ElevateGS, ' . $this->userName . '!')
            ->line('Thank you for registering with ElevateGS. To complete your registration and activate your account, please click the button below.')
            ->action('Activate Account', $activationUrl)
            ->line('This activation link will expire in 24 hours.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, The ElevateGS Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
