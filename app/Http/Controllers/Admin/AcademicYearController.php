<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AcademicYearController extends Controller
{
    /**
     * Display the academic years page.
     */
    public function index()
    {
        $academicYears = AcademicYear::with('uploader')
            ->orderBy('year_name', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeYear = AcademicYear::getActive();

        // Get Programs and Course Templates
        $programs = \App\Models\Program::withCount('courseTemplates')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $courseTemplates = \App\Models\CourseTemplate::with('program')
            ->orderBy('course_code')
            ->get();

        return Inertia::render('Admin/AcademicYear', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'programs' => $programs,
            'courseTemplates' => $courseTemplates,
        ]);
    }

    /**
     * Store a new academic year or upload new version.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year_name' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls|max:10240', // 10MB max
            'status' => 'required|in:Active,Inactive',
            'notes' => 'nullable|string',
        ]);

        try {
            // If setting as Active, deactivate all others
            if ($validated['status'] === 'Active') {
                AcademicYear::where('status', 'Active')->update(['status' => 'Inactive']);
            }

            // Calculate version number
            $version = AcademicYear::getNextVersion($validated['year_name']);

            // Handle file upload
            $filePath = null;
            $fileName = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('academic_years', 'public');
            }

            // Create academic year record
            $academicYear = AcademicYear::create([
                'year_name' => $validated['year_name'],
                'file_path' => $filePath,
                'file_name' => $fileName,
                'version' => $version,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'uploaded_by' => auth()->id(),
            ]);

            // Log the action
            $this->logAction('create', $academicYear, "Created academic year: {$academicYear->year_name} ({$academicYear->version})");

            return redirect()->back()->with('success', 'Academic year created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create academic year: ' . $e->getMessage()]);
        }
    }

    /**
     * Update academic year status.
     */
    public function updateStatus(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'status' => 'required|in:Active,Inactive',
        ]);

        try {
            $oldStatus = $academicYear->status;

            // If setting as Active, deactivate all others
            if ($validated['status'] === 'Active') {
                AcademicYear::where('status', 'Active')->update(['status' => 'Inactive']);
            }

            $academicYear->update(['status' => $validated['status']]);

            // Log the action
            $this->logAction('update', $academicYear, "Updated academic year status: {$academicYear->year_name} from {$oldStatus} to {$validated['status']}", [
                'old' => ['status' => $oldStatus],
                'new' => ['status' => $validated['status']],
            ]);

            return redirect()->back()->with('success', 'Status updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    /**
     * Update academic year notes.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $oldNotes = $academicYear->notes;
            $academicYear->update(['notes' => $validated['notes']]);

            // Log the action
            $this->logAction('update', $academicYear, "Updated academic year notes: {$academicYear->year_name}", [
                'old' => ['notes' => $oldNotes],
                'new' => ['notes' => $validated['notes']],
            ]);

            return redirect()->back()->with('success', 'Notes updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update notes: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete an academic year.
     */
    public function destroy(AcademicYear $academicYear)
    {
        try {
            $yearName = $academicYear->year_name;
            $version = $academicYear->version;

            // Delete file if exists
            if ($academicYear->file_path) {
                Storage::disk('public')->delete($academicYear->file_path);
            }

            $academicYear->delete();

            // Log the action
            $this->logAction('delete', null, "Deleted academic year: {$yearName} ({$version})", null, $academicYear->id);

            return redirect()->back()->with('success', 'Academic year deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to delete academic year: ' . $e->getMessage()]);
        }
    }

    /**
     * Download academic year file.
     */
    public function download(AcademicYear $academicYear)
    {
        if (!$academicYear->file_path || !Storage::disk('public')->exists($academicYear->file_path)) {
            return redirect()->back()->withErrors(['error' => 'File not found.']);
        }

        return Storage::disk('public')->download($academicYear->file_path, $academicYear->file_name);
    }

    /**
     * Log an action to audit logs.
     */
    private function logAction(string $action, $model = null, string $description, array $changes = null, int $modelId = null)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => 'AcademicYear',
            'model_id' => $model ? $model->id : $modelId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => null,
        ]);
    }
}
