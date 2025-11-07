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
                <td class="name">{{ strtoupper($student->last_name ?? '') }}, {{ strtoupper($student->first_name ?? '') }}</td>
                <td class="course">{{ $programName ?? 'N/A' }}</td>
                <td class="grade">{{ $student->grade_point ?? '0.00' }}</td>
                <td class="grade">{{ $student->grade_point ?? '0.00' }}</td>
                <td class="remarks">{{ $student->remarks ?? 'Incomplete' }}</td>
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
                <td>97-100</td>
                <td>1.00</td>
                <td>79-81</td>
                <td>2.50</td>
                <td>63-65</td>
                <td>4.00</td>
            </tr>
            <tr>
                <td>94-96</td>
                <td>1.25</td>
                <td>76-78</td>
                <td>2.75</td>
                <td>60-62</td>
                <td>4.25</td>
            </tr>
            <tr>
                <td>91-93</td>
                <td>1.50</td>
                <td>75</td>
                <td>3.00</td>
                <td>57-59</td>
                <td>4.50</td>
            </tr>
            <tr>
                <td>88-90</td>
                <td>1.75</td>
                <td>72-74</td>
                <td>3.25</td>
                <td>54-56</td>
                <td>4.75</td>
            </tr>
            <tr>
                <td>85-87</td>
                <td>2.00</td>
                <td>69-71</td>
                <td>3.50</td>
                <td>Below 54</td>
                <td>5.00</td>
            </tr>
            <tr>
                <td>82-84</td>
                <td>2.25</td>
                <td>66-68</td>
                <td>3.75</td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
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
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-address">San Miguel, Iriga City | 4431 | Philippines</div>
        <div class="footer-address">205-1234 loc 137 | www.usant.edu.ph | info.gradschool@usant.edu.ph</div>
    </div>
</body>
</html>
