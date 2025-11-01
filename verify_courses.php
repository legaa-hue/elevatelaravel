<?php

use App\Models\Program;
use App\Models\CourseTemplate;

// Get one program to check
$program = Program::where('name', 'LIKE', '%MATHEMATICS%')->first();

echo "Program: {$program->name}\n";
echo str_repeat('=', 80) . "\n\n";

// Get courses grouped by type
$basicCourses = CourseTemplate::where('program_id', $program->id)
    ->where('course_type', 'Basic Course')
    ->get();

$majorCourses = CourseTemplate::where('program_id', $program->id)
    ->where('course_type', 'Major Course')
    ->get();

$thesisCourses = CourseTemplate::where('program_id', $program->id)
    ->where('course_type', 'Thesis')
    ->get();

echo "BASIC COURSES:\n";
echo str_repeat('-', 80) . "\n";
foreach ($basicCourses as $course) {
    echo "{$course->course_code} - {$course->course_name} - {$course->units} units\n";
}

echo "\nMAJOR COURSES:\n";
echo str_repeat('-', 80) . "\n";
foreach ($majorCourses as $course) {
    echo "{$course->course_code} - {$course->course_name} - {$course->units} units\n";
}

echo "\nTHESIS COURSES:\n";
echo str_repeat('-', 80) . "\n";
foreach ($thesisCourses as $course) {
    echo "{$course->course_code} - {$course->course_name} - {$course->units} units\n";
}

echo "\nSUMMARY:\n";
echo str_repeat('=', 80) . "\n";
echo "Basic Courses: " . $basicCourses->count() . "\n";
echo "Major Courses: " . $majorCourses->count() . "\n";
echo "Thesis Courses: " . $thesisCourses->count() . "\n";
echo "Total: " . ($basicCourses->count() + $majorCourses->count() + $thesisCourses->count()) . " courses\n";
