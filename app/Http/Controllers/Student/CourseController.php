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

        // Check grade access status
        $gradeAccessGranted = $joinedCourse->grade_access_granted;
        $gradeAccessRequested = $joinedCourse->grade_access_requested;

        $course = Course::with(['user:id,first_name,last_name,email,profile_picture', 'teachers:id,first_name,last_name,email,profile_picture'])
            ->findOrFail($id);

        // Get course grades from gradebook (already cast to array in model)
        $gradebook = $course->gradebook ?? null;
        $studentId = auth()->id();

        // Get student's midterm and finals grades from periodGrades
        $midtermGrade = null;
        $finalsGrade = null;
        $midtermComponents = [];
        $finalsComponents = [];
        $midtermTables = [];
        $finalsTables = [];

        if ($gradebook) {
            // Get midterm grade (percentage from gradebook)
            if (isset($gradebook['midterm']['periodGrades'][$studentId])) {
                $midtermGrade = $gradebook['midterm']['periodGrades'][$studentId];
            }

            // Get midterm tables
            if (isset($gradebook['midterm']['tables'])) {
                $midtermTables = $gradebook['midterm']['tables'];
            }
            
            // Extract midterm component scores by aggregating individual scores for each table
            if (isset($gradebook['midterm']['grades'][$studentId])) {
                $studentGrades = $gradebook['midterm']['grades'][$studentId];
                
                // For each main table (async, sync, exam), aggregate all scores that start with that table ID
                foreach ($midtermTables as $table) {
                    $tableData = is_array($table) ? $table : (array)$table;
                    $tableId = $tableData['id'] ?? null;
                    
                    // Skip summary table
                    if (!empty($tableData['isSummary']) || !$tableId) {
                        continue;
                    }
                    
                    // Find all grade keys that start with this table ID
                    $tableScores = [];
                    foreach ($studentGrades as $key => $value) {
                        if (strpos($key, $tableId . '-') === 0) {
                            $tableScores[] = floatval($value);
                        }
                    }
                    
                    // Calculate average if we have scores (these are percentages 0-100)
                    if (!empty($tableScores)) {
                        $averagePercentage = array_sum($tableScores) / count($tableScores);
                        
                        // Store the percentage score directly (not weighted)
                        $midtermComponents[$tableId] = $averagePercentage;
                    }
                }
            }
            
            // If gradebook grades are empty, try to auto-populate from classwork submissions
            if (empty($midtermComponents)) {
                // Get all classwork IDs from the midterm tables
                $classworkIds = [];
                foreach ($midtermTables as $table) {
                    $tableData = is_array($table) ? $table : (array)$table;
                    if (!empty($tableData['isSummary']) || empty($tableData['columns'])) {
                        continue;
                    }
                    
                    foreach ($tableData['columns'] as $column) {
                        $columnData = is_array($column) ? $column : (array)$column;
                        if (!empty($columnData['subcolumns'])) {
                            foreach ($columnData['subcolumns'] as $subcolumn) {
                                $subcolData = is_array($subcolumn) ? $subcolumn : (array)$subcolumn;
                                if (!empty($subcolData['classworkId'])) {
                                    $classworkIds[$tableData['id']][] = [
                                        'classworkId' => $subcolData['classworkId'],
                                        'maxPoints' => $subcolData['maxPoints'] ?? 100
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Fetch submissions for these classworks
                if (!empty($classworkIds)) {
                    $allClassworkIds = [];
                    foreach ($classworkIds as $items) {
                        foreach ($items as $item) {
                            $allClassworkIds[] = $item['classworkId'];
                        }
                    }
                    
                    $submissions = \App\Models\ClassworkSubmission::whereIn('classwork_id', $allClassworkIds)
                        ->where('student_id', $studentId)
                        ->where('status', 'graded')
                        ->get(['classwork_id', 'grade']);
                    
                    // Build a map of classwork_id => grade
                    $submissionGrades = [];
                    foreach ($submissions as $submission) {
                        $submissionGrades[$submission->classwork_id] = floatval($submission->grade);
                    }
                    
                    // Calculate component scores based on submissions
                    foreach ($classworkIds as $tableId => $classworks) {
                        $totalPoints = 0;
                        $earnedPoints = 0;
                        $hasSubmissions = false;
                        
                        foreach ($classworks as $classwork) {
                            $totalPoints += $classwork['maxPoints'];
                            if (isset($submissionGrades[$classwork['classworkId']])) {
                                $earnedPoints += $submissionGrades[$classwork['classworkId']];
                                $hasSubmissions = true;
                            }
                        }
                        
                        // Calculate percentage score (0-100%)
                        if ($hasSubmissions && $totalPoints > 0) {
                            // Calculate raw percentage: (earned/total) * 100
                            // Example: (1/1) * 100 = 100%
                            $midtermComponents[$tableId] = ($earnedPoints / $totalPoints) * 100;
                        }
                    }
                }
            }

            // Get finals grade (percentage from gradebook)
            if (isset($gradebook['finals']['periodGrades'][$studentId])) {
                $finalsGrade = $gradebook['finals']['periodGrades'][$studentId];
            }

            // Get finals tables
            if (isset($gradebook['finals']['tables'])) {
                $finalsTables = $gradebook['finals']['tables'];
            }
            
            // Extract finals component scores by aggregating individual scores for each table
            if (isset($gradebook['finals']['grades'][$studentId])) {
                $studentGrades = $gradebook['finals']['grades'][$studentId];
                
                // For each main table (async, sync, exam), aggregate all scores that start with that table ID
                foreach ($finalsTables as $table) {
                    $tableData = is_array($table) ? $table : (array)$table;
                    $tableId = $tableData['id'] ?? null;
                    
                    // Skip summary table
                    if (!empty($tableData['isSummary']) || !$tableId) {
                        continue;
                    }
                    
                    // Find all grade keys that start with this table ID
                    $tableScores = [];
                    foreach ($studentGrades as $key => $value) {
                        if (strpos($key, $tableId . '-') === 0) {
                            $tableScores[] = floatval($value);
                        }
                    }
                    
                    // Calculate average if we have scores (these are percentages 0-100)
                    if (!empty($tableScores)) {
                        $averagePercentage = array_sum($tableScores) / count($tableScores);
                        
                        // Store the percentage score directly (not weighted)
                        $finalsComponents[$tableId] = $averagePercentage;
                    }
                }
            }
            
            // If gradebook grades are empty, try to auto-populate from classwork submissions
            if (empty($finalsComponents)) {
                // Get all classwork IDs from the finals tables
                $classworkIds = [];
                foreach ($finalsTables as $table) {
                    $tableData = is_array($table) ? $table : (array)$table;
                    if (!empty($tableData['isSummary']) || empty($tableData['columns'])) {
                        continue;
                    }
                    
                    foreach ($tableData['columns'] as $column) {
                        $columnData = is_array($column) ? $column : (array)$column;
                        if (!empty($columnData['subcolumns'])) {
                            foreach ($columnData['subcolumns'] as $subcolumn) {
                                $subcolData = is_array($subcolumn) ? $subcolumn : (array)$subcolumn;
                                if (!empty($subcolData['classworkId'])) {
                                    $classworkIds[$tableData['id']][] = [
                                        'classworkId' => $subcolData['classworkId'],
                                        'maxPoints' => $subcolData['maxPoints'] ?? 100
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Fetch submissions for these classworks
                if (!empty($classworkIds)) {
                    $allClassworkIds = [];
                    foreach ($classworkIds as $items) {
                        foreach ($items as $item) {
                            $allClassworkIds[] = $item['classworkId'];
                        }
                    }
                    
                    $submissions = \App\Models\ClassworkSubmission::whereIn('classwork_id', $allClassworkIds)
                        ->where('student_id', $studentId)
                        ->where('status', 'graded')
                        ->get(['classwork_id', 'grade']);
                    
                    // Build a map of classwork_id => grade
                    $submissionGrades = [];
                    foreach ($submissions as $submission) {
                        $submissionGrades[$submission->classwork_id] = floatval($submission->grade);
                    }
                    
                    // Calculate component scores based on submissions
                    foreach ($classworkIds as $tableId => $classworks) {
                        $totalPoints = 0;
                        $earnedPoints = 0;
                        $hasSubmissions = false;
                        
                        foreach ($classworks as $classwork) {
                            $totalPoints += $classwork['maxPoints'];
                            if (isset($submissionGrades[$classwork['classworkId']])) {
                                $earnedPoints += $submissionGrades[$classwork['classworkId']];
                                $hasSubmissions = true;
                            }
                        }
                        
                        // Calculate percentage score (0-100%)
                        if ($hasSubmissions && $totalPoints > 0) {
                            // Calculate raw percentage: (earned/total) * 100
                            $finalsComponents[$tableId] = ($earnedPoints / $totalPoints) * 100;
                        }
                    }
                }
            }
        }

        // Convert percentage grades to Philippine grading scale (1.0-5.0)
        $convertToGradingScale = function($percentGrade) {
            if (!$percentGrade || $percentGrade === 0) return null;
            
            $grade = floatval($percentGrade);
            
            if ($grade >= 100) return 1.0;
            if ($grade >= 99) return 1.15;
            if ($grade >= 98) return 1.2;
            if ($grade >= 97) return 1.25;
            if ($grade >= 96) return 1.3;
            if ($grade >= 95) return 1.35;
            if ($grade >= 94) return 1.4;
            if ($grade >= 93) return 1.45;
            if ($grade >= 92) return 1.5;
            if ($grade >= 91) return 1.55;
            if ($grade >= 90) return 1.6;
            if ($grade >= 89) return 1.65;
            if ($grade >= 88) return 1.7;
            if ($grade >= 87) return 1.75;
            if ($grade >= 86) return 1.8;
            if ($grade >= 85) return 1.85;
            if ($grade >= 84) return 1.9;
            if ($grade >= 83) return 1.95;
            if ($grade >= 82) return 2.0;
            if ($grade >= 81) return 2.05;
            if ($grade >= 80) return 2.1;
            if ($grade >= 79) return 2.15;
            if ($grade >= 78) return 2.2;
            if ($grade >= 77) return 2.25;
            if ($grade >= 76) return 2.3;
            if ($grade >= 75) return 2.35;
            if ($grade >= 74) return 2.4;
            if ($grade >= 73) return 2.45;
            if ($grade >= 72) return 2.5;
            if ($grade >= 71) return 2.55;
            if ($grade >= 70) return 2.6;
            if ($grade >= 69) return 2.65;
            if ($grade >= 68) return 2.7;
            if ($grade >= 67) return 2.75;
            if ($grade >= 66) return 2.8;
            if ($grade >= 65) return 2.85;
            if ($grade >= 64) return 2.9;
            if ($grade >= 63) return 2.95;
            if ($grade >= 62) return 3.0;
            
            return 5.0; // Below 62% = Failed
        };

        // Format component scores with clean names
        $formatComponents = function($components, $tables) use ($studentId) {
            $formatted = [];
            
            // If no tables defined or components, return empty
            if (empty($tables)) {
                return $formatted;
            }
            
            // Map table IDs to their clean names and display the scores
            foreach ($tables as $table) {
                $tableId = is_array($table) ? ($table['id'] ?? null) : ($table->id ?? null);
                $tableName = is_array($table) ? ($table['name'] ?? 'Unknown') : ($table->name ?? 'Unknown');
                $isSummary = is_array($table) ? ($table['isSummary'] ?? false) : ($table->isSummary ?? false);
                
                // Skip the summary table itself
                if ($isSummary) {
                    continue;
                }
                
                // Look for component with this table ID
                if (isset($components[$tableId])) {
                    $value = $components[$tableId];
                    if ($value === null || $value === '' || $value === 'auto') {
                        $formatted[$tableName] = '—';
                    } else {
                        $formatted[$tableName] = number_format((float)$value, 2);
                    }
                } else {
                    $formatted[$tableName] = '—';
                }
            }
            
            return $formatted;
        };

        $midtermComponentsFormatted = $formatComponents($midtermComponents, $midtermTables);
        $finalsComponentsFormatted = $formatComponents($finalsComponents, $finalsTables);

        $courseGrades = [
            'overall_percentage' => $gradebook['overallGrade'] ?? '0.00',
            'total_earned' => $gradebook['totalEarned'] ?? 0,
            'total_points' => $gradebook['totalPoints'] ?? 0,
            'midterm' => $midtermGrade !== null ? number_format($convertToGradingScale($midtermGrade), 2) : '—',
            'midterm_components' => $midtermComponentsFormatted,
            'final' => $finalsGrade !== null ? number_format($convertToGradingScale($finalsGrade), 2) : '—',
            'finals_components' => $finalsComponentsFormatted,
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
                
                // Get all classwork for this course (excluding materials and lessons)
                $classwork = $course->classworks()
                    ->whereNotIn('type', ['material', 'lesson'])
                    ->where('status', '!=', 'draft')
                    ->get();
                
                // Get submissions from this student for this course
                $submissions = ClassworkSubmission::whereIn('classwork_id', $classwork->pluck('id'))
                    ->where('student_id', $student->id)
                    ->where('status', '!=', 'draft')
                    ->get();
                
                // Calculate progress
                $totalClasswork = $classwork->count();
                
                // Get unique classwork IDs for each status
                $submittedClassworkIds = $submissions->where('status', '!=', 'draft')->pluck('classwork_id')->unique();
                $gradedClassworkIds = $submissions->whereIn('status', ['graded', 'returned'])->pluck('classwork_id')->unique();
                $pendingClassworkIds = $submissions->where('status', 'submitted')->pluck('classwork_id')->unique();
                
                // Count unique classwork for each category
                $submittedCount = $submittedClassworkIds->count();
                $gradedCount = $gradedClassworkIds->count();
                $pendingCount = $pendingClassworkIds->count(); // Unique pending classwork, not total submissions
                $notSubmittedCount = max(0, $totalClasswork - $submittedCount);
                
                // Calculate average grade from graded submissions only
                $gradedSubmissions = $submissions->whereIn('status', ['graded', 'returned'])->where('grade', '!=', null);
                $averageGrade = $gradedSubmissions->count() > 0 
                    ? round($gradedSubmissions->avg('grade'), 2)
                    : null;
                
                // Calculate completion rate based on graded work (completed work)
                $completionRate = $totalClasswork > 0 ? round(($gradedCount / $totalClasswork) * 100, 2) : 0;
                
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
                        'completion_rate' => min(100, $completionRate),
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
            'gradeAccess' => [
                'granted' => $gradeAccessGranted,
                'requested' => $gradeAccessRequested,
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

    public function requestGradeAccess($courseId)
    {
        $course = Course::findOrFail($courseId);
        
        $joinedCourse = JoinedCourse::where('user_id', auth()->id())
            ->where('course_id', $courseId)
            ->where('role', 'Student')
            ->firstOrFail();

        // Update to request access
        $joinedCourse->update([
            'grade_access_requested' => true,
            'grade_access_requested_at' => now(),
        ]);

        // Notify the teacher
        $teacher = $course->teacher;
        if ($teacher) {
            $teacher->notify(new \App\Notifications\GradeAccessRequestNotification(
                auth()->user(),
                $course
            ));
        }

        return back()->with('success', 'Grade access request sent to your teacher.');
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
        
        // When resubmitting, replace files instead of adding to them
        // Only keep existing files if no new files are uploaded
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
                
                // Replace with new files (not add to existing)
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
        } else if ($existingSubmission && $existingSubmission->attachments) {
            // Keep existing files only if no new files uploaded
            $uploadedFiles = $existingSubmission->attachments;
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
        $isNewSubmission = !$existingSubmission; // Track if this is a new submission
        
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

        // Only notify teacher on new submissions, not updates
        if ($isNewSubmission) {
            NotificationService::notifyTeacherAboutSubmission($classwork, auth()->user());
        }

        // Prepare success message
        $actionWord = $isNewSubmission ? 'submitted' : 'updated';
        $successMessage = "Work {$actionWord} successfully!";
        if ($autoGrade !== null) {
            $successMessage = "Quiz {$actionWord} and auto-graded! Your score: {$autoGrade}/{$totalPoints} points.";
        } elseif ($needsManualGrading) {
            $successMessage = "Quiz {$actionWord}! Your teacher will grade the essay questions.";
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
