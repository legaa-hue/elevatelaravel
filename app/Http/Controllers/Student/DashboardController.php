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
                
                // Calculate accurate progress metrics
                $totalTasks = $course->classworks()
                    ->whereIn('type', ['assignment', 'quiz', 'activity'])
                    ->where('status', 'active')
                    ->count();
                
                // Count submitted tasks (graded, returned, or submitted status)
                $completedTasks = \App\Models\ClassworkSubmission::whereIn('classwork_id', 
                    $course->classworks()
                        ->whereIn('type', ['assignment', 'quiz', 'activity'])
                        ->where('status', 'active')
                        ->pluck('id')
                )
                ->where('student_id', $user->id)
                ->whereIn('status', ['submitted', 'graded', 'returned'])
                ->count();
                
                $pendingTasks = $totalTasks - $completedTasks;
                $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                
                // Calculate current grade from graded submissions
                $gradedSubmissions = \App\Models\ClassworkSubmission::whereIn('classwork_id', 
                    $course->classworks()
                        ->whereIn('type', ['assignment', 'quiz', 'activity'])
                        ->where('status', 'active')
                        ->pluck('id')
                )
                ->where('student_id', $user->id)
                ->whereIn('status', ['graded', 'returned'])
                ->whereNotNull('grade')
                ->with('classwork')
                ->get();
                
                $currentGrade = 'N/A';
                if ($gradedSubmissions->count() > 0) {
                    $totalGrade = 0;
                    $totalPoints = 0;
                    
                    foreach ($gradedSubmissions as $submission) {
                        $classwork = $submission->classwork;
                        if ($classwork && $classwork->points > 0) {
                            $totalGrade += ($submission->grade / $classwork->points) * 100;
                            $totalPoints++;
                        }
                    }
                    
                    if ($totalPoints > 0) {
                        $currentGrade = round($totalGrade / $totalPoints, 2);
                    }
                }
                
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
        
        // Calculate accurate pending tasks count across all courses
        $allCourseIds = JoinedCourse::where('user_id', $user->id)
            ->where('role', 'Student')
            ->pluck('course_id');
        
        $totalClassworks = \App\Models\Classwork::whereIn('course_id', $allCourseIds)
            ->whereIn('type', ['assignment', 'quiz', 'activity'])
            ->where('status', 'active')
            ->count();
        
        $submittedClassworks = \App\Models\ClassworkSubmission::whereIn('classwork_id', 
            \App\Models\Classwork::whereIn('course_id', $allCourseIds)
                ->whereIn('type', ['assignment', 'quiz', 'activity'])
                ->where('status', 'active')
                ->pluck('id')
        )
        ->where('student_id', $user->id)
        ->whereIn('status', ['submitted', 'graded', 'returned'])
        ->count();
        
        $pendingTasks = $totalClassworks - $submittedClassworks;
        
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
