<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradebookController extends Controller
{
    public function index()
    {
        // Get teacher's own courses
        $myCourses = auth()->user()->courses()
            ->with('teacher', 'academicYear')
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get courses where teacher has joined
        $joinedCourses = auth()->user()->joinedTeacherCourses()
            ->with('teacher', 'academicYear')
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Teacher/GradebookIndex', [
            'myCourses' => $myCourses,
            'joinedCourses' => $joinedCourses,
        ]);
    }

    public function show(Course $course)
    {
        // Check if user is the teacher, joined teacher, or admin
        if (auth()->user()->role !== 'admin' && 
            $course->teacher_id !== auth()->id() && 
            !$course->teachers->contains(auth()->id())) {
            abort(403, 'Unauthorized. Teacher or Admin access only.');
        }

        // Get enrolled students with role filter
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get()
            ->map(function($student) {
                $student->name = $student->first_name . ' ' . $student->last_name;
                return $student;
            });

        // Get all classworks for this course
        $classworks = $course->classworks()
            ->whereIn('type', ['assignment', 'quiz', 'activity'])
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get(['id', 'title', 'type', 'points', 'grading_period', 'grade_table_name', 'grade_main_column', 'grade_sub_column']);

        // Get all graded submissions to populate grades
        $submissions = \App\Models\ClassworkSubmission::whereIn('classwork_id', $classworks->pluck('id'))
            ->whereIn('status', ['graded', 'returned'])
            ->whereNotNull('grade')
            ->get(['id', 'classwork_id', 'student_id', 'grade']);

        // Include saved gradebook (structure + saved grades) if exists
        return Inertia::render('Teacher/Gradebook', [
            'course' => $course,
            'students' => $students,
            'classworks' => $classworks,
            'submissions' => $submissions,
            'gradebook' => $course->gradebook ?? null,
        ]);
    }

    public function saveGrades(Request $request, Course $course)
    {
        // Check if user is the teacher, joined teacher, or admin
        if (auth()->user()->role !== 'admin' && 
            $course->teacher_id !== auth()->id() && 
            !$course->teachers->contains(auth()->id())) {
            abort(403, 'Unauthorized. Teacher or Admin access only.');
        }

        $validated = $request->validate([
            'gradingPeriod' => 'required|in:midterm,finals',
            'grades' => 'required|array',
            'tableStructure' => 'required|array',
            // optional midterm/finals percentages
            'midtermPercentage' => 'nullable|numeric|min:0|max:100',
            'finalsPercentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Persist gradebook data into the course->gradebook JSON column
        $gradebook = $course->gradebook ?? [];

        $gradebook[$validated['gradingPeriod']] = [
            'tables' => $validated['tableStructure'],
            'grades' => $validated['grades'],
        ];

        if (isset($validated['midtermPercentage'])) {
            $gradebook['midtermPercentage'] = $validated['midtermPercentage'];
        }
        if (isset($validated['finalsPercentage'])) {
            $gradebook['finalsPercentage'] = $validated['finalsPercentage'];
        }

        $course->gradebook = $gradebook;
        $course->save();

        return redirect()->back()->with('success', 'Grades and gradebook saved successfully');
    }

    public function save(Request $request, Course $course)
    {
        // Check if user is the teacher, joined teacher, or admin
        if (auth()->user()->role !== 'admin' && 
            $course->teacher_id !== auth()->id() && 
            !$course->teachers->contains(auth()->id())) {
            abort(403, 'Unauthorized. Teacher or Admin access only.');
        }

        $validated = $request->validate([
            'midtermPercentage' => 'nullable|numeric|min:0|max:100',
            'finalsPercentage' => 'nullable|numeric|min:0|max:100',
            'midterm' => 'nullable|array',
            'midterm.tables' => 'nullable|array',
            'midterm.grades' => 'nullable|array',
            'finals' => 'nullable|array',
            'finals.tables' => 'nullable|array',
            'finals.grades' => 'nullable|array',
        ]);

        // Ensure default structure if not provided
        $gradebookData = [
            'midtermPercentage' => $validated['midtermPercentage'] ?? 50,
            'finalsPercentage' => $validated['finalsPercentage'] ?? 50,
            'midterm' => $validated['midterm'] ?? [
                'tables' => [],
                'grades' => []
            ],
            'finals' => $validated['finals'] ?? [
                'tables' => [],
                'grades' => []
            ]
        ];

        // Save the complete gradebook structure
        \Log::info('Saving gradebook for course ' . $course->id, ['data' => $gradebookData]);
        
        $course->gradebook = $gradebookData;
        $course->save();
        
        \Log::info('Gradebook saved successfully for course ' . $course->id);

        return response()->json([
            'success' => true,
            'message' => 'Gradebook saved successfully'
        ]);
    }

    public function load(Course $course)
    {
        // Check if user is the teacher, joined teacher, or admin
        if (auth()->user()->role !== 'admin' && 
            $course->teacher_id !== auth()->id() && 
            !$course->teachers->contains(auth()->id())) {
            abort(403, 'Unauthorized. Teacher or Admin access only.');
        }

        return response()->json([
            'gradebook' => $course->gradebook
        ]);
    }
}
