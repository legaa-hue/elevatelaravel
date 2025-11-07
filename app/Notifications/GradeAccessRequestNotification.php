<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class GradeAccessRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $student;
    protected $course;

    /**
     * Create a new notification instance.
     */
    public function __construct($student, $course)
    {
        $this->student = $student;
        $this->course = $course;
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
        $title = 'Grade Access Request';
        $body = sprintf(
            '%s has requested access to view their grades in %s',
            $this->student->name,
            $this->course->title
        );

        return (new WebPushMessage)
            ->title($title)
            ->icon('/build/assets/icon-192x192.png')
            ->body($body)
            ->action('Review Request', 'review_grade_request')
            ->data([
                'url' => route('teacher.courses.show', $this->course->id),
                'student_id' => $this->student->id,
                'course_id' => $this->course->id,
                'type' => 'grade_access_request',
            ])
            ->badge('/build/assets/badge-72x72.png')
            ->tag('grade-request-' . $this->student->id . '-' . $this->course->id)
            ->requireInteraction(true)
            ->vibrate([200, 100, 200]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'grade_access_request',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'course_id' => $this->course->id,
            'course_name' => $this->course->title,
            'message' => $this->student->name . ' has requested access to view their grades in ' . $this->course->title,
            'url' => route('teacher.courses.show', $this->course->id),
        ];
    }
}
