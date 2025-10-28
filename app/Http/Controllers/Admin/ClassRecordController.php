<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassRecordController extends Controller
{
    /**
     * Display all courses with their gradebook links
     */
    public function index()
    {
        $courses = Course::with(['teacher', 'academicYear', 'program'])
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'section' => $course->section,
                    'description' => $course->description,
                    'status' => $course->status,
                    'students_count' => $course->students_count,
                    'teacher' => [
                        'id' => $course->teacher->id ?? null,
                        'first_name' => $course->teacher->first_name ?? 'N/A',
                        'last_name' => $course->teacher->last_name ?? '',
                    ],
                    'academic_year' => $course->academicYear ? $course->academicYear->year_name : 'N/A',
                    'program' => $course->program ? $course->program->program_name : 'N/A',
                ];
            });

        return Inertia::render('Admin/ClassRecord', [
            'courses' => $courses,
        ]);
    }
}
