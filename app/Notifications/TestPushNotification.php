<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TestPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body, $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url ?? '/dashboard';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/build/assets/icon-192x192.png')
            ->body($this->body)
            ->action('View', 'view_action')
            ->data(['url' => $this->url])
            ->badge('/build/assets/badge-72x72.png')
            ->dir('auto')
            ->image('/build/assets/notification-image.png')
            ->lang('en')
            ->renotify(false)
            ->requireInteraction(false)
            ->tag('test-notification')
            ->vibrate([200, 100, 200]);
    }
}
