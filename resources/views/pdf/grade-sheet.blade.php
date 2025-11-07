<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grade Sheet - {{ $course->title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm 1.5cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }

        .logo-container img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 0 10px;
        }

        .header-center h1 {
            font-size: 16pt;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: table-cell;
            width: 200px;
            vertical-align: middle;
            text-align: right;
        }

        .header-right .subtitle {
            font-size: 8pt;
            color: #000;
            font-weight: 600;
            line-height: 1.3;
        }

        .title-section {
            text-align: center;
            margin: 10px 0 15px 0;
        }

        .title-section h2 {
            font-size: 14pt;
            font-weight: bold;
            color: #000;
            letter-spacing: 2px;
        }

        .course-info {
            margin-bottom: 15px;
            font-size: 8pt;
            line-height: 1.6;
        }

        .course-info-line {
            margin-bottom: 3px;
        }

        .course-info-line .label {
            font-weight: bold;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .student-table th {
            background-color: #fff;
            color: #000;
            font-weight: bold;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 8pt;
        }

        .student-table td {
            padding: 4px;
            border: 1px solid #000;
            font-size: 8pt;
        }

        .student-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .student-table td.number {
            text-align: center;
            width: 30px;
        }

        .student-table td.name {
            text-align: left;
            padding-left: 8px;
            width: 180px;
        }

        .student-table td.course {
            text-align: center;
            font-size: 7pt;
            width: 120px;
        }

        .student-table td.grade {
            text-align: center;
            width: 60px;
            font-weight: 600;
        }

        .student-table td.remarks {
            text-align: center;
            width: 70px;
            font-weight: 600;
        }

        .grading-system {
            background-color: #fff;
            padding: 10px;
            border: 1px solid #000;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .grading-system h3 {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000;
        }

        .grading-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grading-table td {
            padding: 2px 8px;
            font-size: 8pt;
            vertical-align: top;
        }

        .grading-col {
            width: 33.33%;
        }

        .grading-row {
            margin-bottom: 2px;
        }

        .signatures {
            margin-top: 30px;
            page-break-inside: avoid;
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 50px;
            display: inline-block;
            min-width: 250px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2px;
        }

        .signature-title {
            font-size: 9pt;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            color: #666;
        }

        .footer-address {
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            @if(file_exists(public_path('images/usant-logo.png')))
            <div class="logo-container">
                <img src="{{ public_path('images/usant-logo.png') }}" alt="University Logo">
            </div>
            @endif
        </div>
        <div class="header-center">
            <h1>UNIVERSITY OF SAINT ANTHONY</h1>
        </div>
        <div class="header-right">
            <div class="subtitle">
                SCHOOL OF<br>
                GRADUATE STUDIES<br>
                AND RESEARCH
            </div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-section">
        <h2>GRADING SHEET</h2>
    </div>

    <!-- Course Information -->
    <div class="course-info">
        <div class="course-info-line">
            <span class="label">Course Code:</span> {{ $course->section }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="label">Course Name:</span> {{ $course->title }}
        </div>
        <div class="course-info-line">
            <span class="label">Units:</span> {{ $course->units ?? '3' }}
            &nbsp;&nbsp;
            <span class="label">Semester:</span> {{ $semester }}
            &nbsp;&nbsp;
            <span class="label">Summer:</span> _____
            &nbsp;&nbsp;
            <span class="label">School Year:</span> {{ $course->academicYear->year_name ?? $course->academicYear->name ?? 'N/A' }}
        </div>
    </div>

    <!-- Student Table -->
    <table class="student-table">
        <thead>
            <tr>
                <th>NO</th>
                <th>NAME OF STUDENTS</th>
                <th>COURSE</th>
                <th>MID-TERM</th>
                <th>FINAL</th>
                <th>REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td class="number">{{ $index + 1 }}</td>
                <td class="name">{{ strtoupper($student->last_name ?? '') }}, {{ strtoupper($student->first_name ?? '') }}</td>
                <td class="course">{{ $programName ?? 'N/A' }}</td>
                <td class="grade">{{ $student->grade_point ?? '0.00' }}</td>
                <td class="grade">{{ $student->grade_point ?? '0.00' }}</td>
                <td class="remarks">{{ $student->remarks ?? 'Incomplete' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 15px; color: #666;">
                    No students enrolled in this course
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Grading System -->
    <div class="grading-system">
        <h3>GRADING SYSTEM:</h3>
        <table class="grading-table">
            <tr>
                <td class="grading-col">
                    <div class="grading-row"><b>Percent</b> &nbsp; <b>Grade</b></div>
                    <div class="grading-row">100 &nbsp;&nbsp;&nbsp;&nbsp; 1.0</div>
                    <div class="grading-row">99 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.15</div>
                    <div class="grading-row">98 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.2</div>
                    <div class="grading-row">97 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.25</div>
                    <div class="grading-row">96 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.3</div>
                </td>
                <td class="grading-col">
                    <div class="grading-row"><b>Percent</b> &nbsp; <b>Grade</b></div>
                    <div class="grading-row">95 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.35</div>
                    <div class="grading-row">94 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.4</div>
                    <div class="grading-row">93 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.45</div>
                    <div class="grading-row">92 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.5</div>
                    <div class="grading-row">91 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.55</div>
                </td>
                <td class="grading-col">
                    <div class="grading-row"><b>Percent</b> &nbsp; <b>Grade</b></div>
                    <div class="grading-row">90 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.6</div>
                    <div class="grading-row">89 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.65</div>
                    <div class="grading-row">88 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.7</div>
                    <div class="grading-row">87 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.75</div>
                    <div class="grading-row">86 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.8</div>
                    <div class="grading-row">85 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1.85</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-line">
            <div class="signature-name">
                @if($course->teacher)
                    {{ strtoupper($course->teacher->first_name ?? '') }} {{ strtoupper($course->teacher->last_name ?? '') }}
                @else
                    &nbsp;
                @endif
            </div>
            <div class="signature-title">Professor</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-address">San Miguel, Iriga City | 4431 | Philippines</div>
        <div class="footer-address">205-1234 loc 137 | www.usant.edu.ph | info.gradschool@usant.edu.ph</div>
    </div>
</body>
</html>
