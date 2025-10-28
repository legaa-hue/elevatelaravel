# Programs and Course Templates Implementation

## Overview
Implemented a hierarchical course management system where:
- **Admins** create Programs and Course Templates
- **Teachers** create courses by selecting a Program and Template (auto-fills course title and units)

## Database Structure

### Programs Table
- `id` - Primary Key
- `name` - Program name (e.g., "MASTER OF ARTS IN EDUCATION")
- `description` - Optional program description
- `status` - Enum: 'Active' or 'Inactive'
- `timestamps`

### Course Templates Table
- `id` - Primary Key
- `program_id` - Foreign Key to programs table
- `course_code` - Course identifier (e.g., "EDUC501")
- `course_name` - Full course name (e.g., "Educational Psychology")
- `units` - Integer 1-12
- `status` - Enum: 'Active' or 'Inactive'
- `timestamps`

### Courses Table Updates
Added three new columns:
- `program_id` - Foreign Key to programs table (nullable)
- `course_template_id` - Foreign Key to course_templates table (nullable)
- `units` - Integer (nullable)

## Relationships
- Program `hasMany` CourseTemplates
- Program `hasMany` Courses
- CourseTemplate `belongsTo` Program
- CourseTemplate `hasMany` Courses
- Course `belongsTo` Program
- Course `belongsTo` CourseTemplate

## Admin Features

### Programs Management (`/admin/programs`)
- **List View**: Shows all programs with course template count
- **Create**: Add new program with name, description, and status
- **Edit**: Update program details
- **Delete**: Remove program (with confirmation)
- **Status**: Toggle between Active/Inactive

### Course Templates Management (`/admin/course-templates`)
- **List View**: Shows all course templates with program, code, name, and units
- **Create**: 
  - Select Program (dropdown of active programs)
  - Enter Course Code (e.g., "IT101")
  - Enter Course Name (e.g., "Introduction to Computing")
  - Enter Units (1-12 validation)
  - Set Status (Active/Inactive)
- **Edit**: Update template details
- **Delete**: Remove template (with confirmation)
- **Filtering**: Only active programs shown in dropdown

## Teacher Features

### Course Creation (`/teacher/my-courses`)
- **Enhanced Create Course Modal**:
  1. Select Academic Year (dropdown of active years)
  2. Select Program (dropdown of active programs)
  3. Select Course Template (auto-loaded based on program, shows code, name, and units)
  4. Course Title (auto-filled from template - read-only)
  5. Units (auto-filled from template - read-only)
  6. Enter Section (required, e.g., "Section A")
  7. Enter Description (optional)

### API Endpoint
- `GET /teacher/programs/{program}/course-templates`
  - Returns JSON array of active course templates for the selected program
  - Used for cascading dropdown (select program → load templates)

## Frontend Components

### Admin Components
1. **Programs.vue** (`resources/js/Pages/Admin/Programs.vue`)
   - Full CRUD interface
   - Modal-based create/edit forms
   - Delete confirmation dialog
   - Status badges (green for Active, red for Inactive)

2. **CourseTemplates.vue** (`resources/js/Pages/Admin/CourseTemplates.vue`)
   - Full CRUD interface
   - Program selection dropdown
   - Units input with validation (1-12)
   - Course code and name fields
   - Status management

### Teacher Components
1. **MyCourses.vue** (`resources/js/Pages/Teacher/MyCourses.vue`)
   - Course listing grid
   - Enhanced create course modal with:
     - Academic year selection
     - Program selection (triggers template loading)
     - Course template selection (auto-fills title and units)
     - Section and description inputs
   - Axios integration for dynamic template loading
   - Vue watchers for cascading dropdowns

## Backend Controllers

### Admin Controllers
1. **ProgramController.php** (`app/Http/Controllers/Admin/ProgramController.php`)
   - `index()` - List all programs with course template count
   - `store()` - Create new program
   - `update()` - Update program
   - `destroy()` - Delete program
   - `getCourseTemplates($programId)` - API endpoint for templates

