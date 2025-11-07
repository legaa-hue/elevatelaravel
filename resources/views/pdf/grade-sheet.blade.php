<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grade Sheet - {{ $course->title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 2cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
        }

        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 100px;
        }

        .logo-container {
            display: inline-block;
            vertical-align: middle;
        }

        .logo-container img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 0 15px;
        }

        .header-center h1 {
            font-size: 14pt;
            font-weight: bold;
            color: #c41e3a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: table-cell;
            width: 180px;
            vertical-align: middle;
            text-align: right;
        }

        .header-right .subtitle {
            font-size: 9pt;
            color: #000;
            font-weight: bold;
            line-height: 1.5;
        }

        .title-section {
            text-align: center;
            margin: 8px 0 12px 0;
        }

        .title-section h2 {
            font-size: 13pt;
            font-weight: bold;
            color: #000;
            letter-spacing: 1.5px;
        }

        .course-info {
            margin-bottom: 12px;
            font-size: 9pt;
            line-height: 1.8;
        }

        .course-info-line {
            margin-bottom: 4px;
        }

        .course-info-line .label {
            font-weight: bold;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .student-table th {
            background-color: #fff;
            color: #000;
            font-weight: bold;
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 9pt;
        }

        .student-table td {
            padding: 5px 4px;
            border: 1px solid #000;
            font-size: 9pt;
        }

        .student-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .student-table td.number {
            text-align: center;
            width: 35px;
        }

        .student-table td.name {
            text-align: left;
            padding-left: 8px;
            width: 200px;
        }

        .student-table td.course {
            text-align: center;
            font-size: 8pt;
            width: 140px;
        }

        .student-table td.grade {
            text-align: center;
            width: 65px;
            font-weight: normal;
        }

        .student-table td.remarks {
            text-align: center;
            width: 75px;
            font-weight: normal;
        }

        .grading-system {
            background-color: #fff;
            padding: 8px 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .grading-system h3 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 6px;
            color: #000;
        }

        .grading-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grading-table td {
            padding: 1px 10px;
            font-size: 9pt;
            vertical-align: top;
        }

        .grading-col {
            width: 33.33%;
        }

        .grading-row {
            margin-bottom: 1px;
            line-height: 1.4;
        }

        .signatures {
            margin-top: 25px;
            page-break-inside: avoid;
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 40px;
            display: inline-block;
            min-width: 200px;
            text-align: center;
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
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #333;
        }

        .footer-line {
            margin-bottom: 2px;
            line-height: 1.6;
        }

        .footer-icon {
            display: inline-block;
            margin-right: 3px;
            font-weight: bold;
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
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span class="label">Course Name:</span> {{ $course->title }}
        </div>
        <div class="course-info-line">
            <span class="label">Units:</span> {{ $course->units ?? '3' }}
            &nbsp;&nbsp;
            <span class="label">Semester:</span> {{ $semester }}
            &nbsp;&nbsp;
            <span class="label">Summer:</span> ____
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
        <div class="footer-line">📍 San Miguel, Iriga City | 4431 | Philippines</div>
        <div class="footer-line">
            📞 205-1234 loc 137 | 🌐 www.usant.edu.ph | ✉ info.gradschool@usant.edu.ph
        </div>
    </div>
</body>
</html>
