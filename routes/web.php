<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    
    // Redirect based on role
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->role === 'teacher') {
        return redirect()->route('teacher.dashboard');
    } elseif ($user->role === 'student') {
        return redirect()->route('student.dashboard');
    }
    
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Calendar and Events
    Route::get('/calendar', [\App\Http\Controllers\Admin\EventController::class, 'index'])->name('calendar');
    Route::post('/events', [\App\Http\Controllers\Admin\EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [\App\Http\Controllers\Admin\EventController::class, 'update'])->name('events.update');
    Route::patch('/events/{event}/date', [\App\Http\Controllers\Admin\EventController::class, 'updateDate'])->name('events.updateDate');
    Route::delete('/events/{event}', [\App\Http\Controllers\Admin\EventController::class, 'destroy'])->name('events.destroy');
    
    Route::get('/class-record', function () {
        return Inertia::render('Admin/ClassRecord');
    })->name('class-record');
    
    // Course Management
    Route::get('/courses', [\App\Http\Controllers\Admin\CoursesController::class, 'index'])->name('courses.index');
    Route::post('/courses', [\App\Http\Controllers\Admin\CoursesController::class, 'store'])->name('courses.store');
    Route::put('/courses/{course}', [\App\Http\Controllers\Admin\CoursesController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [\App\Http\Controllers\Admin\CoursesController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/archive', [\App\Http\Controllers\Admin\CoursesController::class, 'archive'])->name('courses.archive');
    Route::post('/courses/{course}/restore', [\App\Http\Controllers\Admin\CoursesController::class, 'restore'])->name('courses.restore');
    
    // Course Approval (existing)
    Route::post('/courses/{course}/approve', [CourseApprovalController::class, 'approve']);
    Route::post('/courses/{course}/reject', [CourseApprovalController::class, 'reject']);
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users');
    Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
    
    // Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
    
    // Academic Year
    Route::get('/academic-year', [\App\Http\Controllers\Admin\AcademicYearController::class, 'index'])->name('academic-year');
    Route::post('/academic-year', [\App\Http\Controllers\Admin\AcademicYearController::class, 'store'])->name('academic-year.store');
    Route::put('/academic-year/{academicYear}', [\App\Http\Controllers\Admin\AcademicYearController::class, 'update'])->name('academic-year.update');
    Route::patch('/academic-year/{academicYear}/status', [\App\Http\Controllers\Admin\AcademicYearController::class, 'updateStatus'])->name('academic-year.updateStatus');
    Route::delete('/academic-year/{academicYear}', [\App\Http\Controllers\Admin\AcademicYearController::class, 'destroy'])->name('academic-year.destroy');
    Route::get('/academic-year/{academicYear}/download', [\App\Http\Controllers\Admin\AcademicYearController::class, 'download'])->name('academic-year.download');
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/fetch', [\App\Http\Controllers\Admin\ReportController::class, 'fetch'])->name('reports.fetch');
    
    // Programs and Course Templates
    Route::get('/programs', [\App\Http\Controllers\Admin\ProgramController::class, 'index'])->name('programs.index');
    Route::get('/programs/list', [\App\Http\Controllers\Admin\ProgramController::class, 'getPrograms'])->name('programs.list');
    Route::post('/programs', [\App\Http\Controllers\Admin\ProgramController::class, 'store'])->name('programs.store');
    Route::put('/programs/{program}', [\App\Http\Controllers\Admin\ProgramController::class, 'update'])->name('programs.update');
    Route::delete('/programs/{program}', [\App\Http\Controllers\Admin\ProgramController::class, 'destroy'])->name('programs.destroy');
    Route::get('/programs/{program}/course-templates', [\App\Http\Controllers\Admin\ProgramController::class, 'getCourseTemplates'])->name('programs.course-templates');
    
    Route::get('/course-templates', [\App\Http\Controllers\Admin\CourseTemplateController::class, 'index'])->name('course-templates.index');
    Route::post('/course-templates', [\App\Http\Controllers\Admin\CourseTemplateController::class, 'store'])->name('course-templates.store');
    Route::put('/course-templates/{courseTemplate}', [\App\Http\Controllers\Admin\CourseTemplateController::class, 'update'])->name('course-templates.update');
    Route::delete('/course-templates/{courseTemplate}', [\App\Http\Controllers\Admin\CourseTemplateController::class, 'destroy'])->name('course-templates.destroy');
    
    // Course Approval
    Route::post('/courses/{course}/approve', [\App\Http\Controllers\Admin\CourseApprovalController::class, 'approve'])->name('courses.approve');
    Route::post('/courses/{course}/reject', [\App\Http\Controllers\Admin\CourseApprovalController::class, 'reject'])->name('courses.reject');
    Route::post('/courses/{course}/archive', [\App\Http\Controllers\Admin\CourseApprovalController::class, 'archive'])->name('courses.archive');
});

// Teacher Routes
Route::middleware(['auth', 'verified', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Teacher\DashboardController::class, 'index'])->name('dashboard');
    
    // Programs and Course Templates for Teachers
    Route::get('/programs/list', [\App\Http\Controllers\Admin\ProgramController::class, 'getPrograms'])->name('programs.list');
    Route::get('/programs/{program}/course-templates', [\App\Http\Controllers\Admin\ProgramController::class, 'getCourseTemplates'])->name('programs.course-templates');
    
    // Course Management
    Route::post('/courses', [\App\Http\Controllers\Teacher\CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/join', [\App\Http\Controllers\Teacher\CourseController::class, 'join'])->name('courses.join');
    Route::get('/my-courses', [\App\Http\Controllers\Teacher\CourseController::class, 'index'])->name('my-courses');
    Route::get('/joined-courses', [\App\Http\Controllers\Teacher\CourseController::class, 'joined'])->name('joined-courses');
    Route::get('/courses/{course}', [\App\Http\Controllers\Teacher\CourseViewController::class, 'show'])->name('courses.show');
    Route::delete('/courses/{course}', [\App\Http\Controllers\Teacher\CourseController::class, 'destroy'])->name('courses.destroy');
    Route::delete('/courses/{course}/leave', [\App\Http\Controllers\Teacher\CourseController::class, 'leave'])->name('courses.leave');
    
    // Classwork Management
    Route::get('/courses/{course}/classwork', [\App\Http\Controllers\Teacher\ClassworkController::class, 'index'])->name('courses.classwork.index');
    Route::post('/courses/{course}/classwork', [\App\Http\Controllers\Teacher\ClassworkController::class, 'store'])->name('courses.classwork.store');
    Route::get('/courses/{course}/classwork/{classwork}', [\App\Http\Controllers\Teacher\ClassworkController::class, 'show'])->name('courses.classwork.show');
    Route::put('/courses/{course}/classwork/{classwork}', [\App\Http\Controllers\Teacher\ClassworkController::class, 'update'])->name('courses.classwork.update');
    Route::delete('/courses/{course}/classwork/{classwork}', [\App\Http\Controllers\Teacher\ClassworkController::class, 'destroy'])->name('courses.classwork.destroy');
    Route::post('/courses/{course}/classwork/{classwork}/submissions/{submission}/grade', [\App\Http\Controllers\Teacher\ClassworkController::class, 'gradeSubmission'])->name('courses.classwork.submissions.grade');
    
    // Gradebook
    Route::get('/gradebook', [\App\Http\Controllers\Teacher\GradebookController::class, 'index'])->name('gradebook');
    Route::get('/courses/{course}/gradebook', [\App\Http\Controllers\Teacher\GradebookController::class, 'show'])->name('courses.gradebook');
    Route::post('/courses/{course}/gradebook/save', [\App\Http\Controllers\Teacher\GradebookController::class, 'saveGrades'])->name('courses.gradebook.save');
    
    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\Teacher\CalendarController::class, 'index'])->name('calendar');
    Route::post('/calendar', [\App\Http\Controllers\Teacher\CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{event}', [\App\Http\Controllers\Teacher\CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{event}', [\App\Http\Controllers\Teacher\CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::patch('/calendar/{event}/datetime', [\App\Http\Controllers\Teacher\CalendarController::class, 'updateDateTime'])->name('calendar.updateDateTime');
    
    // Class Record
    Route::get('/class-record', function () {
        return Inertia::render('Teacher/ClassRecord');
    })->name('class-record');
    
    // Reports
    Route::get('/reports', function () {
        return Inertia::render('Teacher/Reports');
    })->name('reports');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Profile & Settings
    Route::get('/profile', function () {
        return Inertia::render('Teacher/Profile');
    })->name('profile');
    
    Route::get('/settings', function () {
        return Inertia::render('Teacher/Settings');
    })->name('settings');
});

// Student Routes
Route::middleware(['auth', 'verified', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Student\DashboardController::class, 'index'])->name('dashboard');
    
    // Join Course
    Route::post('/courses/join', [\App\Http\Controllers\Student\CourseController::class, 'join'])->name('courses.join');
    Route::get('/joined-courses', [\App\Http\Controllers\Student\CourseController::class, 'joinedCourses'])->name('joined-courses');
    Route::get('/courses/{id}', [\App\Http\Controllers\Student\CourseController::class, 'show'])->name('courses.show');
    Route::post('/classwork/{classwork}/submit', [\App\Http\Controllers\Student\CourseController::class, 'submitClasswork'])->name('classwork.submit');
    Route::delete('/classwork/{classwork}/unsubmit', [\App\Http\Controllers\Student\CourseController::class, 'unsubmitClasswork'])->name('classwork.unsubmit');
    
    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\Student\CalendarController::class, 'index'])->name('calendar');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Placeholder routes for student pages
    Route::get('/courses', function () {
        return Inertia::render('Student/Courses');
    })->name('courses');
    
    Route::get('/progress', function () {
        return Inertia::render('Student/Progress');
    })->name('progress');
});

require __DIR__.'/auth.php';
