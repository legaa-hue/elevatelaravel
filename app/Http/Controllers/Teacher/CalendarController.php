<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * Display the teacher calendar.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get teacher's own courses
        $courses = Course::where('teacher_id', $user->id)
            ->orWhereHas('joinedCourses', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('role', 'Teacher');
            })
            ->with('academicYear')
            ->get();

        // Get events visible to teachers
        $events = Event::where(function ($query) use ($user) {
            // Teacher's own events
            $query->where('user_id', $user->id)
                // Or admin/system events for teachers or both
                ->orWhere(function ($q) {
                    $q->where('target_audience', 'teachers')
                      ->orWhere('target_audience', 'both');
                });
        })
        ->with(['user', 'course'])
        ->orderBy('date', 'desc')
        ->get()
        ->map(function ($event) use ($user) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->date->format('Y-m-d'),
                'time' => $event->time,
                'description' => $event->description,
                'category' => $event->category,
                'is_deadline' => $event->is_deadline,
                'color' => $event->color,
                'visibility' => $event->visibility,
                'target_audience' => $event->target_audience,
                'course_id' => $event->course_id,
                'course_name' => $event->course ? $event->course->title : null,
                'created_by' => $event->user ? ($event->user->first_name . ' ' . $event->user->last_name) : 'System',
                'is_own' => $event->user_id === $user->id,
            ];
        });

        return Inertia::render('Teacher/Calendar', [
            'events' => $events,
            'courses' => $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'section' => $course->section,
                ];
            }),
        ]);
    }

    /**
     * Store a new event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'category' => 'required|in:general,meeting,academic,deadline,maintenance,urgent',
            'is_deadline' => 'nullable|boolean',
            'color' => 'required|string|max:7',
            'target_audience' => 'required|in:teachers,students,both',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['visibility'] = 'all'; // Teacher events are visible to their students
        $validated['is_deadline'] = $request->boolean('is_deadline', false);
        
        // If target_audience is 'students' and course_id is provided, it's a specific course announcement
        // If target_audience is 'both' and no course_id, it's for all courses
        // If target_audience is 'students' and no course_id, set to 'both' for all courses
        if ($validated['target_audience'] === 'students' && empty($validated['course_id'])) {
            $validated['target_audience'] = 'both';
        }

        Event::create($validated);

        return back()->with('success', 'Event created successfully');
    }

    /**
     * Update an event.
     */
    public function update(Request $request, Event $event)
    {
        // Check if user owns this event
        if ($event->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized action');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'category' => 'required|in:general,meeting,academic,deadline,maintenance,urgent',
            'is_deadline' => 'nullable|boolean',
            'color' => 'required|string|max:7',
            'target_audience' => 'required|in:teachers,students,both',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $validated['is_deadline'] = $request->boolean('is_deadline', false);

        $event->update($validated);

        return back()->with('success', 'Event updated successfully');
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event)
    {
        // Check if user owns this event
        if ($event->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized action');
        }

        $event->delete();

        return back()->with('success', 'Event deleted successfully');
    }

    /**
     * Update event date/time via drag and drop.
     */
    public function updateDateTime(Request $request, Event $event)
    {
        // Check if user owns this event
        if ($event->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized action');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $event->update($validated);

        return response()->json(['success' => true]);
    }
}
