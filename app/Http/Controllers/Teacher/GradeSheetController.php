<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class GradeSheetController extends Controller
{
    // View the PDF in an iframe
    public function viewPdf(Request $request, Course $course)
    {
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();
        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Get semester from request (default to "Second Semester" if not provided)
        $semester = $request->input('semester', 'Second Semester');

        // Load program relationship first
        $course->load('program');
        $passingGrade = $this->getPassingGrade($course);

        // Get enrolled students with their grades
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->selectRaw("users.first_name || ' ' || users.last_name as name")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        $gradebook = $course->gradebook ?? null;

        $studentsWithGrades = $students->map(function ($student) use ($gradebook, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            
            if ($gradebook) {
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                
                // Check if we have pre-calculated periodGrades (from gradebook UI)
                if (isset($gradebook['midterm']['periodGrades'][$student->id])) {
                    $midtermGrade = (float)$gradebook['midterm']['periodGrades'][$student->id];
                } else if (isset($gradebook['midterm'])) {
                    // Fallback to calculation
                    $midtermGrade = $this->calculatePeriodGrade($gradebook, 'midterm', $student->id);
                }
                
                if (isset($gradebook['finals']['periodGrades'][$student->id])) {
                    $finalsGrade = (float)$gradebook['finals']['periodGrades'][$student->id];
                } else if (isset($gradebook['finals'])) {
                    // Fallback to calculation
                    $finalsGrade = $this->calculatePeriodGrade($gradebook, 'finals', $student->id);
                }
                
                $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) +
                              ($finalsGrade * ($finalsPercentage / 100));
                $gradePoint = $this->percentToGradePoint($finalGrade);
                $student->midterm_grade = round($midtermGrade, 2);
                $student->finals_grade = round($finalsGrade, 2);
                $student->final_grade = round($finalGrade, 2);
                $student->grade_point = number_format($gradePoint, 2);
                $student->remarks = $this->getRemarks($gradePoint, $passingGrade);
            } else {
                $student->midterm_grade = 0;
                $student->finals_grade = 0;
                $student->final_grade = 0;
                $student->grade_point = '5.00';
                $student->remarks = 'Incomplete';
            }
            return $student;
        });

        $course->load(['teacher', 'academicYear', 'program']);

        // Get program name for display in Course column
        $programName = $course->program ? $course->program->name : 'N/A';

        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
            'semester' => $semester,
            'programName' => $programName,
        ]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('GradeSheet_' . str_replace(' ', '_', $course->title) . '.pdf');
    }

    // Download the PDF
    public function downloadPdf(Request $request, Course $course)
    {
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();
        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Get semester from request (default to "Second Semester" if not provided)
        $semester = $request->input('semester', 'Second Semester');

        // Load program relationship first
        $course->load('program');
        $passingGrade = $this->getPassingGrade($course);

        // Get enrolled students with their grades
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->selectRaw("users.first_name || ' ' || users.last_name as name")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        $gradebook = $course->gradebook ?? null;

        $studentsWithGrades = $students->map(function ($student) use ($gradebook, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            
            if ($gradebook) {
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                
                // Check if we have pre-calculated periodGrades (from gradebook UI)
                if (isset($gradebook['midterm']['periodGrades'][$student->id])) {
                    $midtermGrade = (float)$gradebook['midterm']['periodGrades'][$student->id];
                } else if (isset($gradebook['midterm'])) {
                    // Fallback to calculation
                    $midtermGrade = $this->calculatePeriodGrade($gradebook, 'midterm', $student->id);
                }
                
                if (isset($gradebook['finals']['periodGrades'][$student->id])) {
                    $finalsGrade = (float)$gradebook['finals']['periodGrades'][$student->id];
                } else if (isset($gradebook['finals'])) {
                    // Fallback to calculation
                    $finalsGrade = $this->calculatePeriodGrade($gradebook, 'finals', $student->id);
                }
                
                $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) +
                              ($finalsGrade * ($finalsPercentage / 100));
                $gradePoint = $this->percentToGradePoint($finalGrade);
                $student->midterm_grade = round($midtermGrade, 2);
                $student->finals_grade = round($finalsGrade, 2);
                $student->final_grade = round($finalGrade, 2);
                $student->grade_point = number_format($gradePoint, 2);
                $student->remarks = $this->getRemarks($gradePoint, $passingGrade);
            } else {
                $student->midterm_grade = 0;
                $student->finals_grade = 0;
                $student->final_grade = 0;
                $student->grade_point = '5.00';
                $student->remarks = 'Incomplete';
            }
            return $student;
        });

        $course->load(['teacher', 'academicYear', 'program']);

        // Get program name for display in Course column
        $programName = $course->program ? $course->program->name : 'N/A';

        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
            'semester' => $semester,
            'programName' => $programName,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $filename = 'GradeSheet_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
    // Helper methods for grade calculation and remarks
    private function calculatePeriodGrade($gradebook, $period, $studentId)
    {
        if (!isset($gradebook[$period]['tables'])) {
            return 0;
        }

        $periodData = $gradebook[$period];
        $tables = $periodData['tables'];
        $studentGrades = $periodData['grades'][$studentId] ?? [];
        $autoGrades = $periodData['autoGrades'][$studentId] ?? [];
        
        $totalScore = 0;

        foreach ($tables as $table) {
            // Skip summary table
            if (!empty($table['isSummary']) || (isset($table['id']) && $table['id'] === 'summary')) {
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
                            $tableId = $table['id'] ?? '';
                            $columnId = $column['id'] ?? '';
                            $subcolumnId = $subcolumn['id'] ?? '';
                            $gradeKey = $tableId . '-' . $columnId . '-' . $subcolumnId;
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

    private function percentToGradePoint($percentage)
    {
        // Convert percentage (0-100) to grade point (1.0-5.0)
        // 97-100% = 1.00, 0% = 5.00
        
        if ($percentage >= 97) return 1.00;
        if ($percentage >= 94) return 1.25;
        if ($percentage >= 91) return 1.50;
        if ($percentage >= 88) return 1.75;
        if ($percentage >= 85) return 2.00;
        if ($percentage >= 82) return 2.25;
        if ($percentage >= 79) return 2.50;
        if ($percentage >= 76) return 2.75;
        if ($percentage >= 75) return 3.00;
        if ($percentage >= 72) return 3.25;
        if ($percentage >= 69) return 3.50;
        if ($percentage >= 66) return 3.75;
        if ($percentage >= 63) return 4.00;
        if ($percentage >= 60) return 4.25;
        if ($percentage >= 57) return 4.50;
        if ($percentage >= 54) return 4.75;
        
        return 5.00; // Below 54% is failing
    }

    private function getRemarks($gradePoint, $passingGrade = 1.75)
    {
        // Use dynamic passing grade (1.75 for Masteral, 1.45 for Doctorate)
        return $gradePoint <= $passingGrade ? 'Passed' : 'Failed';
    }

    /**
     * Determine passing grade based on program type
     */
    private function getPassingGrade(Course $course)
    {
        $programName = strtolower($course->program->name ?? '');
        // Doctorate programs require 1.45 or better
        // Check for common doctorate indicators
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
}
