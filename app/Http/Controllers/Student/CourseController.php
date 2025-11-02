<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\JoinedCourse;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
use App\Models\Event;
use App\Services\NotificationService;
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

        // Check if student is trying to join a course they created (shouldn't happen but added for safety)
        if ($course->teacher_id === auth()->id()) {
            return back()->withErrors([
                'join_code' => 'You cannot join your own course as a student.'
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

        $course = Course::with(['user:id,first_name,last_name,email,profile_picture', 'teachers:id,first_name,last_name,email,profile_picture'])
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
            ->with(['creator:id,first_name,last_name', 'rubricCriteria', 'quizQuestions'])
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
                    'rubric_criteria' => $classwork->rubricCriteria->map(function($criteria) {
                        return [
                            'description' => $criteria->description,
                            'points' => $criteria->points,
                            'order' => $criteria->order,
                        ];
                    }),
                    'quiz_questions' => $classwork->quizQuestions->map(function($question) {
                        return [
                            'type' => $question->type,
                            'question' => $question->question,
                            'options' => $question->options,
                            'option_labels' => $question->option_labels,
                            'correct_answer' => $question->correct_answer,
                            'correct_answers' => $question->correct_answers, // Added for enumeration
                            'points' => $question->points,
                            'order' => $question->order,
                        ];
                    }),
                    'has_submission' => $classwork->has_submission,
                    'show_correct_answers' => $classwork->show_correct_answers,
                    'status' => $classwork->status,
                    'color_code' => $classwork->color_code ?? $classwork->color,
                    'created_by_name' => $classwork->creator->first_name . ' ' . $classwork->creator->last_name,
                    'created_at' => $classwork->created_at->format('M d, Y h:i A'),
                    // Student submission data
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'content' => $submission->submission_content,
                        'submission_content' => $submission->submission_content,
                        'link' => $submission->link,
                        'attachments' => $submission->attachments,
                        'quiz_answers' => $submission->quiz_answers,
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

        // Get announcements for this course
        $announcements = Event::where(function ($query) use ($course) {
                // Show announcements that are:
                // 1. For 'both' (all courses) by teachers who are assigned to this course AND specific to this course
                // 2. For 'students' specifically for this course
                $query->where(function ($q) use ($course) {
                    // Get teacher IDs who are assigned to this course (including course owner)
                    $teacherIds = collect([$course->teacher_id])->merge(
                        DB::table('joined_courses')
                            ->where('course_id', $course->id)
                            ->where('role', 'Teacher')
                            ->pluck('user_id')
                    )->unique()->values();
                    
                    // Check if announcement is for 'both', created by assigned teacher, AND for this specific course
                    $q->where('target_audience', 'both')
                      ->whereIn('user_id', $teacherIds)
                      ->where('course_id', $course->id);
                })->orWhere(function ($q) use ($course) {
                    // Or course-specific announcement
                    $q->where('target_audience', 'students')
                      ->where('course_id', $course->id);
                });
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => \Carbon\Carbon::parse($event->date)->format('M d, Y'),
                    'time' => $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : null,
                    'category' => $event->category,
                    'color' => $event->color,
                    'target_audience' => $event->target_audience,
                    'author' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'Unknown',
                    'created_at' => $event->created_at->diffForHumans(),
                ];
            });

        // Get all students in the course (including current student)
        $classmates = $course->students()
            ->get()
            ->map(function ($student) use ($course) {
                $pivot = $student->pivot;
                
                // Get all classwork for this course that requires submission
                $classwork = $course->classworks()
                    ->where('has_submission', true)
                    ->where('status', '!=', 'draft')
                    ->get();
                
                // Get submissions from this student for this course
                $submissions = ClassworkSubmission::whereIn('classwork_id', $classwork->pluck('id'))
                    ->where('student_id', $student->id)
                    ->where('status', '!=', 'draft')
                    ->get();
                
                // Calculate progress
                $totalClasswork = $classwork->where('has_submission', true)->count();
                $submittedCount = $submissions->where('status', '!=', 'draft')->pluck('classwork_id')->unique()->count();
                $gradedCount = $submissions->whereIn('status', ['graded', 'returned'])->pluck('classwork_id')->unique()->count();
                
                // Calculate average grade
                $gradedSubmissions = $submissions->whereIn('status', ['graded', 'returned'])->where('grade', '!=', null);
                $averageGrade = $gradedSubmissions->count() > 0 
                    ? round($gradedSubmissions->avg('grade'), 2)
                    : null;
                
                // Calculate pending and not submitted counts
                $pendingCount = $submissions->where('status', 'submitted')->count();
                $notSubmittedCount = max(0, $totalClasswork - $submittedCount);
                
                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'email' => $student->email,
                    'profile_picture' => $student->profile_picture,
                    'joined_at' => $pivot ? $pivot->created_at->format('M d, Y') : null,
                    'is_current_user' => $student->id === auth()->id(),
                    'progress' => [
                        'total_classwork' => $totalClasswork,
                        'submitted' => $submittedCount,
                        'graded' => $gradedCount,
                        'pending' => $pendingCount,
                        'not_submitted' => $notSubmittedCount,
                        'completion_rate' => $totalClasswork > 0 ? min(100, round(($submittedCount / $totalClasswork) * 100, 2)) : 0,
                        'average_grade' => $averageGrade,
                    ],
                ];
            });
        
        // Get instructors (course owner and additional teachers)
        $instructors = collect([$course->user])
            ->merge($course->teachers)
            ->unique('id')
            ->map(function ($teacher) use ($course) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->first_name . ' ' . $teacher->last_name,
                    'email' => $teacher->email,
                    'profile_picture' => $teacher->profile_picture,
                    'is_owner' => $teacher->id === $course->user_id,
                ];
            })
            ->values();

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
            'announcements' => $announcements,
            'classmates' => $classmates,
            'instructors' => $instructors,
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

        // Check if already graded - can't resubmit if graded
        $existingSubmission = ClassworkSubmission::where('classwork_id', $classwork->id)
            ->where('student_id', auth()->id())
            ->first();

        if ($existingSubmission && $existingSubmission->status === 'graded' && $existingSubmission->grade !== null) {
            return back()->withErrors(['error' => 'Cannot edit a graded submission.']);
        }

        // Validate basic fields first
        $validated = $request->validate([
            'content' => 'nullable|string|max:10000',
            'link' => 'nullable|url|max:500',
            'quiz_answers' => 'nullable|array',
        ]);

        // Handle file uploads with more lenient validation
        $uploadedFiles = [];
        
        // Get existing attachments if updating
        if ($existingSubmission && $existingSubmission->attachments) {
            $uploadedFiles = $existingSubmission->attachments;
        }
        
        if ($request->hasFile('attachments')) {
            try {
                // Validate files separately for better error messages
                $fileValidation = $request->validate([
                    'attachments' => 'array|max:10',
                    'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,zip,rar|max:20480', // 20MB max per file
                ], [
                    'attachments.*.file' => 'One or more attachments is not a valid file.',
                    'attachments.*.mimes' => 'File must be a PDF, document, image, or archive file.',
                    'attachments.*.max' => 'File size must not exceed 20MB.',
                ]);
                
                // Add new files to existing attachments
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('submissions', 'public');
                        $uploadedFiles[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'size' => $file->getSize(),
                            'type' => $file->getMimeType(),
                        ];
                    }
                }
            } catch (\Exception $e) {
                return back()->withErrors(['attachments' => 'Failed to upload files. Please ensure files are under 20MB and are valid file types (PDF, documents, images, or archives).']);
            }
        }

        // Auto-grade quiz if applicable
        $autoGrade = null;
        $autoGradedStatus = 'submitted';
        $needsManualGrading = false;
        
        if ($classwork->type === 'quiz' && isset($validated['quiz_answers'])) {
            $quizQuestions = $classwork->quizQuestions;
            $totalPoints = 0;
            $earnedPoints = 0;
            foreach ($quizQuestions as $index => $question) {
                $qPoints = $question->points;
                $totalPoints += $qPoints;
                $studentAnswer = $validated['quiz_answers'][$index] ?? '';
                $type = strtolower($question->type);
                // Check if question type requires manual grading (essay/short answer)
                if (in_array($type, ['essay', 'long answer', 'short answer'])) {
                    $needsManualGrading = true;
                    continue;
                }
                // ENUMERATION: Each correct = (points / number of correct answers)
                if ($type === 'enumeration' && is_array($question->correct_answers) && count($question->correct_answers) > 0) {
                    $numCorrect = count($question->correct_answers);
                    $perItem = $qPoints / $numCorrect;
                    $studentAnsArr = is_array($studentAnswer) ? $studentAnswer : [];
                    foreach ($question->correct_answers as $ansIdx => $corr) {
                        if (isset($studentAnsArr[$ansIdx]) && trim(strtolower($studentAnsArr[$ansIdx])) === trim(strtolower($corr))) {
                            $earnedPoints += $perItem;
                        }
                    }
                    continue;
                }
                // Auto-grade objective questions (multiple choice, true/false, identification, etc.)
                if ($question->correct_answer) {
                    $correctAnswer = trim(strtolower($question->correct_answer));
                    $studentAnswerNormalized = trim(strtolower($studentAnswer));
                    if ($correctAnswer === $studentAnswerNormalized) {
                        $earnedPoints += $qPoints;
                    }
                }
            }
            // If no questions need manual grading, auto-grade completely
            if (!$needsManualGrading && $totalPoints > 0) {
                $autoGrade = $earnedPoints;
                $autoGradedStatus = 'graded';
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
                'link' => $validated['link'] ?? null,
                'attachments' => $uploadedFiles,
                'quiz_answers' => $validated['quiz_answers'] ?? null,
                'grade' => $autoGrade,
                'status' => $autoGradedStatus,
                'submitted_at' => now(),
                'graded_at' => $autoGrade !== null ? now() : null,
            ]
        );

        // Notify teacher about the submission
        NotificationService::notifyTeacherAboutSubmission($classwork, auth()->user());

        // Prepare success message
        $successMessage = 'Work submitted successfully!';
        if ($autoGrade !== null) {
            $successMessage = "Quiz submitted and auto-graded! Your score: {$autoGrade}/{$totalPoints} points.";
        } elseif ($needsManualGrading) {
            $successMessage = 'Quiz submitted! Your teacher will grade the essay questions.';
        }

        return redirect()->back()->with('success', $successMessage);
    }

    public function unsubmitClasswork($classworkId)
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

        $submission = ClassworkSubmission::where('classwork_id', $classwork->id)
            ->where('student_id', auth()->id())
            ->first();

        if (!$submission) {
            return back()->withErrors(['error' => 'No submission found.']);
        }

        // Can't unsubmit if already graded
        if ($submission->status === 'graded' || $submission->grade !== null) {
            return back()->withErrors(['error' => 'Cannot unsubmit a graded work.']);
        }

        // Delete the submission
        $submission->delete();

        return redirect()->back()->with('success', 'Submission removed. You can now resubmit.');
    }
}
