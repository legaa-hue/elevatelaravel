<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
use App\Models\Course;
use App\Models\Event;
use App\Models\RubricCriteria;
use App\Models\QuizQuestion;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClassworkController extends Controller
{
    /**
     * Get all classwork for a course.
     */
    public function index(Course $course)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        $classwork = Classwork::where('course_id', $course->id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($classwork);
    }

    /**
     * Store a newly created classwork.
     */
    public function store(Request $request, Course $course)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        // Log incoming request data for debugging
        logger()->info('Classwork creation request', [
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'due_date' => $request->input('due_date'),
            'has_files' => $request->hasFile('attachments'),
            'file_count' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
            'rubric_criteria_count' => $request->has('rubric_criteria') ? count($request->input('rubric_criteria', [])) : 0,
            'quiz_questions_count' => $request->has('quiz_questions') ? count($request->input('quiz_questions', [])) : 0,
        ]);

        $validated = $request->validate([
            'type' => 'required|in:lesson,assignment,quiz,activity',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'sometimes|file|max:10240', // Allow file uploads, 10MB max
            'has_submission' => 'nullable|boolean',
            'status' => 'nullable|in:active,draft,archived',
            'color_code' => 'nullable|string|max:7',
            'rubric_criteria' => 'nullable|array',
            'rubric_criteria.*.description' => 'required_with:rubric_criteria|string',
            'rubric_criteria.*.points' => 'required_with:rubric_criteria|integer|min:0',
            'quiz_questions' => 'nullable|array',
            'quiz_questions.*.type' => 'required_with:quiz_questions|in:multiple_choice,true_false,short_answer,essay,identification,enumeration',
            'quiz_questions.*.question' => 'required_with:quiz_questions|string',
            'quiz_questions.*.options' => 'nullable|array',
            'quiz_questions.*.correct_answer' => 'nullable|string',
            'quiz_questions.*.correct_answers' => 'nullable|array',
            'quiz_questions.*.points' => 'required_with:quiz_questions|integer|min:0',
        ]);

        // Handle file uploads
        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('classwork', 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $validated['attachments'] = $uploadedFiles;
        } else {
            $validated['attachments'] = [];
        }

        $validated['course_id'] = $course->id;
        $validated['created_by'] = Auth::id();
        
        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }
        
        // Set default color based on type if not provided
        if (!isset($validated['color_code'])) {
            $validated['color_code'] = match($validated['type']) {
                'lesson' => '#3b82f6', // blue-500
                'assignment' => '#eab308',     // yellow-500
                'quiz' => '#ef4444',     // red-500
                'activity' => '#10b981', // green-500
                default => '#3b82f6',
            };
        }

        // Set has_submission to false for lessons
        if ($validated['type'] === 'lesson') {
            $validated['has_submission'] = false;
        }

        DB::beginTransaction();
        try {
            $classwork = Classwork::create($validated);

            logger()->info('Classwork created successfully', [
                'id' => $classwork->id,
                'type' => $classwork->type,
                'title' => $classwork->title,
                'due_date' => $classwork->due_date,
                'attachments_count' => is_array($classwork->attachments) ? count($classwork->attachments) : 0,
            ]);

            // Create rubric criteria for non-quiz types
            if ($validated['type'] !== 'quiz' && isset($validated['rubric_criteria'])) {
                foreach ($validated['rubric_criteria'] as $index => $criteria) {
                    RubricCriteria::create([
                        'classwork_id' => $classwork->id,
                        'description' => $criteria['description'],
                        'points' => $criteria['points'],
                        'order' => $index,
                    ]);
                }
            }

            // Create quiz questions for quiz type
            if ($validated['type'] === 'quiz' && isset($validated['quiz_questions'])) {
                foreach ($validated['quiz_questions'] as $index => $question) {
                    QuizQuestion::create([
                        'classwork_id' => $classwork->id,
                        'type' => $question['type'],
                        'question' => $question['question'],
                        'options' => $question['options'] ?? null,
                        'correct_answer' => $question['correct_answer'] ?? null,
                        'correct_answers' => $question['correct_answers'] ?? null,
                        'points' => $question['points'],
                        'order' => $index,
                    ]);
                }
            }

            // Auto-create calendar event if there's a due date
            if ($classwork->due_date) {
                $dueDateTime = new \DateTime($classwork->due_date);
                Event::create([
                    'user_id' => Auth::id(),
                    'title' => $classwork->title . ' - Due',
                    'date' => $dueDateTime->format('Y-m-d'),
                    'time' => $dueDateTime->format('H:i:s'),
                    'description' => "Type: " . ucfirst($classwork->type) . "\n" . ($classwork->description ?? ''),
                    'category' => 'deadline',
                    'color' => '#ef4444', // Red color for deadlines
                ]);
            }

            DB::commit();

            // Notify students about new classwork/material
            NotificationService::notifyStudentsAboutClasswork($classwork);

            // Return an Inertia-friendly redirect to the course view so the frontend receives a valid Inertia response
            return redirect()->route('teacher.courses.show', $course->id)
                ->with('success', ucfirst($classwork->type) . ' created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the exception and redirect back with an error message so Inertia receives a proper response
            logger()->error('Failed to create classwork', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['attachments']), // Don't log file data
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to create classwork: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified classwork.
     */
    public function update(Request $request, Course $course, Classwork $classwork)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        if ($classwork->course_id !== $course->id) {
            return response()->json(['message' => 'Classwork does not belong to this course'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:lesson,assignment,quiz,activity',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
            'has_submission' => 'boolean',
            'status' => 'in:active,draft,archived',
            'color_code' => 'nullable|string|max:7',
        ]);

        // Handle file uploads for update
        if ($request->hasFile('attachments')) {
            $existingFiles = $classwork->attachments ?? [];
            $uploadedFiles = [];
            
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('classwork', 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            
            // Merge with existing files
            $validated['attachments'] = array_merge($existingFiles, $uploadedFiles);
        }

        $classwork->update($validated);

        // Return Inertia-friendly redirect
        return redirect()->route('teacher.courses.show', $course->id)->with('success', 'Classwork updated.');
    }

    /**
     * Remove the specified classwork.
     */
    public function destroy(Course $course, Classwork $classwork)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        if ($classwork->course_id !== $course->id) {
            return response()->json(['message' => 'Classwork does not belong to this course'], 403);
        }

        $classwork->delete();

        // Return Inertia-friendly redirect
        return redirect()->route('teacher.courses.show', $course->id)->with('success', 'Classwork deleted successfully.');
    }

    /**
     * Show the classwork grading view with all submissions.
     */
    public function show(Course $course, Classwork $classwork)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        if ($classwork->course_id !== $course->id) {
            abort(404, 'Classwork does not belong to this course');
        }

        // Get all students enrolled in the course
        $students = $course->joinedCourses()
            ->where('role', 'Student')
            ->with('user')
            ->get()
            ->map(function ($joined) use ($classwork) {
                $student = $joined->user;
                $submission = ClassworkSubmission::where('classwork_id', $classwork->id)
                    ->where('student_id', $student->id)
                    ->with(['student', 'gradedBy'])
                    ->first();
                
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'submission' => $submission,
                ];
            });

        // Load classwork with relationships
        $classwork->load(['rubricCriteria', 'quizQuestions', 'creator']);

        return Inertia::render('Teacher/ClassworkGrading', [
            'course' => $course,
            'classwork' => $classwork,
            'submissions' => ClassworkSubmission::where('classwork_id', $classwork->id)
                ->with(['student', 'gradedBy'])
                ->get(),
            'students' => $students,
        ]);
    }

    /**
     * Save grade for a submission.
     */
    public function gradeSubmission(Request $request, Course $course, Classwork $classwork, ClassworkSubmission $submission)
    {
        // Check if user is the teacher or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }

        if ($classwork->course_id !== $course->id || $submission->classwork_id !== $classwork->id) {
            abort(404, 'Invalid submission or classwork');
        }

        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:' . $classwork->points,
            'feedback' => 'nullable|string',
            'rubric_scores' => 'nullable|array',
            'status' => 'in:graded,returned',
        ]);

        $submission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'rubric_scores' => $validated['rubric_scores'] ?? null,
            'status' => $validated['status'] ?? 'graded',
            'graded_at' => now(),
            'graded_by' => Auth::id(),
        ]);

        return redirect()->route('teacher.courses.classwork.show', [
            'course' => $course->id,
            'classwork' => $classwork->id
        ])->with('success', 'Grade saved successfully.');
    }
}
