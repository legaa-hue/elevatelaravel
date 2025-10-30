<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch courses where the user is enrolled as Student
        $courses = Course::with(['teacher', 'academicYear'])
            ->whereHas('students', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->orderByDesc('id')
            ->get();

        $result = $courses->map(function (Course $course) use ($user) {
            $gradebook = $course->gradebook ?? [];

            // Normalize course status
            $statusRaw = strtolower((string)($course->status ?? 'pending'));
            $status = match (true) {
                str_contains($statusRaw, 'complete') => 'completed',
                str_contains($statusRaw, 'progress') => 'in_progress',
                default => 'pending',
            };

            // Build rows for table (per column per period)
            $rows = [];
            $periodPercents = [
                'midterm' => null,
                'finals' => null,
            ];

            foreach (['midterm', 'finals'] as $periodKey) {
                if (!isset($gradebook[$periodKey])) {
                    continue;
                }
                $period = $gradebook[$periodKey];
                $tables = $period['tables'] ?? [];
                $gradesByStudent = $period['grades'] ?? [];
                $studentGrades = $gradesByStudent[$user->id] ?? [];

                // Compute period percent for this student
                $periodPercents[$periodKey] = $this->calculatePeriodGradeFromData($studentGrades, $tables);

                foreach ($tables as $table) {
                    if (!empty($table['isSummary'])) {
                        continue;
                    }
                    $category = $table['category'] ?? $this->guessCategory(($table['title'] ?? '') . ' ' . ($table['name'] ?? ''));
                    $columns = $table['columns'] ?? [];
                    foreach ($columns as $col) {
                        $colTitle = $col['title'] ?? $col['name'] ?? ('Column ' . ($col['id'] ?? '?'));
                        $colId = $col['id'] ?? null;
                        $totalItems = 0;
                        $score = 0.0;
                        foreach (($col['subcolumns'] ?? []) as $sub) {
                            $subId = $sub['id'] ?? null;
                            $max = (float)($sub['total'] ?? $sub['max'] ?? 0);
                            $totalItems += is_numeric($max) ? (int)$max : 0;
                            if ($colId && $subId) {
                                $key = ($table['id'] ?? '') . '-' . $colId . '-' . $subId;
                                $val = $studentGrades[$key] ?? 0;
                                $score += is_numeric($val) ? (float)$val : 0.0;
                            }
                        }
                        $percent = $totalItems > 0 ? round(($score / max($totalItems, 1)) * 100, 2) : null;
                        $rows[] = [
                            'period' => ucfirst($periodKey),
                            'category' => $category,
                            'column' => $colTitle,
                            'total' => $totalItems > 0 ? $totalItems : null,
                            'score' => $score,
                            'percent' => $percent,
                        ];
                    }
                }
            }

            // Weighted average across periods (50/50 unless one is missing)
            $weightedAvg = null;
            if (is_numeric($periodPercents['midterm']) && is_numeric($periodPercents['finals'])) {
                $weightedAvg = round(((float)$periodPercents['midterm'] + (float)$periodPercents['finals']) / 2, 2);
            } elseif (is_numeric($periodPercents['midterm'])) {
                $weightedAvg = round((float)$periodPercents['midterm'], 2);
            } elseif (is_numeric($periodPercents['finals'])) {
                $weightedAvg = round((float)$periodPercents['finals'], 2);
            }

            // Remarks only if course is completed and weighted has value
            $remark = null;
            if ($status === 'completed' && $weightedAvg !== null) {
                $remark = $weightedAvg >= 75 ? 'passed' : 'retake';
            }

            return [
                'id' => $course->id,
                'title' => $course->title,
                'section' => $course->section,
                'status' => $status,
                'instructor' => [
                    'first_name' => $course->teacher->first_name ?? 'N/A',
                    'last_name' => $course->teacher->last_name ?? '',
                ],
                'semester' => $course->academicYear->year_name ?? null,
                'periodPercents' => [
                    'midterm' => $periodPercents['midterm'],
                    'finals' => $periodPercents['finals'],
                ],
                'weightedAverage' => $status === 'completed' ? $weightedAvg : null,
                'remark' => $remark, // 'passed' | 'retake' | null
                'rows' => $rows,
            ];
        });

        // Build filter options from data
        $periods = ['All', 'Midterm', 'Finals'];
        $categories = ['All'];
        $columns = ['All'];
        $statuses = ['All', 'Completed', 'In Progress', 'Pending'];

        foreach ($result as $c) {
            foreach ($c['rows'] as $r) {
                if (!in_array($r['category'], $categories, true) && !empty($r['category'])) {
                    $categories[] = $r['category'];
                }
                if (!in_array($r['column'], $columns, true) && !empty($r['column'])) {
                    $columns[] = $r['column'];
                }
            }
        }

        return Inertia::render('Student/Progress', [
            'filters' => [
                'period' => 'All',
                'category' => 'All',
                'column' => 'All',
                'status' => 'All',
            ],
            'filterOptions' => [
                'periods' => $periods,
                'categories' => $categories,
                'columns' => $columns,
                'statuses' => $statuses,
            ],
            'courses' => $result,
        ]);
    }

    private function calculatePeriodGradeFromData(array $studentGrades, array $tables): float
    {
        $total = 0.0;

        foreach ($tables as $table) {
            if (!empty($table['isSummary'])) {
                continue;
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

    private function guessCategory(string $label): string
    {
        $l = strtolower($label);
        return match (true) {
            str_contains($l, 'async') => 'Asynchronous',
            str_contains($l, 'sync') => 'Synchronous',
            str_contains($l, 'exam') => 'Exam',
            str_contains($l, 'quiz') => 'Asynchronous',
            str_contains($l, 'project') => 'Synchronous',
            str_contains($l, 'lab') => 'Synchronous',
            str_contains($l, 'attendance') => 'Synchronous',
            default => 'Asynchronous',
        };
    }
}
