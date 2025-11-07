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
use Illuminate\Support\Facades\Cache;
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

        // Idempotency guard: if the same client_token posts again within a short window, ignore duplicates
        $clientToken = $request->input('client_token');
        if ($clientToken) {
            $idempKey = 'cw_idemp:' . $user->id . ':' . $clientToken;
            // Cache::add returns false if the key already exists
            if (!Cache::add($idempKey, true, now()->addMinutes(5))) {
                // Duplicate submit detected; return a normal redirect without creating a new record
                return redirect()->route('teacher.courses.show', $course->id)
                    ->with('info', 'Duplicate request ignored');
            }
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
            'type' => 'required|in:lesson,assignment,quiz,activity,essay,project',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'sometimes|file|max:10240', // Allow file uploads, 10MB max
            'has_submission' => 'nullable|boolean',
            'status' => 'nullable|in:active,draft,archived',
            'color_code' => 'nullable|string|max:7',
            'show_correct_answers' => 'nullable|boolean',
            'grading_period' => 'nullable|in:midterm,finals',
            'grade_table_name' => 'nullable|string|max:255',
            'grade_main_column' => 'nullable|string|max:255',
            'grade_sub_column' => 'nullable|string|max:255',
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
            'client_token' => 'nullable|string|max:128',
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
                'essay' => '#8b5cf6', // purple-500
                'project' => '#f97316', // orange-500
                default => '#3b82f6',
            };
        }

        // Set has_submission to false for lessons and quizzes
        if ($validated['type'] === 'lesson' || $validated['type'] === 'quiz') {
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
                        'option_labels' => $question['option_labels'] ?? null,
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
                    'course_id' => $course->id,
                    'title' => $classwork->title . ' - Due',
                    'date' => $dueDateTime->format('Y-m-d'),
                    'time' => $dueDateTime->format('H:i:s'),
                    'description' => "Type: " . ucfirst($classwork->type) . "\n" . ($classwork->description ?? ''),
                    'category' => 'deadline',
                    'color' => '#ef4444', // Red color for deadlines
                    'is_deadline' => true,
                    'target_audience' => 'students',
                    'visibility' => 'all',
                ]);
            }

            // Auto-create subcolumn in gradebook if gradebook integration is enabled
            if ($classwork->grading_period && $classwork->grade_table_name && 
                $classwork->grade_main_column && $classwork->grade_sub_column) {
                
                $this->createGradebookSubcolumn($course, $classwork);
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
            'type' => 'sometimes|in:lesson,assignment,quiz,activity,essay,project',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
            'has_submission' => 'boolean',
            'status' => 'in:active,draft,archived',
            'color_code' => 'nullable|string|max:7',
            'show_correct_answers' => 'nullable|boolean',
            'grading_period' => 'nullable|in:midterm,finals',
            'grade_table_name' => 'nullable|string|max:255',
            'grade_main_column' => 'nullable|string|max:255',
            'grade_sub_column' => 'nullable|string|max:255',
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

        DB::beginTransaction();
        try {
            $submission->update([
                'grade' => $validated['grade'],
                'feedback' => $validated['feedback'] ?? null,
                'rubric_scores' => $validated['rubric_scores'] ?? null,
                'status' => $validated['status'] ?? 'graded',
                'graded_at' => now(),
                'graded_by' => Auth::id(),
            ]);

            // Automatically update gradebook if this classwork has gradebook integration
            if ($classwork->grading_period && $classwork->grade_table_name && 
                $classwork->grade_main_column && $classwork->grade_sub_column) {
                
                $this->updateGradebook($course, $classwork, $submission);
            }

            DB::commit();

            return redirect()->route('teacher.courses.classwork.show', [
                'course' => $course->id,
                'classwork' => $classwork->id
            ])->with('success', 'Grade saved successfully and gradebook updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error grading submission', [
                'error' => $e->getMessage(),
                'submission_id' => $submission->id,
                'classwork_id' => $classwork->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to save grade: ' . $e->getMessage());
        }
    }

    /**
     * Update gradebook with submission grade
     */
    private function updateGradebook(Course $course, Classwork $classwork, ClassworkSubmission $submission)
    {
        $gradebook = $course->gradebook ?? [];
        
        // Get the grading period data (midterm or finals)
        $period = $classwork->grading_period; // 'midterm' or 'finals'
        
        if (!isset($gradebook[$period])) {
            $gradebook[$period] = ['tables' => [], 'grades' => []];
        }
        
        // Find the table
        $tableIndex = null;
        foreach ($gradebook[$period]['tables'] as $index => $table) {
            if ($table['name'] === $classwork->grade_table_name) {
                $tableIndex = $index;
                break;
            }
        }
        
        if ($tableIndex === null) {
            logger()->warning('Grade table not found in gradebook', [
                'period' => $period,
                'table_name' => $classwork->grade_table_name,
            ]);
            return;
        }
        
        // Find the main column
        $columnIndex = null;
        foreach ($gradebook[$period]['tables'][$tableIndex]['columns'] as $index => $column) {
            if ($column['name'] === $classwork->grade_main_column) {
                $columnIndex = $index;
                break;
            }
        }
        
        if ($columnIndex === null) {
            logger()->warning('Grade column not found in gradebook', [
                'period' => $period,
                'column_name' => $classwork->grade_main_column,
            ]);
            return;
        }
        
        // Find or create the subcolumn
        $subcolumnIndex = null;
        $subcolumns = $gradebook[$period]['tables'][$tableIndex]['columns'][$columnIndex]['subcolumns'] ?? [];
        
        foreach ($subcolumns as $index => $subcolumn) {
            if ($subcolumn['name'] === $classwork->grade_sub_column) {
                $subcolumnIndex = $index;
                break;
            }
        }
        
        // If subcolumn doesn't exist, create it
        if ($subcolumnIndex === null) {
            $subcolumnIndex = count($subcolumns);
            $gradebook[$period]['tables'][$tableIndex]['columns'][$columnIndex]['subcolumns'][] = [
                'name' => $classwork->grade_sub_column,
                'maxPoints' => $classwork->points,
                'autoPopulated' => true,
                'classwork_id' => $classwork->id,
            ];
        }
        
        // Initialize grades array for this student if not exists
        $studentId = $submission->student_id;
        if (!isset($gradebook[$period]['grades'][$studentId])) {
            $gradebook[$period]['grades'][$studentId] = [];
        }
        
        if (!isset($gradebook[$period]['grades'][$studentId][$tableIndex])) {
            $gradebook[$period]['grades'][$studentId][$tableIndex] = [];
        }
        
        if (!isset($gradebook[$period]['grades'][$studentId][$tableIndex][$columnIndex])) {
            $gradebook[$period]['grades'][$studentId][$tableIndex][$columnIndex] = [];
        }
        
        // Set the grade for this student in this subcolumn
        $gradebook[$period]['grades'][$studentId][$tableIndex][$columnIndex][$subcolumnIndex] = $submission->grade;
        
        // Save the updated gradebook
        $course->update(['gradebook' => $gradebook]);
        
        logger()->info('Gradebook updated successfully', [
            'student_id' => $studentId,
            'period' => $period,
            'table' => $classwork->grade_table_name,
            'column' => $classwork->grade_main_column,
            'subcolumn' => $classwork->grade_sub_column,
            'grade' => $submission->grade,
        ]);
    }

    /**
     * Create a subcolumn in the gradebook when classwork is created
     */
    private function createGradebookSubcolumn(Course $course, Classwork $classwork)
    {
        $gradebook = $course->gradebook ?? [];
        
        // Get the grading period data (midterm or finals)
        $period = $classwork->grading_period;
        
        if (!isset($gradebook[$period])) {
            logger()->warning('Grading period not found in gradebook', [
                'period' => $period,
                'classwork_id' => $classwork->id,
            ]);
            return;
        }
        
        // Find the table
        $tableIndex = null;
        foreach ($gradebook[$period]['tables'] as $index => $table) {
            if ($table['name'] === $classwork->grade_table_name) {
                $tableIndex = $index;
                break;
            }
        }
        
        if ($tableIndex === null) {
            logger()->warning('Grade table not found in gradebook', [
                'period' => $period,
                'table_name' => $classwork->grade_table_name,
                'classwork_id' => $classwork->id,
            ]);
            return;
        }
        
        // Find the main column
        $columnIndex = null;
        foreach ($gradebook[$period]['tables'][$tableIndex]['columns'] as $index => $column) {
            if ($column['name'] === $classwork->grade_main_column) {
                $columnIndex = $index;
                break;
            }
        }
        
        if ($columnIndex === null) {
            logger()->warning('Grade column not found in gradebook', [
                'period' => $period,
                'column_name' => $classwork->grade_main_column,
                'classwork_id' => $classwork->id,
            ]);
            return;
        }
        
        // Check if subcolumn already exists
        $subcolumns = $gradebook[$period]['tables'][$tableIndex]['columns'][$columnIndex]['subcolumns'] ?? [];
        $subcolumnExists = false;
        
        foreach ($subcolumns as $subcolumn) {
            if ($subcolumn['name'] === $classwork->grade_sub_column) {
                $subcolumnExists = true;
                break;
            }
        }
        
        // If subcolumn doesn't exist, create it
        if (!$subcolumnExists) {
            $gradebook[$period]['tables'][$tableIndex]['columns'][$columnIndex]['subcolumns'][] = [
                'name' => $classwork->grade_sub_column,
                'maxPoints' => $classwork->points ?? 100,
                'autoPopulated' => true,
                'classwork_id' => $classwork->id,
            ];
            
            // Save the updated gradebook
            $course->update(['gradebook' => $gradebook]);
            
            logger()->info('Gradebook subcolumn created successfully', [
                'period' => $period,
                'table' => $classwork->grade_table_name,
                'column' => $classwork->grade_main_column,
                'subcolumn' => $classwork->grade_sub_column,
                'max_points' => $classwork->points ?? 100,
                'classwork_id' => $classwork->id,
            ]);
        } else {
            logger()->info('Gradebook subcolumn already exists', [
                'period' => $period,
                'table' => $classwork->grade_table_name,
                'column' => $classwork->grade_main_column,
                'subcolumn' => $classwork->grade_sub_column,
                'classwork_id' => $classwork->id,
            ]);
        }
    }
}
