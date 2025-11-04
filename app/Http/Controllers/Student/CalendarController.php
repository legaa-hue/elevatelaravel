<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get the IDs of courses the student is enrolled in
        $enrolledCourseIds = $user->joinedCourses()
            ->where('role', 'Student')
            ->pluck('course_id')
            ->toArray();
        
        // Get events that are for students or both
        $events = Event::where(function($query) use ($enrolledCourseIds) {
                $query->where(function($q) {
                    // Global announcements (no specific course)
                    $q->where('target_audience', 'students')
                      ->orWhere('target_audience', 'both');
                })
                ->where(function($q) use ($enrolledCourseIds) {
                    // Either no course specified (global) OR in a course the student is enrolled in
                    $q->whereNull('course_id')
                      ->orWhereIn('course_id', $enrolledCourseIds);
                });
            })
            ->with('user')
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date->format('Y-m-d'),
                    'time' => $event->time,
                    'category' => $event->category,
                    'is_deadline' => $event->is_deadline ?? ($event->category === 'deadline'),
                    'color' => $event->color,
                    'created_by' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'System',
                ];
            });

        return Inertia::render('Student/Calendar', [
            'events' => $events,
        ]);
    }
}
