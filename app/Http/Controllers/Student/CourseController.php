<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\JoinedCourse;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function join(Request $request)
    {
        $request->validate([
            'join_code' => 'required|string|size:6',
        ]);

        $course = Course::where('join_code', $request->join_code)
            ->where('status', 'Active')
            ->first();

        if (!$course) {
            return back()->withErrors([
                'join_code' => 'Invalid course code or course not available.'
            ]);
        }

        // Check if already joined
        $alreadyJoined = JoinedCourse::where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyJoined) {
            return back()->withErrors([
                'join_code' => 'You have already joined this course.'
            ]);
        }

        // Join the course
        JoinedCourse::create([
            'user_id' => auth()->id(),
            'course_id' => $course->id,
            'role' => 'Student',
            'last_accessed_at' => now(),
        ]);

        return back()->with('success', 'Successfully joined the course!');
    }

    public function joinedCourses()
    {
        $joinedCourses = JoinedCourse::where('user_id', auth()->id())
            ->where('role', 'Student')
            ->with(['course' => function($query) {
                $query->with('user:id,first_name,last_name')
                    ->where('status', 'Active');
            }])
            ->get()
            ->filter(function($joinedCourse) {
                return $joinedCourse->course !== null;
            })
            ->map(function($joinedCourse) {
                return [
                    'id' => $joinedCourse->course->id,
                    'title' => $joinedCourse->course->title,
                    'section' => $joinedCourse->course->section,
                    'units' => $joinedCourse->course->units,
                    'teacher_name' => $joinedCourse->course->user->first_name . ' ' . $joinedCourse->course->user->last_name,
                ];
            })
            ->values();

        return response()->json($joinedCourses);
    }

    public function show($id)
    {
        // Verify student is enrolled in this course
        $joinedCourse = JoinedCourse::where('user_id', auth()->id())
            ->where('course_id', $id)
            ->where('role', 'Student')
            ->first();

        if (!$joinedCourse) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not enrolled in this course.');
        }

        // Update last accessed timestamp
        $joinedCourse->update([
            'last_accessed_at' => now(),
        ]);

        $course = Course::with('user:id,first_name,last_name')
            ->findOrFail($id);

        // Get course grades from gradebook (already cast to array in model)
        $gradebook = $course->gradebook ?? null;

        $courseGrades = [
            'overall_percentage' => $gradebook['overallGrade'] ?? '0.00',
            'total_earned' => $gradebook['totalEarned'] ?? 0,
            'total_points' => $gradebook['totalPoints'] ?? 0,
            'midterm' => $gradebook['termGrades']['midterm'] ?? '—',
            'tentative_final' => $gradebook['termGrades']['tentativeFinal'] ?? '—',
            'final' => $gradebook['termGrades']['final'] ?? '—',
            'categories' => [],
        ];

        // Build category breakdown
        if ($gradebook && isset($gradebook['categories'])) {
            foreach ($gradebook['categories'] as $categoryName => $categoryData) {
                $courseGrades['categories'][] = [
                    'name' => $categoryName,
                    'earned' => $categoryData['earned'] ?? 0,
                    'total' => $categoryData['total'] ?? 0,
                    'percentage' => $categoryData['percentage'] ?? '—',
                ];
            }
        }

        // Get all classwork for this course
        $classworks = Classwork::where('course_id', $id)
            ->with(['creator:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($classwork) {
                // Get student's submission for this classwork
                $submission = ClassworkSubmission::where('classwork_id', $classwork->id)
                    ->where('student_id', auth()->id())
                    ->first();

                return [
                    'id' => $classwork->id,
                    'type' => $classwork->type,
                    'title' => $classwork->title,
                    'description' => $classwork->description,
                    'due_date' => $classwork->due_date?->format('M d, Y h:i A'),
                    'due_date_raw' => $classwork->due_date,
                    'points' => $classwork->points,
                    'attachments' => $classwork->attachments,
                    'has_submission' => $classwork->has_submission,
                    'status' => $classwork->status,
                    'color_code' => $classwork->color_code ?? $classwork->color,
                    'created_by_name' => $classwork->creator->first_name . ' ' . $classwork->creator->last_name,
                    'created_at' => $classwork->created_at->format('M d, Y h:i A'),
                    // Student submission data
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'content' => $submission->submission_content,
                        'attachments' => $submission->attachments,
                        'grade' => $submission->grade,
                        'feedback' => $submission->feedback,
                        'submitted_at' => $submission->submitted_at?->format('M d, Y h:i A'),
                        'graded_at' => $submission->graded_at?->format('M d, Y h:i A'),
                    ] : null,
                ];
            });

        // Separate into pending and completed
        $pendingClassworks = $classworks->filter(function($cw) {
            return !$cw['submission'] || $cw['submission']['status'] === 'draft';
        })->values();

        $completedClassworks = $classworks->filter(function($cw) {
            return $cw['submission'] && in_array($cw['submission']['status'], ['submitted', 'graded']);
        })->values();

        return inertia('Student/CourseView', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'section' => $course->section,
                'units' => $course->units,
                'join_code' => $course->join_code,
                'teacher_name' => $course->user->first_name . ' ' . $course->user->last_name,
            ],
            'courseGrades' => $courseGrades,
            'classworks' => $classworks,
            'pendingClassworks' => $pendingClassworks,
            'completedClassworks' => $completedClassworks,
        ]);
    }

    public function submitClasswork(Request $request, $classworkId)
    {
        $classwork = Classwork::findOrFail($classworkId);
        
        // Verify student is enrolled in the course
        $enrolled = JoinedCourse::where('user_id', auth()->id())
            ->where('course_id', $classwork->course_id)
            ->where('role', 'Student')
            ->exists();

        if (!$enrolled) {
            return back()->withErrors(['error' => 'You are not enrolled in this course.']);
        }

        $validated = $request->validate([
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        // Handle file uploads
        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('submissions', 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        // Create or update submission
        $submission = ClassworkSubmission::updateOrCreate(
            [
                'classwork_id' => $classwork->id,
                'student_id' => auth()->id(),
            ],
            [
                'submission_content' => $validated['content'] ?? '',
                'attachments' => $uploadedFiles,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Assignment submitted successfully!');
    }
}
