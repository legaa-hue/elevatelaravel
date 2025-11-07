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
        $studentsWithGrades = $students->map(function ($student) use ($gradebook) {
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
        $studentsWithGrades = $students->map(function ($student) use ($gradebook) {
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

        // Get enrolled students with their grades (same logic as downloadPdf)
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

    private function getLetterGrade($grade)
    {
        if ($grade >= 97) return '1.0';
        if ($grade >= 94) return '1.25';
        if ($grade >= 91) return '1.5';
        if ($grade >= 88) return '1.75';
        if ($grade >= 85) return '2.0';
        if ($grade >= 82) return '2.25';
        if ($grade >= 79) return '2.5';
        if ($grade >= 76) return '2.75';
        if ($grade >= 75) return '3.0';
        if ($grade >= 70) return '4.0';
        if ($grade >= 65) return '5.0';
        return 'F';
    }

    private function getRemarks($grade)
    {
        if ($grade >= 75) return 'Passed';
        if ($grade >= 65) return 'Conditional';
        if ($grade > 0) return 'Failed';
        return 'Incomplete';
    }
}
