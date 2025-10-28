<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current date
        $today = Carbon::today();
        $lastMonth = Carbon::now()->subMonth();

        // Count users by role
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $teacherCount = User::where('role', 'teacher')->count();
        $studentCount = User::where('role', 'student')->count();

        // Count users last month for comparison
        $lastMonthUsers = User::where('created_at', '<', $lastMonth)->count();
        $userGrowth = $totalUsers > 0 ? round((($totalUsers - $lastMonthUsers) / max($totalUsers, 1)) * 100, 1) : 0;

        // Get recent events from calendar
        $recentEvents = Event::with('user')
            ->where('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date,
                    'time' => $event->time,
                    'category' => $event->category,
                    'color' => $event->color,
                    'created_by' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'System',
                ];
            });

        // Get active academic year
        $activeYear = AcademicYear::where('status', 'Active')->first();

        // Get recent audit logs for activity feed
        $recentActivity = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System',
                    'created_at' => $log->created_at->diffForHumans(),
                ];
            });

        // Today's statistics
        $todayStats = [
            'actions' => AuditLog::whereDate('created_at', $today)->count(),
            'logins' => AuditLog::whereDate('created_at', $today)->where('action', 'login')->count(),
            'new_users' => User::whereDate('created_at', $today)->count(),
        ];

        // Fetch real-time metrics
        $activeCourses = Course::where('status', 'Active')->count();
        
        $metrics = [
            'activeUsers' => $totalUsers,
            'activeCourses' => $activeCourses,
            'activeInstructors' => $teacherCount,
            'activeStudents' => $studentCount,
            'userGrowth' => $userGrowth,
            'teacherGrowth' => 5, // Calculate from actual data later
            'studentGrowth' => 15, // Calculate from actual data later
        ];

        // Course statistics
        $courseStats = [
            'active' => Course::where('status', 'Active')->count(),
            'pending' => Course::where('status', 'Pending')->count(),
            'archived' => Course::where('status', 'Archived')->count(),
            'total' => Course::count(),
        ];

        // Get all courses with teacher and student information
        $courses = Course::with(['teacher', 'academicYear'])
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'userId' => 'USR' . str_pad($course->teacher_id, 3, '0', STR_PAD_LEFT),
                    'courseName' => $course->title,
                    'section' => $course->section,
                    'instructorName' => $course->teacher ? $course->teacher->first_name . ' ' . $course->teacher->last_name : 'N/A',
                    'studentCount' => $course->students_count,
                    'dateCreated' => $course->created_at->format('Y-m-d'),
                    'status' => strtolower($course->status),
                    'joinCode' => $course->join_code,
                    'academicYear' => $course->academicYear ? $course->academicYear->year_name : 'N/A',
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'metrics' => $metrics,
            'events' => $recentEvents,
            'courseStats' => $courseStats,
            'courses' => $courses,
            'activeAcademicYear' => $activeYear,
            'recentActivity' => $recentActivity,
            'todayStats' => $todayStats,
        ]);
    }
}
