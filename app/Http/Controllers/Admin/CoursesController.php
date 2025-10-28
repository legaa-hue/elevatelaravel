<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $academicYearId = $request->get('academic_year');
        $instructorId = $request->get('instructor');
        $status = $request->get('status');
        $program = $request->get('program');
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);

        // Build query
        $query = Course::with(['teacher', 'academicYear'])
            ->withCount('students');

        // Filter by academic year
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        // Filter by instructor
        if ($instructorId) {
            $query->where('teacher_id', $instructorId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by program (based on course code prefixes)
        if ($program) {
            $programPrefixes = [
                'ADMINISTRATION' => ['EDUAS'],
                'MATHEMATICS' => ['EDUMT'],
                'SCIENCE' => ['EDUSC'],
                'FILIPINO' => ['EDUFI'],
                'MAPEH' => ['EDUMAP'],
                'TLE' => ['EDUTLE'],
                'HISTORY' => ['EDUHI'],
                'ENGLISH' => ['EDUEN'],
                'PRESCHOOL' => ['EDUPRE'],
                'GUIDANCE' => ['EDUGC'],
                'ALTERNATIVE' => ['EDUAL'],
                'SPECIAL' => ['EDUSN']
            ];

            if (isset($programPrefixes[$program])) {
                $prefixes = $programPrefixes[$program];
                $query->where(function ($q) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        $q->orWhere('title', 'like', $prefix . '%');
                    }
                    // Also include common courses (EDUCN, EDUC)
                    $q->orWhere('title', 'like', 'EDUCN%')
                      ->orWhere('title', 'like', 'EDUC %');
                });
            }
        }

        // Search by course code, title, or section
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('section', 'like', '%' . $search . '%');
            });
        }

        // Get paginated results
        $courses = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Map courses data
        $coursesData = $courses->through(function ($course) {
            return [
                'id' => $course->id,
                'courseCode' => $this->extractCourseCode($course->title),
                'title' => $course->title,
                'section' => $course->section,
                'instructor' => $course->teacher ? $course->teacher->first_name . ' ' . $course->teacher->last_name : 'N/A',
                'instructorId' => $course->teacher_id,
                'academicYear' => $course->academicYear ? $course->academicYear->year_name : 'N/A',
                'academicYearId' => $course->academic_year_id,
                'enrolledStudents' => $course->students_count,
                'status' => $course->status,
                'description' => $course->description,
                'joinCode' => $course->join_code,
                'dateCreated' => $course->created_at->format('M d, Y'),
                'lastUpdated' => $course->updated_at->format('M d, Y'),
            ];
        });

        // Get summary statistics
        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'Active')->count();
        $ongoingCourses = Course::where('status', 'Active')
            ->whereHas('academicYear', function ($q) {
                $q->where('status', 'Active');
            })->count();
        $completedCourses = Course::where('status', 'Archived')->count();
        $totalInstructors = User::where('role', 'teacher')
            ->whereHas('courses')
            ->count();

        // Get all academic years for filter
        $academicYears = AcademicYear::orderBy('created_at', 'desc')->get();

        // Get all instructors for filter
        $instructors = User::where('role', 'teacher')
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ];
            });

        // Get active academic year
        $activeAcademicYear = AcademicYear::where('status', 'Active')->first();

        return Inertia::render('Admin/Courses', [
            'courses' => $coursesData,
            'summary' => [
                'totalCourses' => $totalCourses,
                'activeCourses' => $activeCourses,
                'ongoingCourses' => $ongoingCourses,
                'completedCourses' => $completedCourses,
                'totalInstructors' => $totalInstructors,
            ],
            'academicYears' => $academicYears,
            'instructors' => $instructors,
            'activeAcademicYear' => $activeAcademicYear,
            'filters' => [
                'academic_year' => $academicYearId,
                'instructor' => $instructorId,
                'status' => $status,
                'program' => $program,
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'section' => 'required|string|max:100',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'status' => 'nullable|in:Pending,Active,Inactive,Archived',
        ]);

        $validated['join_code'] = strtoupper(Str::random(6));
        $validated['status'] = $validated['status'] ?? 'Active';

        $course = Course::create($validated);

        // Log action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'description' => 'Admin created course: ' . $course->title,
            'model_type' => 'Course',
            'model_id' => $course->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'section' => 'required|string|max:100',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'status' => 'nullable|in:Pending,Active,Inactive,Archived',
        ]);

        $course->update($validated);

        // Log action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'description' => 'Admin updated course: ' . $course->title,
            'model_type' => 'Course',
            'model_id' => $course->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $title = $course->title;

        // Delete related records first to avoid foreign key constraints
        // Delete joined courses (student enrollments)
        $course->joinedCourses()->delete();
        
        // Delete classworks and their submissions
        foreach ($course->classworks as $classwork) {
            $classwork->submissions()->delete();
            $classwork->delete();
        }

        // Log action before deletion
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'description' => 'Admin deleted course: ' . $title,
            'model_type' => 'Course',
            'model_id' => $course->id,
            'ip_address' => request()->ip(),
        ]);

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function archive(Course $course)
    {
        $course->update(['status' => 'Archived']);

        // Log action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'archive',
            'description' => 'Admin archived course: ' . $course->title,
            'model_type' => 'Course',
            'model_id' => $course->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course archived successfully.');
    }

    public function restore($id)
    {
        $course = Course::findOrFail($id);
        $course->update(['status' => 'Active']);

        // Log action
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'restore',
            'description' => 'Admin restored course: ' . $course->title,
            'model_type' => 'Course',
            'model_id' => $course->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course restored successfully.');
    }

    private function extractCourseCode($title)
    {
        // Extract course code from title (e.g., "EDUCN 204 – Statistics" -> "EDUCN 204")
        if (preg_match('/^([A-Z]+\s*\d+)/', $title, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
