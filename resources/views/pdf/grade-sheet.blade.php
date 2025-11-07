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
            font-size: 10pt;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #000;
            position: relative;
        }

        .logo-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: 80px;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-text {
            padding-top: 5px;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .header .subtitle {
            font-size: 9pt;
            color: #000;
            margin: 2px 0;
            font-weight: 600;
        }

        .header h2 {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
            margin-top: 10px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .course-info {
            margin-bottom: 15px;
            padding: 10px 0;
        }

        .course-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .course-info td {
            padding: 3px 0;
            font-size: 9pt;
        }

        .course-info td.label {
            font-weight: bold;
            width: 120px;
            color: #000;
        }

        .course-info td.value {
            color: #000;
        }

        .course-info .section-divider {
            width: 50px;
        }

        .grade-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .grade-table th {
            background-color: #fff;
            color: #000;
            font-weight: bold;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #000;
            font-size: 9pt;
        }

        .grade-table td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 9pt;
        }

        .grade-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .grade-table td.number {
            text-align: center;
            width: 40px;
        }

        .grade-table td.name {
            text-align: left;
            padding-left: 10px;
        }

        .grade-table td.course {
            text-align: center;
            width: 120px;
        }

        .grade-table td.grade {
            text-align: center;
            width: 80px;
            font-weight: 600;
        }

        .grade-table td.remarks {
            text-align: center;
            width: 80px;
            font-weight: 600;
        }

        .grading-system {
            background-color: #fff;
            padding: 12px;
            border: 1px solid #000;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .grading-system h3 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000;
        }

        .grading-system table {
            width: 100%;
            border-collapse: collapse;
        }

        .grading-system td {
            padding: 2px 5px;
            font-size: 8pt;
            width: 25%;
        }

        .signatures {
            margin-top: 30px;
            page-break-inside: avoid;
            text-align: right;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 250px;
        }

        .signature-line {
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 50px;
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
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
        }

        .footer-address {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if(file_exists(public_path('images/usant-logo.png')))
        <div class="logo-container">
            <img src="{{ public_path('images/usant-logo.png') }}" alt="University Logo">
        </div>
        @endif
        <div class="header-text">
            <h1>UNIVERSITY OF SAINT ANTHONY</h1>
            <div class="subtitle">SCHOOL OF CONTINUING AND PROFESSIONAL STUDIES AND RESEARCH</div>
            <h2>GRADING SHEET</h2>
        </div>
    </div>

    <!-- Course Information -->
    <div class="course-info">
        <table>
            <tr>
                <td class="label">Course Code:</td>
                <td class="value">{{ $course->section }}</td>
                <td class="section-divider"></td>
                <td class="label">Course Name:</td>
                <td class="value">{{ $course->title }}</td>
            </tr>
            <tr>
                <td class="label">Units:</td>
                <td class="value">{{ $course->units ?? '3' }}</td>
                <td class="section-divider"></td>
                <td class="label">Semester:</td>
                <td class="value">{{ $semester }}</td>
            </tr>
            <tr>
                <td class="label">School Year:</td>
                <td class="value">{{ $course->academicYear->year_name ?? $course->academicYear->name ?? 'N/A' }}</td>
                <td class="section-divider"></td>
                <td class="label"></td>
                <td class="value"></td>
            </tr>
        </table>
    </div>

    <!-- Grade Table -->
    <table class="grade-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>NAME OF STUDENTS</th>
                <th>COURSE</th>
                <th>MIDTERM</th>
                <th>FINAL TERM</th>
                <th>REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td class="number">{{ $index + 1 }}</td>
                <td class="name">{{ strtoupper($student->last_name) }}, {{ strtoupper($student->first_name) }}</td>
                <td class="course">{{ $programName }}</td>
                <td class="grade">{{ $student->grade_point }}</td>
                <td class="grade">{{ $student->grade_point }}</td>
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
        <h3>GRADING SYSTEM:</h3>
        <table>
            <tr>
                <td><b>Percent</b></td>
                <td><b>Grade</b></td>
                <td><b>Percent</b></td>
                <td><b>Grade</b></td>
                <td><b>Percent</b></td>
                <td><b>Grade</b></td>
            </tr>
            <tr>
                <td>100</td>
                <td>1.0</td>
                <td>95</td>
                <td>1.35</td>
                <td>90</td>
                <td>1.6</td>
            </tr>
            <tr>
                <td>99</td>
                <td>1.15</td>
                <td>94</td>
                <td>1.4</td>
                <td>89</td>
                <td>1.65</td>
            </tr>
            <tr>
                <td>98</td>
                <td>1.2</td>
                <td>93</td>
                <td>1.45</td>
                <td>88</td>
                <td>1.7</td>
            </tr>
            <tr>
                <td>97</td>
                <td>1.25</td>
                <td>92</td>
                <td>1.5</td>
                <td>87</td>
                <td>1.75</td>
            </tr>
            <tr>
                <td>96</td>
                <td>1.3</td>
                <td>91</td>
                <td>1.55</td>
                <td>86</td>
                <td>1.8</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>85</td>
                <td>1.85</td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <div class="signature-name">{{ strtoupper($course->teacher->first_name) }} {{ strtoupper($course->teacher->last_name) }}</div>
                <div class="signature-title">Professor</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-address">San Miguel, Iriga City | 4431 | Philippines</div>
        <div class="footer-address">205-1234 loc 137 | www.usant.edu.ph | info.gradschool@usant.edu.ph</div>
    </div>
</body>
</html>
