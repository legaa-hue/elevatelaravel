<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseTemplate;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseTemplateController extends Controller
{
    public function index()
    {
        $courseTemplates = CourseTemplate::with('program')
            ->orderBy('course_code')
            ->get();
        $programs = Program::where('status', 'Active')->get();
        
        return inertia('Admin/CourseTemplates', [
            'courseTemplates' => $courseTemplates,
            'programs' => $programs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'course_code' => 'nullable|string|max:50',
            'course_name' => 'required|string|max:255',
            'course_type' => 'required|in:Major Course,Basic Course,Thesis',
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1|max:12',
            'status' => 'required|in:Active,Inactive',
        ]);

        CourseTemplate::create($validated);

        return redirect()->back()->with('success', 'Course template created successfully');
    }

    public function update(Request $request, CourseTemplate $courseTemplate)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'course_code' => 'nullable|string|max:50',
            'course_name' => 'required|string|max:255',
            'course_type' => 'required|in:Major Course,Basic Course,Thesis',
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1|max:12',
            'status' => 'required|in:Active,Inactive',
        ]);

        $courseTemplate->update($validated);

        return redirect()->back()->with('success', 'Course template updated successfully');
    }

    public function destroy(CourseTemplate $courseTemplate)
    {
        $courseTemplate->delete();

        return redirect()->back()->with('success', 'Course template deleted successfully');
    }
}
