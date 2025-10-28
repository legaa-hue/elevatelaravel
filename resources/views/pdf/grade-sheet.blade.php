<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grade Sheet - {{ $course->title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 4px solid #7f1d1d;
        }
        
        .header h1 {
            font-size: 22pt;
            font-weight: bold;
            color: #7f1d1d;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 11pt;
            color: #333;
            margin: 3px 0;
        }
        
        .header .subtitle.bold {
            font-weight: bold;
        }
        
        .header h2 {
            font-size: 20pt;
            font-weight: bold;
            color: #7f1d1d;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .header .academic-year {
            font-size: 12pt;
            color: #555;
            font-weight: 600;
        }
        
        .course-info {
            background-color: #f9fafb;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .course-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .course-info td {
            padding: 5px 0;
            font-size: 10pt;
        }
        
        .course-info td.label {
            font-weight: bold;
            width: 150px;
            color: #333;
        }
        
        .course-info td.value {
            color: #000;
        }
        
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .grade-table th {
            background-color: #7f1d1d;
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: center;
            border: 2px solid #000;
            font-size: 10pt;
        }
        
        .grade-table td {
            padding: 8px;
            border: 1px solid #666;
            font-size: 10pt;
        }
        
        .grade-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .grade-table td.number {
            text-align: center;
            font-weight: 600;
            width: 40px;
        }
        
        .grade-table td.name {
            text-align: left;
            font-weight: 600;
        }
        
        .grade-table td.grade {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .grade-table td.final-grade {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            color: #1d4ed8;
        }
        
        .grade-table td.letter-grade {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            color: #15803d;
        }
        
        .grade-table td.remarks {
            text-align: center;
            font-weight: 600;
        }
        
        .grading-system {
            background-color: #f9fafb;
            padding: 15px;
            border: 2px solid #ddd;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .grading-system h3 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
        }
        
        .grading-system table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .grading-system td {
            padding: 3px 5px;
            font-size: 9pt;
            width: 25%;
        }
        
        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signatures td {
            text-align: center;
            vertical-align: bottom;
            width: 33.33%;
            padding: 0 20px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 60px;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 3px;
        }
        
        .signature-title {
            font-size: 9pt;
            color: #555;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>PHILIPPINE CHRISTIAN UNIVERSITY</h1>
        <div class="subtitle">Taft Avenue, Manila</div>
        <div class="subtitle bold">GRADUATE SCHOOL</div>
        <h2>GRADING SHEET</h2>
        <div class="academic-year">{{ $course->academicYear->name ?? 'Academic Year' }}</div>
    </div>

    <!-- Course Information -->
    <div class="course-info">
        <table>
            <tr>
                <td class="label">Course Title:</td>
                <td class="value">{{ $course->title }}</td>
                <td class="label">Course Code:</td>
                <td class="value">{{ $course->section }}</td>
            </tr>
            <tr>
                <td class="label">Instructor:</td>
                <td class="value">{{ $course->teacher->first_name }} {{ $course->teacher->last_name }}</td>
                <td class="label">Program:</td>
                <td class="value">{{ $course->program->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Academic Year:</td>
                <td class="value">{{ $course->academicYear->name ?? 'N/A' }}</td>
                <td class="label">Total Students:</td>
                <td class="value">{{ count($students) }}</td>
            </tr>
        </table>
    </div>

    <!-- Grade Table -->
    <table class="grade-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Student Name</th>
                <th>Midterm<br>Grade</th>
                <th>Finals<br>Grade</th>
                <th>Final Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td class="number">{{ $index + 1 }}</td>
                <td class="name">{{ $student->last_name }}, {{ $student->first_name }}</td>
                <td class="grade">{{ $student->midterm_grade }}</td>
                <td class="grade">{{ $student->finals_grade }}</td>
                <td class="letter-grade">{{ $student->letter_grade }}</td>
                <td class="remarks">{{ $student->remarks }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                    No students enrolled in this course
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Grading System -->
    <div class="grading-system">
        <h3>Grading System</h3>
        <table>
            <tr>
                <td><b>1.0</b> - 97-100 (Excellent)</td>
                <td><b>1.25</b> - 94-96 (Very Good)</td>
                <td><b>1.5</b> - 91-93 (Very Good)</td>
                <td><b>1.75</b> - 88-90 (Good)</td>
            </tr>
            <tr>
                <td><b>2.0</b> - 85-87 (Good)</td>
                <td><b>2.25</b> - 82-84 (Satisfactory)</td>
                <td><b>2.5</b> - 79-81 (Satisfactory)</td>
                <td><b>2.75</b> - 76-78 (Fair)</td>
            </tr>
            <tr>
                <td><b>3.0</b> - 75 (Passing)</td>
                <td><b>4.0</b> - 70-74 (Conditional)</td>
                <td><b>5.0</b> - 65-69 (Failing)</td>
                <td><b>F</b> - Below 65 (Failing)</td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="signature-line">
                        <div class="signature-name">{{ $course->teacher->first_name }} {{ $course->teacher->last_name }}</div>
                        <div class="signature-title">Instructor</div>
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        <div class="signature-name">&nbsp;</div>
                        <div class="signature-title">Program Director</div>
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        <div class="signature-name">&nbsp;</div>
                        <div class="signature-title">Dean, Graduate School</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        Date Generated: {{ date('F d, Y') }}
    </div>
</body>
</html>
