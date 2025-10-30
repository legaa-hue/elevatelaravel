<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Performance - {{ $course->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 5px 0;
            font-size: 18px;
            color: #2563eb;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2563eb;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .excellent {
            color: #059669;
            font-weight: bold;
        }
        .good {
            color: #0284c7;
            font-weight: bold;
        }
        .average {
            color: #f59e0b;
            font-weight: bold;
        }
        .poor {
            color: #dc2626;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
        .stats-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
        }
        .stats-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #1e40af;
        }
        .stats-box p {
            margin: 3px 0;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COURSE PERFORMANCE REPORT</h1>
        <h2>{{ $course->title }} - {{ $course->section }}</h2>
        @if($course->academicYear)
        <p>Academic Year: {{ $course->academicYear->year_name }}</p>
        @endif
        <p>Teacher: {{ $course->teacher->first_name }} {{ $course->teacher->last_name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Student Name</th>
                <th style="width: 15%;" class="text-center">Total Submissions</th>
                <th style="width: 15%;" class="text-center">Total Classwork</th>
                <th style="width: 15%;" class="text-center">Average Grade</th>
                <th style="width: 15%;" class="text-center">Performance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $student['name'] }}</td>
                <td class="text-center">{{ $student['total_submissions'] }}</td>
                <td class="text-center">{{ $student['total_classwork'] }}</td>
                <td class="text-center"><strong>{{ $student['average_grade'] }}</strong></td>
                <td class="text-center">
                    @php
                        $percentage = floatval($student['percentage']);
                        $class = $percentage >= 90 ? 'excellent' : ($percentage >= 75 ? 'good' : ($percentage >= 60 ? 'average' : 'poor'));
                    @endphp
                    <span class="{{ $class }}">{{ $student['percentage'] }}%</span>
                </td>
            </tr>
            @endforeach
            @if(count($students) === 0)
            <tr>
                <td colspan="6" class="text-center">No students enrolled</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="stats-box">
        <h3>Performance Statistics</h3>
        @php
            $totalStudents = count($students);
            $avgPerformance = $totalStudents > 0 ? collect($students)->avg(function($s) { return floatval($s['percentage']); }) : 0;
            $excellentCount = collect($students)->filter(function($s) { return floatval($s['percentage']) >= 90; })->count();
            $goodCount = collect($students)->filter(function($s) { $p = floatval($s['percentage']); return $p >= 75 && $p < 90; })->count();
            $avgCount = collect($students)->filter(function($s) { $p = floatval($s['percentage']); return $p >= 60 && $p < 75; })->count();
            $poorCount = collect($students)->filter(function($s) { return floatval($s['percentage']) < 60; })->count();
        @endphp
        <p><strong>Total Students:</strong> {{ $totalStudents }}</p>
        <p><strong>Class Average:</strong> {{ number_format($avgPerformance, 2) }}%</p>
        <p><strong>Performance Distribution:</strong></p>
        <p style="margin-left: 15px;">
            Excellent (90%+): {{ $excellentCount }} | 
            Good (75-89%): {{ $goodCount }} | 
            Average (60-74%): {{ $avgCount }} | 
            Needs Improvement (<60%): {{ $poorCount }}
        </p>
    </div>

    <div class="footer">
        <p><strong>Generated by:</strong> {{ $generated_by }}</p>
        <p><strong>Generated on:</strong> {{ $generated_at }}</p>
        <p><em>This report shows cumulative performance based on graded submissions.</em></p>
    </div>
</body>
</html>
