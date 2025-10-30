<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassRecordController extends Controller
{
    /**
     * Show list of courses for class records (owned and joined by current user)
     */
    public function index()
    {
        $user = auth()->user();

        // Courses the user owns (as primary teacher)
        $myCourses = Course::with(['teacher', 'program', 'academicYear'])
            ->withCount('students')
            ->where('teacher_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'section' => $c->section,
                    'status' => $c->status,
                    'students_count' => $c->students_count,
                    'teacher' => [
                        'first_name' => optional($c->teacher)->first_name,
                        'last_name' => optional($c->teacher)->last_name,
                    ],
                    'midterm_grade' => $this->computeMidtermGrade($c),
                ];
            });

        // Courses the user joined (as Teacher assistant or Student)
        $joinedCourses = Course::with(['teacher', 'program', 'academicYear'])
            ->withCount('students')
            ->whereHas('joinedCourses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'section' => $c->section,
                    'status' => $c->status,
                    'students_count' => $c->students_count,
                    'teacher' => [
                        'first_name' => optional($c->teacher)->first_name,
                        'last_name' => optional($c->teacher)->last_name,
                    ],
                    'midterm_grade' => $this->computeMidtermGrade($c),
                ];
            });

        return Inertia::render('Teacher/ClassRecord', [
            'myCourses' => $myCourses,
            'joinedCourses' => $joinedCourses,
        ]);
    }

    /**
     * Display class record for a specific course
     */
    public function show(Course $course)
    {
        // Check if teacher owns or is assigned to this course
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();

        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Load course with necessary relationships
        $course->load([
            'teacher',
            'academicYear',
            'program',
            'students' => function ($query) {
                $query->with('joinedCourses');
            }
        ]);

        return Inertia::render('Teacher/GradeSheet', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'section' => $course->section,
                'description' => $course->description,
                'join_code' => $course->join_code,
                'status' => $course->status,
                'teacher' => [
                    'id' => $course->teacher->id ?? null,
                    'first_name' => $course->teacher->first_name ?? 'N/A',
                    'last_name' => $course->teacher->last_name ?? '',
                ],
                'academic_year' => $course->academicYear ? [
                    'id' => $course->academicYear->id,
                    'name' => $course->academicYear->year_name,
                ] : null,
                'program' => $course->program ? [
                    'id' => $course->program->id,
                    'name' => $course->program->program_name,
                ] : null,
            ],
        ]);
    }

    /**
     * Compute the midterm grade for a course (average of all students)
     */
    private function computeMidtermGrade(Course $course): ?float
    {
        $gradebook = $course->gradebook;
        if (!$gradebook || !isset($gradebook['midterm'])) {
            return null;
        }
        $period = $gradebook['midterm'];
        $tables = $period['tables'] ?? [];
        $gradesByStudent = $period['grades'] ?? [];
        if (!is_array($gradesByStudent) || empty($gradesByStudent)) {
            return null;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($gradesByStudent as $studentId => $studentGrades) {
            if (!is_array($studentGrades)) {
                continue;
            }
            $sum += $this->calculatePeriodGradeFromData($studentGrades, $tables);
            $count++;
        }
        return $count > 0 ? round($sum / $count, 2) : null;
    }

    /**
     * Calculate a single student's period grade from gradebook tables/grades
     */
    private function calculatePeriodGradeFromData(array $studentGrades, array $tables): float
    {
        $total = 0.0;

        foreach ($tables as $table) {
            if (!empty($table['isSummary'])) {
                continue; // skip summary tables
            }
            
            $tableWeight = (float)($table['percentage'] ?? 0);
            if ($tableWeight == 0) {
                continue;
            }
            
            $columns = $table['columns'] ?? [];
            $columnWeightSum = 0.0;
            
            // Calculate total column percentage
            foreach ($columns as $col) {
                if (!empty($col['subcolumns'])) {
                    $columnWeightSum += (float)($col['percentage'] ?? 0);
                }
            }

            $tableTotal = 0.0;
            if ($columnWeightSum > 0) {
                foreach ($columns as $col) {
                    if (empty($col['subcolumns'])) {
                        continue;
                    }
                    
                    $colId = $col['id'] ?? null;
                    $colWeight = (float)($col['percentage'] ?? 0);
                    $colScore = 0.0;
                    $colMax = 0.0;
                    
                    // Calculate column score and max points
                    foreach ($col['subcolumns'] as $sub) {
                        $subId = $sub['id'] ?? null;
                        $maxPoints = (float)($sub['maxPoints'] ?? 100);
                        
                        if ($colId && $subId) {
                            $key = ($table['id'] ?? '') . '-' . $colId . '-' . $subId;
                            $val = $studentGrades[$key] ?? 0;
                            $colScore += is_numeric($val) ? (float)$val : 0.0;
                        }
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
}