2. **CourseTemplateController.php** (`app/Http/Controllers/Admin/CourseTemplateController.php`)
   - `index()` - List all templates with program relationship
   - `store()` - Create new template (validates units 1-12)
   - `update()` - Update template
   - `destroy()` - Delete template

### Teacher Controllers
1. **CourseController.php** (`app/Http/Controllers/Teacher/CourseController.php`)
   - `index()` - Updated to pass programs and academicYears props
   - `store()` - Modified to accept program_id and course_template_id, auto-fills title and units from template

## Routes

### Admin Routes
```php
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('programs', ProgramController::class);
    Route::resource('course-templates', CourseTemplateController::class);
});
```

### Teacher Routes
```php
Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'teacher'])->group(function () {
    Route::get('/programs/{program}/course-templates', [ProgramController::class, 'getCourseTemplates'])
        ->name('programs.course-templates');
});
```

## Sample Data (ProgramSeeder)

### Programs Created
1. **MASTER OF ARTS IN EDUCATION**
   - 4 course templates (EDUC501-504)
   
2. **BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY**
   - 6 course templates (IT101, IT102, IT201, IT202, IT301, IT302)
   
3. **BACHELOR OF SCIENCE IN COMPUTER SCIENCE**
   - 6 course templates (CS101, CS102, CS201, CS202, CS301, CS302)

All templates have 3 units and Active status.

## Status Values
**Important**: Both Programs and CourseTemplates use capitalized enum values:
- `'Active'` (not 'active')
- `'Inactive'` (not 'inactive')

## Workflow

### Admin Workflow
1. Navigate to `/admin/programs`
2. Click "Add Program" → Enter name, description, status → Create
3. Navigate to `/admin/course-templates`
4. Click "Add Course Template" → Select program → Enter code, name, units → Create
5. Repeat for all courses in the program

### Teacher Workflow
1. Navigate to `/teacher/my-courses`
2. Click "Create Course"
3. Select Academic Year
4. Select Program (e.g., "BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY")
5. Templates automatically load in dropdown
6. Select Template (e.g., "IT101 - Introduction to Computing (3 units)")
7. Course title and units auto-fill
8. Enter Section (e.g., "BSIT-1A")
9. Optionally add description
10. Click "Create Course"

## Files Modified/Created

### Migrations
- `2025_10_28_070633_create_course_templates_table.php`
- `2025_10_28_070753_add_program_and_template_to_courses_table.php`

### Models
- `app/Models/Program.php` (updated)
- `app/Models/CourseTemplate.php` (created)
- `app/Models/Course.php` (updated)

### Controllers
- `app/Http/Controllers/Admin/ProgramController.php` (created)
- `app/Http/Controllers/Admin/CourseTemplateController.php` (created)
- `app/Http/Controllers/Teacher/CourseController.php` (updated)
- `app/Http/Controllers/Teacher/DashboardController.php` (updated)

### Views
- `resources/js/Pages/Admin/Programs.vue` (created)
- `resources/js/Pages/Admin/CourseTemplates.vue` (created)
- `resources/js/Pages/Teacher/MyCourses.vue` (created)
- `resources/js/Layouts/AdminLayout.vue` (updated - added menu items)

### Routes
- `routes/web.php` (updated)

### Seeders
- `database/seeders/ProgramSeeder.php` (created)

## Testing Checklist
- [ ] Admin can create, edit, delete programs
- [ ] Admin can create, edit, delete course templates
- [ ] Course templates filtered by program
- [ ] Teacher sees programs and academic years in dropdown
- [ ] Selecting program loads course templates via API
- [ ] Selecting template auto-fills course title and units
- [ ] Teacher can successfully create course with template
- [ ] Created course shows correct title, units, program, and template associations
- [ ] Only Active programs/templates appear in dropdowns
- [ ] Units validation works (1-12)

## Future Enhancements
- Bulk import course templates from CSV
- Copy course templates between programs
- Course prerequisite management
- Program curriculum visualization
- Course template versioning
- Academic year association with programs
