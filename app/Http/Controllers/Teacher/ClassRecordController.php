<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassRecordController extends Controller
{
    /**
     * Show list of courses for class records (owned and joined by current user)
     */
    public function index()
    {
        $user = auth()->user();

        // Courses the user owns (as primary teacher)
        $myCourses = Course::with(['teacher', 'program', 'academicYear'])
            ->withCount('students')
            ->where('teacher_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'section' => $c->section,
                    'status' => $c->status,
                    'students_count' => $c->students_count,
                    'teacher' => [
                        'first_name' => optional($c->teacher)->first_name,
                        'last_name' => optional($c->teacher)->last_name,
                    ],
                ];
            });

        // Courses the user joined (as Teacher assistant or Student)
        $joinedCourses = Course::with(['teacher', 'program', 'academicYear'])
            ->withCount('students')
            ->whereHas('joinedCourses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'section' => $c->section,
                    'status' => $c->status,
                    'students_count' => $c->students_count,
                    'teacher' => [
                        'first_name' => optional($c->teacher)->first_name,
                        'last_name' => optional($c->teacher)->last_name,
                    ],
                ];
            });

        return Inertia::render('Teacher/ClassRecord', [
            'myCourses' => $myCourses,
            'joinedCourses' => $joinedCourses,
        ]);
    }

    /**
     * Display class record for a specific course
     */
    public function show(Course $course)
    {
        // Check if teacher owns or is assigned to this course
        $user = auth()->user();
        $isOwner = $course->teacher_id === $user->id;
        $isAssigned = $course->teachers()->where('user_id', $user->id)->exists();

        if (!$isOwner && !$isAssigned) {
            abort(403, 'Unauthorized access to this course.');
        }

        // Load course with necessary relationships
        $course->load([
            'teacher',
            'academicYear',
            'program',
            'students' => function ($query) {
                $query->with('joinedCourses');
            }
        ]);

        return Inertia::render('Teacher/GradeSheet', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'section' => $course->section,
                'description' => $course->description,
                'join_code' => $course->join_code,
                'status' => $course->status,
                'teacher' => [
                    'id' => $course->teacher->id ?? null,
                    'first_name' => $course->teacher->first_name ?? 'N/A',
                    'last_name' => $course->teacher->last_name ?? '',
                ],
                'academic_year' => $course->academicYear ? [
                    'id' => $course->academicYear->id,
                    'name' => $course->academicYear->year_name,
                ] : null,
                'program' => $course->program ? [
                    'id' => $course->program->id,
                    'name' => $course->program->program_name,
                ] : null,
            ],
        ]);
    }
}
