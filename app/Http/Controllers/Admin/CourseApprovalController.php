<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class CourseApprovalController extends Controller
{
    public function approve($id)
    {
        $course = Course::findOrFail($id);
        
        $course->update(['status' => 'Active']);

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Approve',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Approved course: {$course->title}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Course approved successfully!');
    }

    public function reject($id)
    {
        $course = Course::findOrFail($id);
        
        $course->update(['status' => 'Inactive']);

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Reject',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Rejected course: {$course->title}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Course rejected successfully!');
    }

    public function archive($id)
    {
        $course = Course::findOrFail($id);
        
        $course->update(['status' => 'Archived']);

        // Log activity
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Archive',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Archived course: {$course->title}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Course archived successfully!');
    }
}
