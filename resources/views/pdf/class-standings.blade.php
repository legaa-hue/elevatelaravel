<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Standings - {{ $course->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
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
            color: #059669;
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
            background-color: #059669;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #059669;
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
        .rank-1 {
            background-color: #fef3c7 !important;
            font-weight: bold;
        }
        .rank-2 {
            background-color: #e5e7eb !important;
            font-weight: bold;
        }
        .rank-3 {
            background-color: #fed7aa !important;
            font-weight: bold;
        }
        .rank-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 10px;
        }
        .rank-badge-1 {
            background-color: #fbbf24;
            color: #78350f;
        }
        .rank-badge-2 {
            background-color: #9ca3af;
            color: #1f2937;
        }
        .rank-badge-3 {
            background-color: #fb923c;
            color: #7c2d12;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
        .note {
            margin-top: 20px;
            padding: 10px;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .note h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #78350f;
        }
        .note p {
            margin: 3px 0;
            font-size: 10px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CLASS STANDINGS</h1>
        <h2>{{ $course->title }} - {{ $course->section }}</h2>
        @if($course->academicYear)
        <p>Academic Year: {{ $course->academicYear->year_name }}</p>
        @endif
        <p>Teacher: {{ $course->teacher->first_name }} {{ $course->teacher->last_name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">Rank</th>
                <th style="width: 35%;">Student Name</th>
                <th style="width: 20%;" class="text-center">Student ID</th>
                <th style="width: 20%;" class="text-center">Program</th>
                <th style="width: 15%;" class="text-center">Final Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr class="{{ $student['rank'] <= 3 ? 'rank-' . $student['rank'] : '' }}">
                <td class="text-center">
                    @if($student['rank'] === 1)
                        <span class="rank-badge rank-badge-1">🥇 {{ $student['rank'] }}</span>
                    @elseif($student['rank'] === 2)
                        <span class="rank-badge rank-badge-2">🥈 {{ $student['rank'] }}</span>
                    @elseif($student['rank'] === 3)
                        <span class="rank-badge rank-badge-3">🥉 {{ $student['rank'] }}</span>
                    @else
                        <strong>{{ $student['rank'] }}</strong>
                    @endif
                </td>
                <td>{{ $student['name'] }}</td>
                <td class="text-center">{{ $student['student_id'] }}</td>
                <td class="text-center">{{ $student['program'] }}</td>
                <td class="text-center"><strong>{{ $student['final_grade_formatted'] }}</strong></td>
            </tr>
            @endforeach
            @if(count($students) === 0)
            <tr>
                <td colspan="5" class="text-center">No students enrolled</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="note">
        <h3>📋 Ranking Note</h3>
        <p>Students are ranked based on their final grades. Lower grades indicate better performance (e.g., 1.0 is the highest possible grade).</p>
        <p>🥇 Gold - Top Student | 🥈 Silver - Second Place | 🥉 Bronze - Third Place</p>
    </div>

    <div class="footer">
        <p><strong>Generated by:</strong> {{ $generated_by }}</p>
        <p><strong>Generated on:</strong> {{ $generated_at }}</p>
        <p><strong>Total Students:</strong> {{ count($students) }}</p>
    </div>
</body>
</html>
