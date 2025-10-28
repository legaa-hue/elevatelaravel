<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassRecordController extends Controller
{
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
