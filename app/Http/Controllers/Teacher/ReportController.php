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

        $resultStudents = [];

        $tablesByPeriod = [
            'midterm' => collect($gradebook['midterm']['tables'] ?? []),
            'finals'  => collect($gradebook['finals']['tables'] ?? []),
        ];
        $gradesByPeriodAndStudent = [
            'midterm' => $gradebook['midterm']['grades'] ?? [],
            'finals'  => $gradebook['finals']['grades'] ?? [],
        ];

        $tableAverages = ['midterm' => [], 'finals' => []];

        foreach ($course->students as $student) {
            $periodTotals = ['midterm' => 0.0, 'finals' => 0.0];

            foreach (['midterm', 'finals'] as $periodKey) {
                $tables = $tablesByPeriod[$periodKey];
                $studentGrades = $gradesByPeriodAndStudent[$periodKey][$student->id] ?? [];
                $periodTotal = 0.0;

                foreach ($tables as $table) {
                    if (!empty($table['isSummary'])) continue; // skip summary table
                    $tableTotal = 0.0;
                    if (!empty($table['columns'])) {
                        foreach ($table['columns'] as $column) {
                            $colSum = 0.0; $colMax = 0.0;
                            foreach (($column['subcolumns'] ?? []) as $sub) {
                                $gradeKey = $table['id'].'-'.$column['id'].'-'.$sub['id'];
                                $val = isset($studentGrades[$gradeKey]) ? (float)$studentGrades[$gradeKey] : 0.0;
                                $max = (float)($sub['maxPoints'] ?? 0);
                                $colSum += $val; $colMax += $max;
                            }
                            if ($colMax > 0) {
                                $columnPercent = ($colSum / $colMax) * ((float)($column['percentage'] ?? 0));
                                $tableTotal += $columnPercent;
                            }
                        }
                    }
                    $periodTotal += $tableTotal;

                    // Track table averages for insights
                    $tableAverages[$periodKey][$table['name']] = ($tableAverages[$periodKey][$table['name']] ?? 0) + $tableTotal;
                }
                $periodTotals[$periodKey] = $periodTotal;
            }

            $weighted = ($periodTotals['midterm'] * $midtermPct + $periodTotals['finals'] * $finalsPct) / 100.0;
            $percent = round($weighted, 2);
            $eqGrade = $this->percentToGrade($percent);
            $passed = $percent >= 75; // basic rule

            $resultStudents[] = [
                'id' => $student->id,
                'name' => $student->last_name.', '.$student->first_name,
                'email' => $student->email,
                'weightedAvg' => $percent,
                'grade' => $eqGrade,
                'remarks' => $passed ? 'Passed' : 'Failed',
            ];
        }

        $totalStudents = count($resultStudents);
        $avgGrade = $totalStudents > 0 ? round(collect($resultStudents)->avg('weightedAvg'), 2) : 0;
        $passedCount = collect($resultStudents)->where('remarks', 'Passed')->count();
        $failedCount = $totalStudents - $passedCount;
        $passedPct = $totalStudents ? round(($passedCount / $totalStudents) * 100, 1) : 0;
        $failedPct = $totalStudents ? round(($failedCount / $totalStudents) * 100, 1) : 0;

        // Grade distribution bins
        $bins = [
            '90-100' => 0,
            '80-89' => 0,
            '75-79' => 0,
            '<75' => 0,
        ];
        foreach ($resultStudents as $s) {
            $p = $s['weightedAvg'];
            if ($p >= 90) $bins['90-100']++;
            elseif ($p >= 80) $bins['80-89']++;
            elseif ($p >= 75) $bins['75-79']++;
            else $bins['<75']++;
        }
        $dist = [];
        foreach ($bins as $label => $count) {
            $pct = $totalStudents ? round(($count / $totalStudents) * 100) : 0;
            $dist[] = ['range' => $label, 'count' => $count, 'percent' => $pct];
        }

        // Insights: pick the lowest average table name if available
        $weakCategory = null;
        $avgPerTable = collect($tableAverages['midterm'] + $tableAverages['finals']);
        if ($avgPerTable->count() > 0) {
            $weakCategory = $avgPerTable->sort()->keys()->first();
        }

        return response()->json([
            'overview' => [
                'totalStudents' => $totalStudents,
                'averageGrade' => $avgGrade,
                'passed' => ['count' => $passedCount, 'percent' => $passedPct],
                'failed' => ['count' => $failedCount, 'percent' => $failedPct],
                'trend' => 'Stable', // placeholder
            ],
            'distribution' => $dist,
            'students' => $resultStudents,
            'insights' => [
                'summary' => [
                    'passRate' => $passedPct,
                    'avgWeighted' => $avgGrade,
                    'weakCategory' => $weakCategory,
                ]
            ],
            'course' => ['id' => $course->id, 'title' => $course->title],
        ]);
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

    private function percentToGrade($percent)
    {
        // Simple mapping, can be replaced by school scale
        if ($percent >= 96) return '1.00';
        if ($percent >= 93) return '1.25';
        if ($percent >= 90) return '1.50';
        if ($percent >= 87) return '1.75';
        if ($percent >= 84) return '2.00';
        if ($percent >= 81) return '2.25';
        if ($percent >= 78) return '2.50';
        if ($percent >= 75) return '3.00';
        return '5.00';
    }

    private function authorizeCourse(Course $course)
    {
        $user = Auth::user();
        $isOwner = $course->teacher_id === $user->id;
        $isJoined = $course->joinedCourses()->where('user_id', $user->id)->where('role', 'Teacher')->exists();
        if (!$isOwner && !$isJoined) abort(403);
    }
}
