# Export Functionality - Complete List

This document lists all PDF export functions available in the ElevateGS system.

## Teacher Exports

### 1. Class Record - Course View (CourseView.vue)
**Location:** Teacher > My Courses > [Select Course] > Class Record Tab

**Available Exports:**
- **Final Grades PDF** - Complete final grades for all students
  - Route: `teacher.courses.export-final-grades`
  - Controller: `Teacher\CourseViewController@exportFinalGrades`
  - View: `resources/views/pdf/final-grades.blade.php`
  - Contains: Student Name, Student ID, Program, Midterm Grade, Finals Grade, Final Grade, Remarks
  
- **Course Performance PDF** - Overall course performance analytics
  - Route: `teacher.courses.export-course-performance`
  - Controller: `Teacher\CourseViewController@exportCoursePerformance`
  - View: `resources/views/pdf/course-performance.blade.php`
  - Contains: Student Name, Total Submissions, Total Classwork, Average Grade, Performance Percentage
  - Includes: Performance statistics and distribution

- **Class Standings PDF** - Ranked list of students by final grade
  - Route: `teacher.courses.export-class-standings`
  - Controller: `Teacher\CourseViewController@exportClassStandings`
  - View: `resources/views/pdf/class-standings.blade.php`
  - Contains: Rank, Student Name, Student ID, Program, Final Grade
  - Features: Top 3 students highlighted with medals (🥇🥈🥉)

### 2. Class Record - Main Page (ClassRecord.vue)
**Location:** Teacher > Class Record

**Available Exports:**
- **Grade Sheet PDF (View)** - View grade sheet in modal
  - Route: `teacher.class-record.grade-sheet.pdf`
  - Controller: `Teacher\GradeSheetController@viewPdf`
  - View: `resources/views/pdf/grade-sheet.blade.php`
  - Action: Opens in modal viewer

- **Grade Sheet PDF (Download)** - Download grade sheet
  - Route: `teacher.class-record.grade-sheet.download`
  - Controller: `Teacher\GradeSheetController@downloadPdf`
  - View: `resources/views/pdf/grade-sheet.blade.php`
  - Action: Downloads file directly

### 3. Reports Page (Reports.vue)
**Location:** Teacher > Reports

**Note:** Export buttons have been removed from the Reports page as requested. The export functionality is still available in the backend but not exposed in the UI.

**Backend Routes (Available but not in UI):**
- **Report Export (PDF/CSV)** - Export report data
  - Route: `teacher.reports.export`
  - Controller: `Teacher\ReportController@export`
  - View: `resources/views/teacher/report_pdf.blade.php`
  - Formats: PDF, CSV

## Admin Exports

### 1. Class Record - Main Page (ClassRecord.vue)
**Location:** Admin > Class Record

**Available Exports:**
- **Grade Sheet PDF (View)** - View grade sheet in modal
  - Route: `admin.class-record.grade-sheet.pdf`
  - Controller: `Admin\GradeSheetController@viewPdf`
  - View: `resources/views/pdf/grade-sheet.blade.php`
  - Action: Opens in modal viewer

- **Grade Sheet PDF (Download)** - Download grade sheet
  - Route: `admin.class-record.grade-sheet.download`
  - Controller: `Admin\GradeSheetController@downloadPdf`
  - View: `resources/views/pdf/grade-sheet.blade.php`
  - Action: Downloads file directly

### 2. Grade Sheet Page (GradeSheet.vue)
**Location:** Admin > Grade Sheet

**Available Exports:**
- **Grade Sheet PDF (View)** - View grade sheet
  - Route: `admin.class-record.grade-sheet.pdf`
  - Action: Opens in new tab

- **Grade Sheet PDF (Download)** - Download grade sheet
  - Route: `admin.class-record.grade-sheet.download`
  - Action: Downloads file directly

### 3. Reports Page (Reports.vue)
**Location:** Admin > Reports

**Available Exports:**
- **CSV Export** - Export data to CSV format
  - Function: `exportReport('csv')`
  - Action: Downloads CSV file
  
- **Excel Export** - Export data to Excel format
  - Function: `exportReport('excel')`
  - Action: Downloads CSV file (Excel format)
  
- **PDF Export** - Export data to PDF format
  - Function: `exportReport('pdf')`
  - Action: Placeholder (shows alert for implementation)

### 4. Audit Logs Page (AuditLogs.vue)
**Location:** Admin > Audit Logs

**Available Exports:**
- **CSV Export** - Export audit logs to CSV
  - Function: `exportLogs()`
  - Action: Downloads CSV file with filtered logs

## Export Button Styles

### Teacher Exports (CourseView - Class Record Tab)
- **Final Grades**: Red button (bg-red-600)
- **Course Performance**: Blue button (bg-blue-600)
- **Class Standings**: Green button (bg-green-600)

All buttons include:
- Icon (download/chart/medal SVG)
- Hover effect
- Shadow
- Responsive design

## PDF Features

All PDF exports include:
- Professional header with course/system information
- Formatted tables with alternating row colors
- Color-coded data (grades, performance levels)
- Footer with generation details (who, when)
- Proper pagination for large datasets
- Consistent branding and styling

## Technical Details

**PDF Library:** `barryvdh/laravel-dompdf`
**Paper Sizes:**
- Portrait: Final Grades, Class Standings
- Landscape: Grade Sheet, Course Performance

**Views Location:** `resources/views/pdf/`
**Controllers:**
- Teacher: `app/Http/Controllers/Teacher/`
- Admin: `app/Http/Controllers/Admin/`

## Usage Example

```php
// In Controller
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('pdf.final-grades', $data);
$pdf->setPaper('a4', 'portrait');
return $pdf->download('filename.pdf');
```

```javascript
// In Vue Component
const exportFinalGrades = () => {
    window.open(route('teacher.courses.export-final-grades', course.id), '_blank');
};
```

## Future Enhancements

Potential additions:
1. Individual student progress reports
2. Custom date range exports
3. Email PDF functionality
4. Batch export for multiple courses
5. Excel format with formulas
6. Dashboard summary exports
7. Attendance reports
8. Grade distribution charts
