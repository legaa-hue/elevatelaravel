<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseViewController extends Controller
{
    public function show(Course $course)
    {
        $user = Auth::user();
        
        // Check if user is the owner or joined as teacher
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()
            ->where('user_id', $user->id)
            ->where('role', 'Teacher')
            ->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized access to this course');
        }
        
        // Load course with relationships
        $course->load([
            'teacher',
            'academicYear',
            'students' => function ($query) {
                $query->orderBy('first_name');
            },
            'teachers' => function ($query) {
                $query->orderBy('first_name');
            }
        ]);
        
        // Get classwork items for this course
        $classwork = Classwork::where('course_id', $course->id)
            ->with(['creator', 'rubricCriteria', 'quizQuestions'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                // Get submission statistics
                $totalSubmissions = ClassworkSubmission::where('classwork_id', $item->id)
                    ->whereIn('status', ['submitted', 'graded', 'returned'])
                    ->count();
                
                $gradedSubmissions = ClassworkSubmission::where('classwork_id', $item->id)
                    ->whereIn('status', ['graded', 'returned'])
                    ->count();
                
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'due_date' => $item->due_date?->format('Y-m-d H:i:s'),
                    'due_date_formatted' => $item->due_date?->format('M d, Y g:i A'),
                    'points' => $item->points,
                    'attachments' => $item->attachments,
                    'has_submission' => $item->has_submission,
                    'show_correct_answers' => $item->show_correct_answers,
                    'status' => $item->status,
                    'color_code' => $item->color_code,
                    'created_by' => $item->creator->first_name . ' ' . $item->creator->last_name,
                    'created_at' => $item->created_at->format('M d, Y g:i A'),
                    'is_todo' => $item->has_submission || $item->due_date !== null,
                    'submitted_count' => $totalSubmissions,
                    'graded_count' => $gradedSubmissions,
                    'grading_period' => $item->grading_period,
                    'grade_table_name' => $item->grade_table_name,
                    'grade_main_column' => $item->grade_main_column,
                    'grade_sub_column' => $item->grade_sub_column,
                    'rubric_criteria' => $item->rubricCriteria->map(function ($criteria) {
                        return [
                            'id' => $criteria->id,
                            'description' => $criteria->description,
                            'points' => $criteria->points,
                            'order' => $criteria->order,
                        ];
                    }),
                    'quiz_questions' => $item->quizQuestions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'type' => $question->type,
                            'question' => $question->question,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer,
                            'correct_answers' => $question->correct_answers,
                            'points' => $question->points,
                            'order' => $question->order,
                        ];
                    }),
                ];
            });
        
        return Inertia::render('Teacher/CourseView', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'section' => $course->section,
                'description' => $course->description,
                'join_code' => $course->join_code,
                'status' => $course->status,
                'gradebook' => $course->gradebook,
                'teacher' => [
                    'id' => $course->teacher->id,
                    'name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
                    'email' => $course->teacher->email,
                ],
                'academic_year' => $course->academicYear ? [
                    'id' => $course->academicYear->id,
                    'year_name' => $course->academicYear->year_name,
                ] : null,
                'is_owner' => $isOwner,
                'students_count' => $course->students_count,
                'students' => $course->students->map(function ($student) use ($course, $classwork) {
                    $pivot = $student->joinedCourses->where('course_id', $course->id)->first();
                    
                    // Get all submissions for this student in this course
                    $submissions = ClassworkSubmission::whereIn('classwork_id', $classwork->pluck('id'))
                        ->where('student_id', $student->id)
                        ->where('status', '!=', 'draft')
                        ->get();
                    
                    // Calculate progress
                    $totalClasswork = $classwork->where('has_submission', true)->count();
                    // Count unique classwork items that have been submitted (not draft status)
                    $submittedCount = $submissions->where('status', '!=', 'draft')->pluck('classwork_id')->unique()->count();
                    $gradedCount = $submissions->whereIn('status', ['graded', 'returned'])->pluck('classwork_id')->unique()->count();
                    
                    // Calculate average grade
                    $gradedSubmissions = $submissions->whereIn('status', ['graded', 'returned'])->where('grade', '!=', null);
                    $averageGrade = $gradedSubmissions->count() > 0 
                        ? round($gradedSubmissions->avg('grade'), 2)
                        : null;
                    
                    // Get recent submissions for performance graph
                    $recentSubmissions = ClassworkSubmission::whereIn('classwork_id', $classwork->pluck('id'))
                        ->where('student_id', $student->id)
                        ->whereNotNull('grade')
                        ->with('classwork')
                        ->orderBy('graded_at', 'asc')
                        ->get()
                        ->map(function ($sub) {
                            return [
                                'title' => $sub->classwork->title,
                                'grade' => $sub->grade,
                                'max_points' => $sub->classwork->points,
                                'percentage' => $sub->classwork->points > 0 ? round(($sub->grade / $sub->classwork->points) * 100, 2) : 0,
                                'graded_at' => $sub->graded_at->format('M d'),
                            ];
                        });
                    
                    // Calculate pending and not submitted counts
                    $pendingCount = $submissions->where('status', 'submitted')->count();
                    $notSubmittedCount = $totalClasswork - $submittedCount;
                    
                    return [
                        'id' => $student->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'email' => $student->email,
                        'profile_picture' => $student->profile_picture,
                        'joined_at' => $pivot ? $pivot->created_at->format('M d, Y') : null,
                        'progress' => [
                            'total_classwork' => $totalClasswork,
                            'submitted' => $submittedCount,
                            'graded' => $gradedCount,
                            'pending' => $pendingCount,
                            'not_submitted' => $notSubmittedCount,
                            'completion_rate' => $totalClasswork > 0 ? round(($submittedCount / $totalClasswork) * 100, 2) : 0,
                            'average_grade' => $averageGrade,
                        ],
                        'recent_submissions' => $recentSubmissions,
                    ];
                }),
                'teachers' => collect([$course->teacher])
                    ->merge($course->teachers)
                    ->unique('id')
                    ->map(function ($teacher) use ($course) {
                        return [
                            'id' => $teacher->id,
                            'name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'email' => $teacher->email,
                            'profile_picture' => $teacher->profile_picture,
                            'is_owner' => $teacher->id === $course->teacher_id,
                        ];
                    })
                    ->values(),
            ],
            'classwork' => $classwork,
            // For gradebook tab
            'students' => $course->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'email' => $student->email,
                ];
            }),
            'classworks' => Classwork::where('course_id', $course->id)
                ->whereIn('type', ['assignment', 'quiz', 'activity'])
                ->where('status', 'active')
                ->orderBy('created_at')
                ->get([
                    'id',
                    'title',
                    'type',
                    'points',
                    'grading_period',
                    'grade_table_name',
                    'grade_main_column',
                    'grade_sub_column',
                ]),
            // Announcements for classroom tab
            'announcements' => Event::where(function ($query) use ($course) {
                    // Show announcements that are:
                    // 1. For 'both' (all courses) by teachers who are assigned to this course AND specific to this course
                    // 2. For 'students' specifically for this course
                    $query->where(function ($q) use ($course) {
                        // Get teacher IDs who are assigned to this course (including course owner)
                        $teacherIds = collect([$course->teacher_id])->merge(
                            \DB::table('joined_courses')
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
                        'date' => $event->date->format('M d, Y'),
                        'time' => $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : null,
                        'category' => $event->category,
                        'color' => $event->color,
                        'target_audience' => $event->target_audience,
                        'author' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'Unknown',
                        'created_at' => $event->created_at->diffForHumans(),
                    ];
                }),
        ]);
    }
    
    /**
     * Get graded submissions for gradebook population
     */
    public function getGradedSubmissions(Course $course)
    {
        // Check authorization
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isJoined && !$isAdmin) {
            abort(403, 'Unauthorized');
        }

        // Get all graded submissions for classwork linked to gradebook
        $submissions = ClassworkSubmission::whereHas('classwork', function ($query) use ($course) {
            $query->where('course_id', $course->id)
                ->whereNotNull('grading_period')
                ->whereNotNull('grade_table_name')
                ->whereNotNull('grade_main_column')
                ->whereNotNull('grade_sub_column');
        })
        ->whereIn('status', ['graded', 'returned'])
        ->whereNotNull('grade')
        ->get(['id', 'classwork_id', 'student_id', 'grade']);

        return response()->json([
            'submissions' => $submissions
        ]);
    }

    /**
     * Export Final Grades as PDF
     */
    public function exportFinalGrades(Course $course)
    {
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized');
        }

        $course->load(['students', 'teacher', 'academicYear']);
        
        // Prepare students data with grades
        $studentsData = $course->students->map(function ($student) use ($course) {
            $gradebook = $course->gradebook ?? [];
            
            // Get midterm grade
            $midtermGrade = 0;
            if (isset($gradebook['midterm']['summary'][$student->id])) {
                $midtermGrade = $gradebook['midterm']['summary'][$student->id];
            } elseif (isset($gradebook['midterm']['periodGrades'][$student->id])) {
                $midtermGrade = $gradebook['midterm']['periodGrades'][$student->id];
            }
            
            // Get finals grade
            $finalsGrade = 0;
            if (isset($gradebook['finals']['summary'][$student->id])) {
                $finalsGrade = $gradebook['finals']['summary'][$student->id];
            } elseif (isset($gradebook['finals']['periodGrades'][$student->id])) {
                $finalsGrade = $gradebook['finals']['periodGrades'][$student->id];
            }
            
            // Calculate final grade (50% midterm + 50% finals by default)
            $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
            $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
            $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) + ($finalsGrade * ($finalsPercentage / 100));
            
            // Determine passing status (using Masteral: 1.75, Doctorate: 1.45)
            $passingGrade = 1.75; // Default to Masteral
            $remarks = $finalGrade > 0 && $finalGrade <= $passingGrade ? 'PASSED' : 'FAILED/RETAKE';
            
            return [
                'name' => $student->first_name . ' ' . $student->last_name,
                'student_id' => $student->student_id ?? 'N/A',
                'program' => $student->program ?? 'N/A',
                'midterm_grade' => number_format($midtermGrade, 2),
                'finals_grade' => number_format($finalsGrade, 2),
                'final_grade' => number_format($finalGrade, 2),
                'remarks' => $remarks,
            ];
        })->sortBy('name')->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.final-grades', [
            'course' => $course,
            'students' => $studentsData,
            'generated_by' => $user->first_name . ' ' . $user->last_name,
            'generated_at' => now()->format('F d, Y h:i A'),
        ]);

        $pdf->setPaper('a4', 'portrait');
        $filename = 'Final_Grades_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export Course Performance as PDF
     */
    public function exportCoursePerformance(Course $course)
    {
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized');
        }

        $course->load(['students', 'teacher', 'academicYear']);
        
        // Get all classwork
        $classwork = Classwork::where('course_id', $course->id)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get();

        // Prepare performance data
        $studentsData = $course->students->map(function ($student) use ($course, $classwork) {
            $submissions = ClassworkSubmission::whereIn('classwork_id', $classwork->pluck('id'))
                ->where('student_id', $student->id)
                ->whereIn('status', ['graded', 'returned'])
                ->whereNotNull('grade')
                ->get();

            $totalSubmissions = $submissions->count();
            $averageGrade = $totalSubmissions > 0 ? $submissions->avg('grade') : 0;
            $totalPossible = $submissions->sum(function ($sub) {
                return $sub->classwork->points ?? 0;
            });
            $totalEarned = $submissions->sum('grade');
            $percentage = $totalPossible > 0 ? ($totalEarned / $totalPossible) * 100 : 0;

            return [
                'name' => $student->first_name . ' ' . $student->last_name,
                'total_submissions' => $totalSubmissions,
                'total_classwork' => $classwork->where('has_submission', true)->count(),
                'average_grade' => number_format($averageGrade, 2),
                'percentage' => number_format($percentage, 2),
            ];
        })->sortByDesc('percentage')->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.course-performance', [
            'course' => $course,
            'students' => $studentsData,
            'generated_by' => $user->first_name . ' ' . $user->last_name,
            'generated_at' => now()->format('F d, Y h:i A'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $filename = 'Course_Performance_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export Class Standings as PDF
     */
    public function exportClassStandings(Course $course)
    {
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        
        if (!$isOwner && !$isJoined) {
            abort(403, 'Unauthorized');
        }

        $course->load(['students', 'teacher', 'academicYear']);
        
        // Prepare students data with final grades for ranking
        $studentsData = $course->students->map(function ($student) use ($course) {
            $gradebook = $course->gradebook ?? [];
            
            // Get midterm grade
            $midtermGrade = 0;
            if (isset($gradebook['midterm']['summary'][$student->id])) {
                $midtermGrade = $gradebook['midterm']['summary'][$student->id];
            } elseif (isset($gradebook['midterm']['periodGrades'][$student->id])) {
                $midtermGrade = $gradebook['midterm']['periodGrades'][$student->id];
            }
            
            // Get finals grade
            $finalsGrade = 0;
            if (isset($gradebook['finals']['summary'][$student->id])) {
                $finalsGrade = $gradebook['finals']['summary'][$student->id];
            } elseif (isset($gradebook['finals']['periodGrades'][$student->id])) {
                $finalsGrade = $gradebook['finals']['periodGrades'][$student->id];
            }
            
            // Calculate final grade
            $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
            $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
            $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) + ($finalsGrade * ($finalsPercentage / 100));
            
            return [
                'name' => $student->first_name . ' ' . $student->last_name,
                'student_id' => $student->student_id ?? 'N/A',
                'program' => $student->program ?? 'N/A',
                'final_grade' => $finalGrade,
                'final_grade_formatted' => number_format($finalGrade, 2),
            ];
        })->sortBy('final_grade')->values(); // Lower grades are better (1.0 is highest)

        // Add rank
        $studentsData = $studentsData->map(function ($student, $index) {
            $student['rank'] = $index + 1;
            return $student;
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.class-standings', [
            'course' => $course,
            'students' => $studentsData,
            'generated_by' => $user->first_name . ' ' . $user->last_name,
            'generated_at' => now()->format('F d, Y h:i A'),
        ]);

        $pdf->setPaper('a4', 'portrait');
        $filename = 'Class_Standings_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}

