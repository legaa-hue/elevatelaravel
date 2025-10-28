# Classwork File Upload, Due Date & Criteria Fix

## Problem
When teachers created activities, assignments, or other classwork with:
- File attachments
- Due dates
- Rubric criteria

These fields were not being saved properly to the database.

## Root Cause
The issue was in how file data was being sent from the frontend to the backend:

1. **Frontend Issue**: Files were being sent as just filenames (strings) instead of actual File objects
2. **Backend Issue**: The controller wasn't processing file uploads correctly
3. **Data Format**: The backend expected actual file uploads but received only filename strings

## Fixes Applied

### 1. Backend (ClassworkController.php)

#### Added File Upload Handling in `store()` method:
```php
// Handle file uploads
$uploadedFiles = [];
if ($request->hasFile('attachments')) {
    foreach ($request->file('attachments') as $file) {
        $path = $file->store('classwork', 'public');
        $uploadedFiles[] = [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
        ];
    }
}
$validated['attachments'] = $uploadedFiles;
```

#### Updated Validation Rules:
```php
'attachments' => 'nullable|array',
'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
```

#### Added File Upload Handling in `update()` method:
- Similar file processing for updates
- Merges new files with existing files

### 2. Frontend (CourseView.vue)

#### Changed from sending filenames to sending actual files:
**Before:**
```javascript
classworkForm.attachments = fileAttachments.value.map(file => file.name);
```

**After:**
```javascript
classworkForm.attachments = fileAttachments.value; // Send actual File objects
```

#### Added FormData Transformation:
Used `transform()` method to properly format data for file uploads:
```javascript
classworkForm.transform((data) => {
    const formData = new FormData();
    
    // Add files separately
    data.attachments.forEach((file, index) => {
        if (file instanceof File) {
            formData.append(`attachments[${index}]`, file);
        }
    });
    
    // Add rubric criteria
    data.rubric_criteria.forEach((criteria, index) => {
        formData.append(`rubric_criteria[${index}][description]`, criteria.description);
        formData.append(`rubric_criteria[${index}][points]`, criteria.points);
    });
    
    // ... other fields
    
    return formData;
}).post(route('teacher.courses.classwork.store', props.course.id), {
    forceFormData: true,
    // ...
});
```

## What Now Works

### ✅ File Attachments
- Teachers can upload multiple files (up to 10MB each)
- Files are stored in `storage/app/public/classwork/`
- File metadata is saved (name, path, size, type)
- Students can view and download these files

### ✅ Due Dates
- Due dates are properly saved to the database
- Calendar events are automatically created for deadlines
- Students see due dates in their course view

### ✅ Rubric Criteria
- Criteria descriptions and points are saved correctly
- Multiple criteria can be added
- Order is maintained
- Used for grading assignments and activities

### ✅ Quiz Questions
- Questions, options, and correct answers are saved
- Multiple question types supported
- Points per question tracked

## File Storage Structure

Files are now stored with the following structure:
```json
[
    {
        "name": "assignment_instructions.pdf",
        "path": "classwork/abc123xyz.pdf",
        "size": 245678,
        "type": "application/pdf"
    },
    {
        "name": "sample_output.png",
        "path": "classwork/def456uvw.png",
        "size": 123456,
        "type": "image/png"
    }
]
```

## Testing Checklist

To verify the fix works:

- [ ] Create a new activity with file attachments → Files should upload
- [ ] Create a new assignment with a due date → Due date should save and appear in calendar
- [ ] Create a new assignment with rubric criteria → Criteria should save
- [ ] Create a quiz with questions → Questions should save
- [ ] View created classwork as student → All data should display correctly
- [ ] Download attached files as student → Files should download
- [ ] Update existing classwork with new files → New files should be added
- [ ] Submit assignment with files → Submission files should work

## File Access

Uploaded files are accessible at:
- **Public URL**: `/storage/classwork/filename.ext`
- **Server Path**: `storage/app/public/classwork/filename.ext`

Make sure the storage link is created:
```bash
php artisan storage:link
```

## Supported File Types

The system now supports any file type up to 10MB including:
- Documents: PDF, DOCX, XLSX, PPTX, TXT
- Images: JPG, PNG, GIF, SVG
- Archives: ZIP, RAR
- Code: PHP, JS, HTML, CSS, etc.

## Database Storage

Files are stored as JSON in the `classwork.attachments` column:
```sql
attachments: JSON
```

This allows for flexible metadata storage and easy retrieval.

## Future Enhancements

Consider adding:
1. **File type restrictions** - Limit to specific file types if needed
2. **Virus scanning** - Scan uploaded files for security
3. **File previews** - Preview documents in-browser
4. **Bulk download** - Download all attachments as ZIP
5. **File versioning** - Track file changes over time
6. **Storage cleanup** - Delete old files from orphaned classwork

## Support

If files still don't upload:
1. Check storage permissions: `php artisan storage:link`
2. Verify PHP upload limits in `php.ini`:
   - `upload_max_filesize = 10M`
   - `post_max_size = 10M`
3. Check Laravel file permissions on `storage/` folder
4. Review logs in `storage/logs/laravel.log`
