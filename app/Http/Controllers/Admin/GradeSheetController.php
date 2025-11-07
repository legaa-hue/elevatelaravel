<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class GradeSheetController extends Controller
{
    public function show(Course $course)
    {
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

        // Get gradebook data
        $gradebook = $course->gradebook ?? null;

        // Calculate final grades for each student
        $studentsWithGrades = $students->map(function ($student) use ($gradebook, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            
            if ($gradebook) {
                // Get midterm percentage (default 50%)
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                
                // Calculate midterm grade
                if (isset($gradebook['midterm']['grades'][$student->id])) {
                    $midtermGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['midterm'],
                        'midterm'
                    );
                }
                
                // Calculate finals grade
                if (isset($gradebook['finals']['grades'][$student->id])) {
                    $finalsGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['finals'],
                        'finals'
                    );
                }
                
                // Calculate final grade
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

        // Load course with relationships
        $course->load(['teacher', 'academicYear', 'program']);

        return Inertia::render('Admin/GradeSheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
            'gradebook' => $gradebook,
        ]);
    }

    public function downloadPdf(Request $request, Course $course)
    {
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

        // Get gradebook data
        $gradebook = $course->gradebook ?? null;

        // Calculate final grades for each student
        $studentsWithGrades = $students->map(function ($student) use ($gradebook, $passingGrade) {
            $midtermGrade = 0;
            $finalsGrade = 0;
            
            if ($gradebook) {
                // Get midterm percentage (default 50%)
                $midtermPercentage = $gradebook['midtermPercentage'] ?? 50;
                $finalsPercentage = $gradebook['finalsPercentage'] ?? 50;
                
                // Calculate midterm grade
                if (isset($gradebook['midterm']['grades'][$student->id])) {
                    $midtermGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['midterm'],
                        'midterm'
                    );
                }
                
                // Calculate finals grade
                if (isset($gradebook['finals']['grades'][$student->id])) {
                    $finalsGrade = $this->calculatePeriodGrade(
                        $student->id,
                        $gradebook['finals'],
                        'finals'
                    );
                }
                
                // Calculate final grade
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

        // Load course with relationships
        $course->load(['teacher', 'academicYear', 'program']);

        // Get program name for display in Course column
        $programName = $course->program ? $course->program->name : 'N/A';

        // Generate PDF
        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
            'semester' => $semester,
            'programName' => $programName,
        ]);

        // Set paper size to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Download the PDF
        $filename = 'GradeSheet_' . str_replace(' ', '_', $course->title) . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function viewPdf(Request $request, Course $course)
    {
        // Get semester from request (default to "Second Semester" if not provided)
        $semester = $request->input('semester', 'Second Semester');

        // Load program relationship first
        $course->load('program');
        $passingGrade = $this->getPassingGrade($course);

        // Get enrolled students with their grades (same logic as downloadPdf)
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

        // Generate PDF for viewing
        $pdf = Pdf::loadView('pdf.grade-sheet', [
            'course' => $course,
            'students' => $studentsWithGrades,
            'semester' => $semester,
            'programName' => $programName,
        ]);

        $pdf->setPaper('a4', 'landscape');

        // Stream the PDF (view in browser)
        return $pdf->stream('GradeSheet_' . str_replace(' ', '_', $course->title) . '.pdf');
    }

    private function calculatePeriodGrade($studentId, $periodData, $period)
    {
        if (!isset($periodData['grades'][$studentId]) || !isset($periodData['tables'])) {
            return 0;
        }

        $grades = $periodData['grades'][$studentId];
        $tables = $periodData['tables'];
        $total = 0;

        // Calculate from default tables
        if (isset($tables['tables'])) {
            $defaultTables = $tables['tables'];
            
            foreach (['asynchronous', 'synchronous', 'majorExam'] as $tableKey) {
                if (isset($defaultTables[$tableKey])) {
                    $total += $this->calculateTableTotal($studentId, $tableKey, $defaultTables[$tableKey], $grades);
                }
            }
        }

        // Calculate from custom tables
        if (isset($tables['custom'])) {
            foreach ($tables['custom'] as $customTable) {
                $total += $this->calculateCustomTableTotal($studentId, $customTable, $grades);
            }
        }

        return $total;
    }

    private function calculateTableTotal($studentId, $tableKey, $table, $grades)
    {
        $total = 0;
        
        if (!isset($table['columns'])) {
            return 0;
        }

        foreach ($table['columns'] as $column) {
            $total += $this->calculateColumnGrade($studentId, $tableKey, $column, $grades);
        }

        return $total;
    }

    private function calculateColumnGrade($studentId, $tableKey, $column, $grades)
    {
        $columnId = $column['id'];
        $key = "{$tableKey}-{$columnId}";

        // Handle major exam (direct score)
        if ($tableKey === 'majorExam') {
            $examKey = "{$key}-exam";
            $score = $grades[$examKey] ?? 0;
            $maxPoints = $column['maxPoints'] ?? 100;
            $rawPercentage = $maxPoints > 0 ? (floatval($score) / $maxPoints) * 100 : 0;
            return ($rawPercentage / 100) * ($column['percentage'] ?? 0);
        }

        // Handle columns with subcolumns
        if (!isset($column['subcolumns']) || count($column['subcolumns']) === 0) {
            return 0;
        }

        $totalScore = 0;
        $totalMax = 0;

        foreach ($column['subcolumns'] as $subcol) {
            $subKey = "{$key}-{$subcol['id']}";
            $score = $grades[$subKey] ?? 0;
            $totalScore += floatval($score);
            $totalMax += $subcol['maxPoints'] ?? 100;
        }

        $rawPercentage = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0;
        return ($rawPercentage / 100) * ($column['percentage'] ?? 0);
    }

    private function calculateCustomTableTotal($studentId, $customTable, $grades)
    {
        $total = 0;
        $tableId = $customTable['id'];

        if (!isset($customTable['columns'])) {
            return 0;
        }

        foreach ($customTable['columns'] as $column) {
            $columnId = $column['id'];
            $key = "custom-{$tableId}-{$columnId}";

            if (!isset($column['subcolumns']) || count($column['subcolumns']) === 0) {
                continue;
            }

            $totalScore = 0;
            $totalMax = 0;

            foreach ($column['subcolumns'] as $subcol) {
                $subKey = "{$key}-{$subcol['id']}";
                $score = $grades[$subKey] ?? 0;
                $totalScore += floatval($score);
                $totalMax += $subcol['maxPoints'] ?? 100;
            }

            $rawPercentage = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0;
            $weighted = ($rawPercentage / 100) * ($column['percentage'] ?? 0);
            $total += $weighted;
        }

        return $total;
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
