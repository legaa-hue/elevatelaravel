                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\JoinedCourse;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'course_template_id' => 'required|exists:course_templates,id',
            'section' => 'required|string|max:100',
            'description' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        // Get course template to auto-fill title and units
        $courseTemplate = \App\Models\CourseTemplate::findOrFail($validated['course_template_id']);
        
        $validated['teacher_id'] = auth()->id();
        $validated['title'] = $courseTemplate->course_name;
        $validated['units'] = $courseTemplate->units;

        $course = Course::create($validated);

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Create',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Created course: {$course->title} - {$course->section}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Course created successfully!');
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'join_code' => 'required|string|exists:courses,join_code',
        ]);

        $course = Course::where('join_code', $validated['join_code'])->first();

        if (!$course) {
            return redirect()->back()->withErrors(['join_code' => 'Invalid join code.']);
        }

        // Check if course is pending approval
        if ($course->status === 'Pending') {
            return redirect()->back()->withErrors(['join_code' => 'This course is pending approval and cannot be joined yet.']);
        }

        // Check if course is not active
        if ($course->status !== 'Active') {
            return redirect()->back()->withErrors(['join_code' => 'This course is not available for joining.']);
        }

        // Check if already joined
        $alreadyJoined = JoinedCourse::where('course_id', $course->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($alreadyJoined) {
            return redirect()->back()->withErrors(['join_code' => 'You have already joined this course.']);
        }

        // Join as teacher
        JoinedCourse::create([
            'course_id' => $course->id,
            'user_id' => auth()->id(),
            'role' => 'Teacher',
        ]);

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Join',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Joined course: {$course->title} with code {$course->join_code}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Successfully joined the course!');
    }

    public function index()
    {
        $courses = Course::where('teacher_id', auth()->id())
            ->withCount('students')
            ->with('academicYear')
            ->latest()
            ->get();

        // Return JSON if requested via axios
        if (request()->wantsJson()) {
            return response()->json([
                'courses' => $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'section' => $course->section,
                        'description' => $course->description,
                        'join_code' => $course->join_code,
                        'status' => $course->status,
                        'students_count' => $course->students_count,
                        'academic_year' => $course->academicYear ? $course->academicYear->year_name : null,
                    ];
                })
            ]);
        }

        $programs = \App\Models\Program::where('status', 'Active')->get();
        $academicYears = \App\Models\AcademicYear::where('status', 'Active')->get();

        return Inertia::render('Teacher/MyCourses', [
            'courses' => $courses,
            'programs' => $programs,
            'academicYears' => $academicYears
        ]);
    }

    public function joined()
    {
        $joinedCourses = JoinedCourse::where('user_id', auth()->id())
            ->where('role', 'Teacher')
            ->with(['course.teacher', 'course.academicYear'])
            ->get()
            ->pluck('course')
            ->filter(); // Remove null values if course is deleted

        // Return JSON if requested via axios
        if (request()->wantsJson()) {
            return response()->json($joinedCourses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'section' => $course->section,
                    'description' => $course->description,
                    'join_code' => $course->join_code,
                    'status' => $course->status,
                    'teacher' => $course->teacher ? $course->teacher->name : null,
                    'academic_year' => $course->academicYear ? $course->academicYear->year_name : null,
                ];
            })->values());
        }

        return Inertia::render('Teacher/JoinedCourses', [
            'courses' => $joinedCourses,
        ]);
    }

    public function destroy(Course $course)
    {
        // Only course owner can delete
        if ($course->teacher_id !== auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $courseTitle = $course->title;
        $courseId = $course->id;
        $course->delete();

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Delete',
            'model_type' => 'Course',
            'model_id' => $courseId,
            'description' => "Deleted course: {$courseTitle}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Course deleted successfully!');
    }

    public function leave($courseId)
    {
        $joinedCourse = JoinedCourse::where('course_id', $courseId)
            ->where('user_id', auth()->id())
            ->where('role', 'Teacher')
            ->first();

        if (!$joinedCourse) {
            return redirect()->back()->withErrors(['error' => 'Course not found.']);
        }

        $course = $joinedCourse->course;
        $joinedCourse->delete();

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Leave',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Left course: {$course->title}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Successfully left the course!');
    }
}
