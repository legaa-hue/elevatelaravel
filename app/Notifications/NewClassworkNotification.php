<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewClassworkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $classwork;
    protected $courseName;

    /**
     * Create a new notification instance.
     */
    public function __construct($classwork, $courseName)
    {
        $this->classwork = $classwork;
        $this->courseName = $courseName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $title = 'New Assignment: ' . $this->classwork->title;
        $body = sprintf(
            'A new assignment has been posted in %s. Due: %s',
            $this->courseName,
            $this->classwork->due_date ? $this->classwork->due_date->format('M d, Y') : 'No deadline'
        );

        return (new WebPushMessage)
            ->title($title)
            ->icon('/build/assets/icon-192x192.png')
            ->body($body)
            ->action('View Assignment', 'view_assignment')
            ->data([
                'url' => route('student.classwork.show', [
                    'course' => $this->classwork->course_id,
                    'classwork' => $this->classwork->id,
                ]),
                'classwork_id' => $this->classwork->id,
                'course_id' => $this->classwork->course_id,
            ])
            ->badge('/build/assets/badge-72x72.png')
            ->tag('classwork-' . $this->classwork->id)
            ->requireInteraction(true)
            ->vibrate([300, 200, 300]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_classwork',
            'classwork_id' => $this->classwork->id,
            'course_id' => $this->classwork->course_id,
            'course_name' => $this->courseName,
            'title' => $this->classwork->title,
            'due_date' => $this->classwork->due_date,
            'message' => 'A new assignment has been posted in ' . $this->courseName,
        ];
    }
}
