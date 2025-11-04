<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Courses owned by teacher
        $courses = Course::where('teacher_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'section']);

        return Inertia::render('Teacher/Reports', [
            'courses' => $courses,
        ]);
    }

    public function data(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $course = Course::with(['students' => function ($q) {
            $q->orderBy('last_name');
        }])->findOrFail($request->course_id);

        // Authorization: owner or joined teacher
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        if (!$isOwner && !$isJoined) abort(403);

        // Compute grades from gradebook JSON if available
        $gradebook = null;
        if ($course->gradebook) {
            if (is_string($course->gradebook)) {
                $gradebook = json_decode($course->gradebook, true);
            } elseif (is_array($course->gradebook)) {
                $gradebook = $course->gradebook;
            } else {
                $gradebook = json_decode(json_encode($course->gradebook), true);
            }
        }

        $midtermPct = (float)($gradebook['midtermPercentage'] ?? 50);
        $finalsPct = (float)($gradebook['finalsPercentage'] ?? (100 - $midtermPct));

        // Debug logging
        \Log::info('Report Gradebook Structure', [
            'course_id' => $course->id,
            'has_gradebook' => !empty($gradebook),
            'gradebook_keys' => $gradebook ? array_keys($gradebook) : [],
            'midterm_exists' => isset($gradebook['midterm']),
            'finals_exists' => isset($gradebook['finals']),
            'midterm_pct' => $midtermPct,
            'finals_pct' => $finalsPct,
        ]);

        $resultStudents = [];

        foreach ($course->students as $student) {
            // Calculate midterm grade
            $midtermGrade = $this->calculatePeriodGrade($gradebook, 'midterm', $student->id);
            
            // Calculate finals grade  
            $finalsGrade = $this->calculatePeriodGrade($gradebook, 'finals', $student->id);
            
            // Calculate final weighted grade
            $finalGrade = ($midtermGrade * $midtermPct / 100) + ($finalsGrade * $finalsPct / 100);
            
            // Convert to grade point (1.0 - 5.0 scale)
            $gradePoint = $this->percentToGradePoint($finalGrade);
            
            // Debug log for first student
            if ($student->id === $course->students->first()->id) {
                \Log::info('First Student Calculation', [
                    'student_id' => $student->id,
                    'student_name' => $student->last_name.', '.$student->first_name,
                    'midterm_grade' => $midtermGrade,
                    'finals_grade' => $finalsGrade,
                    'final_grade' => $finalGrade,
                    'grade_point' => $gradePoint,
                ]);
            }
            
            // Determine if passed (1.75 or lower for Masteral, 1.45 for Doctorate)
            $passed = $gradePoint <= 1.75;

            $resultStudents[] = [
                'id' => $student->id,
                'name' => $student->last_name.', '.$student->first_name,
                'email' => $student->email,
                'midtermGrade' => round($midtermGrade, 2),
                'finalsGrade' => round($finalsGrade, 2),
                'weightedAvg' => round($finalGrade, 2),
                'grade' => number_format($gradePoint, 2),
                'remarks' => $passed ? 'Passed' : 'Failed',
            ];
        }

        $totalStudents = count($resultStudents);
        $avgGrade = $totalStudents > 0 ? round(collect($resultStudents)->avg('weightedAvg'), 2) : 0;
        $passedCount = collect($resultStudents)->where('remarks', 'Passed')->count();
        $failedCount = $totalStudents - $passedCount;
        $passedPct = $totalStudents ? round(($passedCount / $totalStudents) * 100, 1) : 0;
        $failedPct = $totalStudents ? round(($failedCount / $totalStudents) * 100, 1) : 0;

        // Grade distribution bins (using grade points, not percentages)
        $bins = [
            '1.00-1.50' => 0,  // Excellent
            '1.75-2.50' => 0,  // Good
            '2.75-3.00' => 0,  // Fair
            'Above 3.00' => 0, // Failed
        ];
        foreach ($resultStudents as $s) {
            $gp = (float)$s['grade'];
            if ($gp <= 1.50) $bins['1.00-1.50']++;
            elseif ($gp <= 2.50) $bins['1.75-2.50']++;
            elseif ($gp <= 3.00) $bins['2.75-3.00']++;
            else $bins['Above 3.00']++;
        }
        $dist = [];
        foreach ($bins as $label => $count) {
            $pct = $totalStudents ? round(($count / $totalStudents) * 100) : 0;
            $dist[] = ['range' => $label, 'count' => $count, 'percent' => $pct];
        }

        return response()->json([
            'overview' => [
                'totalStudents' => $totalStudents,
                'averageGrade' => $avgGrade,
                'passed' => ['count' => $passedCount, 'percent' => $passedPct],
                'failed' => ['count' => $failedCount, 'percent' => $failedPct],
                'trend' => $passedPct >= 70 ? '↗ Improving' : ($passedPct >= 50 ? '→ Stable' : '↘ Needs Attention'),
            ],
            'distribution' => $dist,
            'students' => $resultStudents,
            'insights' => [
                'summary' => [
                    'passRate' => $passedPct,
                    'avgWeighted' => $avgGrade,
                    'weakCategory' => null,
                ]
            ],
            'course' => ['id' => $course->id, 'title' => $course->title],
        ]);
    }

    /**
     * Calculate period grade for a student (midterm or finals)
     */
    private function calculatePeriodGrade($gradebook, $period, $studentId)
    {
        if (!isset($gradebook[$period]['tables'])) {
            return 0;
        }

        $tables = $gradebook[$period]['tables'];
        $studentGrades = $gradebook[$period]['grades'][$studentId] ?? [];
        $autoGrades = $gradebook[$period]['autoGrades'][$studentId] ?? [];
        
        $totalScore = 0;

        foreach ($tables as $tableKey => $table) {
            // Skip summary table
            if (!empty($table['isSummary']) || $tableKey === 'summary') {
                continue;
            }

            $tablePercentage = (float)($table['percentage'] ?? 0);
            if ($tablePercentage == 0) continue;

            $tableScore = 0;

            if (!empty($table['columns'])) {
                foreach ($table['columns'] as $column) {
                    $columnPercentage = (float)($column['percentage'] ?? 0);
                    if ($columnPercentage == 0) continue;
                    
                    if (!empty($column['subcolumns'])) {
                        $columnScore = 0;
                        $columnMaxPoints = 0;

                        foreach ($column['subcolumns'] as $subcolumn) {
                            $gradeKey = $tableKey . '-' . $column['id'] . '-' . $subcolumn['id'];
                            $maxPoints = (float)($subcolumn['maxPoints'] ?? 100);
                            
                            // Check if this is auto-populated (from classwork)
                            if (!empty($subcolumn['isAutoPopulated'])) {
                                $score = isset($autoGrades[$gradeKey]) ? (float)$autoGrades[$gradeKey] : 0;
                            } else {
                                $score = isset($studentGrades[$gradeKey]) ? (float)$studentGrades[$gradeKey] : 0;
                            }

                            $columnScore += $score;
                            $columnMaxPoints += $maxPoints;
                        }

                        // Calculate column contribution (percentage of column)
                        if ($columnMaxPoints > 0) {
                            $columnPercentScore = ($columnScore / $columnMaxPoints) * $columnPercentage;
                            $tableScore += $columnPercentScore;
                        }
                    }
                }
            }

            // The tableScore is already a percentage out of tablePercentage
            // Add it directly to totalScore
            $totalScore += $tableScore;
        }

        return $totalScore;
    }

    /**
     * Convert percentage (0-100) to grade point (1.0-5.0)
     */
    private function percentToGradePoint($percent)
    {
        if ($percent >= 96) return 1.00;
        if ($percent >= 93) return 1.25;
        if ($percent >= 90) return 1.50;
        if ($percent >= 87) return 1.75;
        if ($percent >= 84) return 2.00;
        if ($percent >= 81) return 2.25;
        if ($percent >= 78) return 2.50;
        if ($percent >= 75) return 2.75;
        if ($percent >= 72) return 3.00;
        return 5.00; // Failed
    }

    public function notifyFeedback(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'message' => 'required|string',
        ]);
        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourse($course);
        NotificationService::notifyUser($request->student_id, 'feedback', 'Feedback', $request->message, [
            'course_id' => $course->id,
            'course_title' => $course->title,
            'teacher_name' => $course->teacher ? ($course->teacher->first_name . ' ' . $course->teacher->last_name) : null,
            'url' => "/student/courses/{$course->id}"
        ]);
        return response()->json(['status' => 'ok']);
    }

    public function notifyFailing(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'message' => 'nullable|string',
        ]);
        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourse($course);
        $msg = $request->message ?: 'You are currently below the passing grade. Please review your performance and reach out for assistance.';
        NotificationService::notifyUser($request->student_id, 'failing-alert', 'Academic Standing Alert', $msg, [
            'course_id' => $course->id,
            'url' => "/student/courses/{$course->id}"
        ]);
        return response()->json(['status' => 'ok']);
    }

    public function export(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'format' => 'required|in:csv,pdf',
        ]);
        // Reuse data() logic
        $dataResponse = $this->data(new Request($request->all()));
        $data = $dataResponse->getData(true);

    $format = $request->input('format');
    if ($format === 'csv') {
            $filename = 'teacher_report_'.$data['course']['title'].'_'.date('Y-m-d').'.csv';
            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Student Name', 'Weighted Avg (%)', 'Grade', 'Remarks']);
                foreach ($data['students'] as $s) {
                    fputcsv($out, [$s['name'], $s['weightedAvg'], $s['grade'], $s['remarks']]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        // PDF
        $pdf = Pdf::loadView('teacher.report_pdf', [
            'data' => $data
        ]);
        return $pdf->download('teacher_report_'.($data['course']['title']).'_'.date('Y-m-d').'.pdf');
    }

    private function authorizeCourse(Course $course)
    {
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        if (!$isOwner && !$isJoined) abort(403);
    }

    /**
     * Get remarks based on grade point (1.0-5.0 scale)
     */
    private function getRemarks($gradePoint, $passingGrade = 1.75)
    {
        if ($gradePoint <= $passingGrade) {
            return 'Passed';
        }
        if ($gradePoint <= 3.00) {
            return 'Conditional';
        }
        if ($gradePoint > 0) {
            return 'Failed';
        }
        return 'Incomplete';
    }

    /**
     * Determine passing grade based on program type
     */
    private function getPassingGrade(Course $course)
    {
        $programName = strtolower($course->program->name ?? '');
        // Doctorate programs require 1.45 or better
        if (str_contains($programName, 'doctor') || 
            str_contains($programName, 'phd') || 
            str_contains($programName, 'ph.d') ||
            str_contains($programName, 'dba') ||
            str_contains($programName, 'edd')) {
            return 1.45;
        }
        // Default to Masteral passing grade of 1.75
        return 1.75;
    }

    /**
     * Export Course Performance PDF
     */
    public function exportPerformancePdf(Course $course)
    {
        $this->authorizeCourse($course);

        // Get course data
        $course->load(['students' => function ($q) {
            $q->orderBy('last_name');
        }, 'teacher', 'academicYear', 'program']);

        $passingGrade = $this->getPassingGrade($course);
        $gradebook = $course->gradebook ?? null;
        $midtermPercentage = 50;
        $finalsPercentage = 50;

        if ($gradebook) {
            $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
            $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
        }

        // Calculate student performance
        $students = $course->students->map(function ($student) use ($gradebook, $midtermPercentage, $finalsPercentage, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;

            if ($gradebook) {
                if (isset($gradebook['midterm'])) {
                    $midtermGrade = $this->calculatePeriodGrade($gradebook, 'midterm', $student->id);
                }
                if (isset($gradebook['finals'])) {
                    $finalsGrade = $this->calculatePeriodGrade($gradebook, 'finals', $student->id);
                }
            }

            $finalGrade = ($midtermGrade * $midtermPercentage / 100) + ($finalsGrade * $finalsPercentage / 100);
            $gradePoint = $this->percentToGradePoint($finalGrade);

            return [
                'name' => $student->last_name . ', ' . $student->first_name,
                'midterm' => round($midtermGrade, 2),
                'finals' => round($finalsGrade, 2),
                'final' => round($finalGrade, 2),
                'grade_point' => number_format($gradePoint, 2),
                'remarks' => $this->getRemarks($gradePoint, $passingGrade),
            ];
        });

        // Calculate distribution
        $distribution = [
            '1.00-1.50' => 0,
            '1.75-2.50' => 0,
            '2.75-3.00' => 0,
            'Above 3.00' => 0,
        ];

        foreach ($students as $student) {
            $gp = (float)$student['grade_point'];
            if ($gp >= 1.00 && $gp <= 1.50) {
                $distribution['1.00-1.50']++;
            } elseif ($gp >= 1.75 && $gp <= 2.50) {
                $distribution['1.75-2.50']++;
            } elseif ($gp >= 2.75 && $gp <= 3.00) {
                $distribution['2.75-3.00']++;
            } else {
                $distribution['Above 3.00']++;
            }
        }

        $pdf = Pdf::loadView('pdf.course-performance', [
            'course' => $course,
            'students' => $students,
            'distribution' => $distribution,
            'totalStudents' => $students->count(),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $filename = 'CoursePerformance_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export Class Standings PDF
     */
    public function exportStandingsPdf(Course $course)
    {
        $this->authorizeCourse($course);

        // Get course data
        $course->load(['students' => function ($q) {
            $q->orderBy('last_name');
        }, 'teacher', 'academicYear', 'program']);

        $passingGrade = $this->getPassingGrade($course);
        $gradebook = $course->gradebook ?? null;
        $midtermPercentage = 50;
        $finalsPercentage = 50;

        if ($gradebook) {
            $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
            $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
        }

        // Calculate student performance
        $students = $course->students->map(function ($student) use ($gradebook, $midtermPercentage, $finalsPercentage, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;

            if ($gradebook) {
                if (isset($gradebook['midterm'])) {
                    $midtermGrade = $this->calculatePeriodGrade($gradebook, 'midterm', $student->id);
                }
                if (isset($gradebook['finals'])) {
                    $finalsGrade = $this->calculatePeriodGrade($gradebook, 'finals', $student->id);
                }
            }

            $finalGrade = ($midtermGrade * $midtermPercentage / 100) + ($finalsGrade * $finalsPercentage / 100);
            $gradePoint = $this->percentToGradePoint($finalGrade);

            return [
                'name' => $student->last_name . ', ' . $student->first_name,
                'final_grade' => round($finalGrade, 2),
                'grade_point' => number_format($gradePoint, 2),
                'remarks' => $this->getRemarks($gradePoint, $passingGrade),
            ];
        })->sortBy('grade_point')->values();

        // Add rankings
        $rank = 1;
        foreach ($students as $index => $student) {
            $students[$index]['rank'] = $rank++;
        }

        $pdf = Pdf::loadView('pdf.class-standings', [
            'course' => $course,
            'students' => $students,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $filename = 'ClassStandings_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}

