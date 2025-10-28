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

        // Get enrolled students
        $students = $course->students()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->selectRaw("users.first_name || ' ' || users.last_name as name")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        // Get all classworks for this course
        $classworks = $course->classworks()
            ->whereIn('type', ['assignment', 'quiz', 'activity'])
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get(['id', 'title', 'type', 'points']);

            // Include saved gradebook (structure + saved grades) if exists
            return Inertia::render('Teacher/Gradebook', [
                'course' => $course,
                'students' => $students,
                'classworks' => $classworks,
                'gradebook' => $course->gradebook ?? null,
            ]);
    }

    public function saveGrades(Request $request, Course $course)
    {
        // Check if user is the teacher or admin
        if (auth()->user()->role !== 'admin' && $course->teacher_id !== auth()->id()) {
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
}
