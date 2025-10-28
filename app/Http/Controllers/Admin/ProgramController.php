<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\CourseTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::withCount('courseTemplates')
            ->orderBy('created_at', 'desc')
            ->get();
        return inertia('Admin/Programs', [
            'programs' => $programs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Program::create($validated);

        return redirect()->back()->with('success', 'Program created successfully');
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $program->update($validated);

        return redirect()->back()->with('success', 'Program updated successfully');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()->back()->with('success', 'Program deleted successfully');
    }

    public function getCourseTemplates(Request $request, $programId)
    {
        $query = CourseTemplate::where('program_id', $programId)
            ->where('status', 'Active');
        
        // Filter by course_type if provided
        if ($request->has('course_type') && $request->course_type) {
            $query->where('course_type', $request->course_type);
        }
        
        $templates = $query->orderBy('course_code')->get();
        
        return response()->json($templates);
    }

    public function getPrograms()
    {
        $programs = Program::where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
        
        return response()->json($programs);
    }
}
