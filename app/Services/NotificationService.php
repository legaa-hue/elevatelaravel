<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Course;
use App\Models\Classwork;

class NotificationService
{
    /**
     * Notify teacher about new student submission
     */
    public static function notifyTeacherAboutSubmission($classwork, $student)
    {
        $teacher = $classwork->course->teacher;
        
        Notification::create([
            'user_id' => $teacher->id,
            'type' => 'submission',
            'title' => 'New Submission',
            'message' => "{$student->first_name} {$student->last_name} submitted {$classwork->title}",
            'data' => [
                'course_id' => $classwork->course_id,
                'classwork_id' => $classwork->id,
                'student_id' => $student->id,
                'url' => "/teacher/courses/{$classwork->course_id}/classwork/{$classwork->id}"
            ],
        ]);
    }

    /**
     * Notify students about new classwork/material
     */
    public static function notifyStudentsAboutClasswork($classwork)
    {
        $students = $classwork->course->students;
        
        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'material',
                'title' => 'New ' . ucfirst($classwork->type),
                'message' => "New {$classwork->type} posted: {$classwork->title}",
                'data' => [
                    'course_id' => $classwork->course_id,
                    'classwork_id' => $classwork->id,
                    'url' => "/student/courses/{$classwork->course_id}"
                ],
            ]);
        }
    }

    /**
     * Notify all teachers about admin announcement
     */
    public static function notifyTeachersAboutAnnouncement($announcement)
    {
        $teachers = User::where('role', 'Teacher')->get();
        
        foreach ($teachers as $teacher) {
            Notification::create([
                'user_id' => $teacher->id,
                'type' => 'announcement',
                'title' => 'Admin Announcement',
                'message' => $announcement->title ?? 'New announcement from admin',
                'data' => [
                    'announcement_id' => $announcement->id ?? null,
                    'url' => '/teacher/announcements'
                ],
            ]);
        }
    }

    /**
     * Notify all students about admin/teacher announcement
     */
    public static function notifyStudentsAboutAnnouncement($announcement, $courseId = null)
    {
        if ($courseId) {
            // Notify students in specific course
            $course = Course::find($courseId);
            $students = $course->students;
        } else {
            // Notify all students (admin announcement)
            $students = User::where('role', 'Student')->get();
        }
        
        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'announcement',
                'title' => $courseId ? 'Course Announcement' : 'Admin Announcement',
                'message' => $announcement->title ?? $announcement->message ?? 'New announcement',
                'data' => [
                    'course_id' => $courseId,
                    'announcement_id' => $announcement->id ?? null,
                    'url' => $courseId ? "/student/courses/{$courseId}" : '/student/announcements'
                ],
            ]);
        }
    }

    /**
     * Notify specific user
     */
    public static function notifyUser($userId, $type, $title, $message, $data = [])
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
