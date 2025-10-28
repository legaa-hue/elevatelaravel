<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Event;
use App\Models\JoinedCourse;
use App\Models\Program;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        // Get statistics
        $myCourses = Course::where('teacher_id', $teacher->id)->count();
        $joinedCourses = JoinedCourse::where('user_id', $teacher->id)
            ->where('role', 'Teacher')
            ->count();
        
        // Get total students across all courses
        $totalStudents = JoinedCourse::whereIn('course_id', function ($query) use ($teacher) {
            $query->select('id')
                ->from('courses')
                ->where('teacher_id', $teacher->id);
        })
        ->where('role', 'Student')
        ->distinct('user_id')
        ->count();

        // Get upcoming events
        $upcomingEventsCount = Event::where('date', '>=', now())
            ->count();

        // Get recent courses
        $recentCourses = Course::where('teacher_id', $teacher->id)
            ->with('academicYear')
            ->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        // Get upcoming events with details
        $upcomingEvents = Event::where('date', '>=', now())
            ->orderBy('date')
            ->orderBy('time')
            ->take(5)
            ->get();

        // Get recent announcements (from events with urgent/general category)
        $recentAnnouncements = Event::whereIn('category', ['urgent', 'general'])
            ->latest()
            ->take(3)
            ->get();

        // Get programs and academic years for course creation
        $programs = Program::where('status', 'Active')->get(['id', 'name']);
        $academicYears = AcademicYear::where('status', 'Active')->get(['id', 'year_name']);

        return Inertia::render('Teacher/Dashboard', [
            'stats' => [
                'myCourses' => $myCourses,
                'joinedCourses' => $joinedCourses,
                'totalStudents' => $totalStudents,
                'upcomingEvents' => $upcomingEventsCount,
            ],
            'recentCourses' => $recentCourses,
            'upcomingEvents' => $upcomingEvents,
            'recentAnnouncements' => $recentAnnouncements,
            'programs' => $programs,
            'academicYears' => $academicYears,
        ]);
    }
}
