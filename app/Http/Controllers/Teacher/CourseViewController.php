<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
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
                    'status' => $item->status,
                    'color_code' => $item->color_code,
                    'created_by' => $item->creator->first_name . ' ' . $item->creator->last_name,
                    'created_at' => $item->created_at->format('M d, Y g:i A'),
                    'is_todo' => $item->has_submission || $item->due_date !== null,
                    'submitted_count' => $totalSubmissions,
                    'graded_count' => $gradedSubmissions,
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
                    $submittedCount = $submissions->where('status', '!=', 'draft')->count();
                    $gradedCount = $submissions->whereIn('status', ['graded', 'returned'])->count();
                    
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
                ->get(['id', 'title', 'type', 'points']),
        ]);
    }
}
