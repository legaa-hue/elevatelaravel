<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\JoinedCourse;
use App\Models\Event;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get active academic year
        $activeAcademicYear = AcademicYear::where('status', 'Active')->first();
        
        // Get joined courses count
        $joinedCoursesCount = JoinedCourse::where('user_id', $user->id)
            ->where('role', 'Student')
            ->count();
        
        // Get joined courses with details (recent 5 based on last accessed)
        $joinedCourses = JoinedCourse::where('user_id', $user->id)
            ->where('role', 'Student')
            ->with(['course' => function($query) {
                $query->with('user:id,first_name,last_name');
            }])
            ->orderByDesc('last_accessed_at')
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(function($joinedCourse) use ($user) {
                $course = $joinedCourse->course;
                
                // Calculate progress metrics (placeholder - will be updated with real data)
                $completedTasks = 0;
                $pendingTasks = 0;
                $totalTasks = 0;
                $currentGrade = 'N/A';
                
                // TODO: Implement real task counting and grade calculation
                // For now, use sample data
                $completedTasks = rand(5, 15);
                $totalTasks = rand(15, 30);
                $pendingTasks = $totalTasks - $completedTasks;
                $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                
                // Sample grade
                $grades = ['90', '85', '88', '92', '87', 'N/A'];
                $currentGrade = $grades[array_rand($grades)];
                
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'section' => $course->section,
                    'units' => $course->units,
                    'teacher_name' => $course->user->first_name . ' ' . $course->user->last_name,
                    'progress' => $progress,
                    'completed_tasks' => $completedTasks,
                    'pending_tasks' => $pendingTasks,
                    'current_grade' => $currentGrade,
                    'last_accessed' => $joinedCourse->last_accessed_at,
                ];
            });
        
        // Get pending tasks (classwork not submitted) - placeholder for now
        $pendingTasks = 0; // TODO: Implement classwork submission tracking
        
        // Get upcoming events (limit to 5)
        $upcomingEvents = Event::where('date', '>=', now()->toDateString())
            ->where(function($query) {
                $query->where('target_audience', 'Students')
                    ->orWhere('target_audience', 'Both');
            })
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->take(5)
            ->get();
        
        // Get recent announcements (limit to 3)
        $recentAnnouncements = Event::where('type', 'Announcement')
            ->where(function($query) {
                $query->where('target_audience', 'Students')
                    ->orWhere('target_audience', 'Both');
            })
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();
        
        $stats = [
            'joinedCourses' => $joinedCoursesCount,
            'pendingTasks' => $pendingTasks,
            'upcomingEvents' => $upcomingEvents->count(),
        ];
        
        return Inertia::render('Student/Dashboard', [
            'stats' => $stats,
            'joinedCourses' => $joinedCourses,
            'upcomingEvents' => $upcomingEvents,
            'recentAnnouncements' => $recentAnnouncements,
            'activeAcademicYear' => $activeAcademicYear?->year,
        ]);
    }
}
