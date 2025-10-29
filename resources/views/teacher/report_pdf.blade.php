<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        .small { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; }
        .mt-2 { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Teacher Report — {{ $data['course']['title'] ?? '' }}</h1>
    <div>
        <strong>Overview</strong>
        <div class="small">Total Students: {{ $data['overview']['totalStudents'] ?? 0 }}</div>
        <div class="small">Average Grade: {{ $data['overview']['averageGrade'] ?? 0 }}%</div>
        <div class="small">Passed: {{ $data['overview']['passed']['count'] ?? 0 }} ({{ $data['overview']['passed']['percent'] ?? 0 }}%)</div>
        <div class="small">Failed: {{ $data['overview']['failed']['count'] ?? 0 }} ({{ $data['overview']['failed']['percent'] ?? 0 }}%)</div>
    </div>

    <h3 class="mt-2">Students</h3>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Weighted Avg (%)</th>
                <th>Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($data['students'] ?? []) as $s)
                <tr>
                    <td>{{ $s['name'] }}</td>
                    <td>{{ $s['weightedAvg'] }}</td>
                    <td>{{ $s['grade'] }}</td>
                    <td>{{ $s['remarks'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
