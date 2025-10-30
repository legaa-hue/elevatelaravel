<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class GradeSheetController extends Controller
{
    // View the PDF in an iframe
    public function viewPdf(Course $course)
    {
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();
        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Get enrolled students with their grades
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->selectRaw("users.first_name || ' ' || users.last_name as name")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        $gradebook = $course->gradebook ?? null;

        $studentsWithGrades = $students->map(function ($student) use ($gradebook) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            if ($gradebook) {
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                if (isset($gradebook['midterm']['grades'][$student->id])) {
                    $midtermGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['midterm'],
                        'midterm'
                    );
                }
                if (isset($gradebook['finals']['grades'][$student->id])) {
                    $finalsGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['finals'],
                        'finals'
                    );
                }
                $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) +
                              ($finalsGrade * ($finalsPercentage / 100));
                $student->midterm_grade = round($midtermGrade, 2);
                $student->finals_grade = round($finalsGrade, 2);
                $student->final_grade = round($finalGrade, 2);
                $student->letter_grade = $this->getLetterGrade($finalGrade);
                $student->remarks = $this->getRemarks($finalGrade);
            } else {
                $student->midterm_grade = 0;
                $student->finals_grade = 0;
                $student->final_grade = 0;
                $student->letter_grade = 'INC';
                $student->remarks = 'Incomplete';
            }
            return $student;
        });

        $course->load(['teacher', 'academicYear', 'program']);

        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
        ]);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('GradeSheet_' . str_replace(' ', '_', $course->title) . '.pdf');
    }

    // Download the PDF
    public function downloadPdf(Course $course)
    {
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();
        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Get enrolled students with their grades
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->selectRaw("users.first_name || ' ' || users.last_name as name")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        $gradebook = $course->gradebook ?? null;

        $studentsWithGrades = $students->map(function ($student) use ($gradebook) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            if ($gradebook) {
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                if (isset($gradebook['midterm']['grades'][$student->id])) {
                    $midtermGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['midterm'],
                        'midterm'
                    );
                }
                if (isset($gradebook['finals']['grades'][$student->id])) {
                    $finalsGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['finals'],
                        'finals'
                    );
                }
                $finalGrade = ($midtermGrade * ($midtermPercentage / 100)) +
                              ($finalsGrade * ($finalsPercentage / 100));
                $student->midterm_grade = round($midtermGrade, 2);
                $student->finals_grade = round($finalsGrade, 2);
                $student->final_grade = round($finalGrade, 2);
                $student->letter_grade = $this->getLetterGrade($finalGrade);
                $student->remarks = $this->getRemarks($finalGrade);
            } else {
                $student->midterm_grade = 0;
                $student->finals_grade = 0;
                $student->final_grade = 0;
                $student->letter_grade = 'INC';
                $student->remarks = 'Incomplete';
            }
            return $student;
        });

        $course->load(['teacher', 'academicYear', 'program']);

        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
        ]);
        $pdf->setPaper('a4', 'landscape');
        $filename = 'GradeSheet_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
    // Helper methods for grade calculation and remarks (copied from Admin controller if needed)
    private function calculatePeriodGrade($studentId, $periodData, $period)
    {
        if (!isset($periodData['grades'][$studentId]) || !isset($periodData['tables'])) {
            return 0;
        }
        $grades = $periodData['grades'][$studentId];
        $tables = $periodData['tables'];
        $total = 0;
        
        foreach ($tables as $table) {
            // Skip summary tables
            if (!empty($table['isSummary'])) {
                continue;
            }
            
            $tableWeight = floatval($table['percentage'] ?? 0);
            if ($tableWeight == 0) {
                continue;
            }
            
            $tableTotal = 0;
            $columnWeightSum = 0;
            
            // Calculate total column percentage
            foreach (($table['columns'] ?? []) as $col) {
                if (!empty($col['subcolumns'])) {
                    $columnWeightSum += floatval($col['percentage'] ?? 0);
                }
            }
            
            if ($columnWeightSum > 0) {
                foreach (($table['columns'] ?? []) as $col) {
                    if (empty($col['subcolumns'])) {
                        continue;
                    }
                    
                    $colId = $col['id'];
                    $colWeight = floatval($col['percentage'] ?? 0);
                    $colScore = 0;
                    $colMax = 0;
                    
                    // Calculate column score and max points
                    foreach ($col['subcolumns'] as $subcol) {
                        $subcolId = $subcol['id'];
                        $gradeKey = $table['id'] . '-' . $colId . '-' . $subcolId;
                        $maxPoints = floatval($subcol['maxPoints'] ?? 100);
                        
                        $colScore += isset($grades[$gradeKey]) ? floatval($grades[$gradeKey]) : 0;
                        $colMax += $maxPoints;
                    }
                    
                    // Calculate percentage score for this column
                    if ($colMax > 0) {
                        $colPercentScore = $colScore / $colMax;
                        // Normalized column weight
                        $colNormalizedWeight = $colWeight / $columnWeightSum;
                        $tableTotal += $colPercentScore * $colNormalizedWeight;
                    }
                }
            }
            
            // Apply table weight
            $total += $tableTotal * ($tableWeight / 100) * 100;
        }
        
        return round($total, 2);
    }

    private function getLetterGrade($finalGrade)
    {
        if ($finalGrade >= 97) return 'A+';
        if ($finalGrade >= 93) return 'A';
        if ($finalGrade >= 90) return 'A-';
        if ($finalGrade >= 87) return 'B+';
        if ($finalGrade >= 83) return 'B';
        if ($finalGrade >= 80) return 'B-';
        if ($finalGrade >= 77) return 'C+';
        if ($finalGrade >= 73) return 'C';
        if ($finalGrade >= 70) return 'C-';
        if ($finalGrade >= 67) return 'D+';
        if ($finalGrade >= 63) return 'D';
        if ($finalGrade >= 60) return 'D-';
        return 'F';
    }

    private function getRemarks($finalGrade)
    {
        return $finalGrade >= 75 ? 'Passed' : 'Failed';
    }
}
