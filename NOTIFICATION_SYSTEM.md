# Notification System Documentation

## Overview
The notification system has been implemented for both **Teacher** and **Student** dashboards. It provides real-time notifications for various activities within the ElevateGS platform.

## Features Implemented

### 🔔 For Teachers
Teachers receive notifications for:
- **Student Submissions**: When a student submits an assignment, quiz, or activity
- **Admin Announcements**: System-wide announcements from administrators

### 🔔 For Students
Students receive notifications for:
- **New Materials**: When teachers post new lessons, assignments, quizzes, or activities
- **Admin Announcements**: System-wide announcements from administrators
- **Teacher Announcements**: Course-specific announcements from teachers

## Technical Implementation

### Backend Components

#### 1. Database
- **Table**: `notifications`
- **Migration**: `2025_10_28_000000_create_notifications_table.php`
- **Columns**:
  - `id`: Primary key
  - `user_id`: Foreign key to users table
  - `type`: Notification type (submission, announcement, material, classwork)
  - `title`: Notification title
  - `message`: Notification message
  - `data`: JSON field for additional data (course_id, classwork_id, url, etc.)
  - `is_read`: Boolean flag for read status
  - `read_at`: Timestamp when marked as read
  - `created_at`, `updated_at`: Timestamps

#### 2. Models
- **Location**: `app/Models/Notification.php`
- **Features**:
  - Relationships with User model
  - Scopes for `unread()` and `read()` notifications
  - `markAsRead()` method
  - JSON casting for `data` field

#### 3. Controllers
- **Location**: `app/Http/Controllers/NotificationController.php`
- **Endpoints**:
  - `GET /notifications` - Get all notifications
  - `GET /notifications/unread-count` - Get unread count
  - `POST /notifications/{id}/read` - Mark as read
  - `POST /notifications/read-all` - Mark all as read
  - `DELETE /notifications/{id}` - Delete notification

#### 4. Service Layer
- **Location**: `app/Services/NotificationService.php`
- **Methods**:
  - `notifyTeacherAboutSubmission()` - Notify teacher when student submits
  - `notifyStudentsAboutClasswork()` - Notify students about new materials
  - `notifyTeachersAboutAnnouncement()` - Notify all teachers
  - `notifyStudentsAboutAnnouncement()` - Notify students
  - `notifyUser()` - Generic notification method

#### 5. Integration Points
- **Student Submission**: `app/Http/Controllers/Student/CourseController.php@submitClasswork`
- **Teacher Classwork Creation**: `app/Http/Controllers/Teacher/ClassworkController.php@store`

### Frontend Components

#### 1. StudentLayout.vue
**Location**: `resources/js/Layouts/StudentLayout.vue`

**Features**:
- Notification bell icon with unread count badge
- Dropdown panel showing all notifications
- Color-coded notification icons based on type:
  - 🔵 Blue: New materials/classwork
  - 🟡 Yellow: Announcements
- Click to navigate to related content
- Mark as read functionality
- Delete notifications
- Auto-refresh every 30 seconds
- Time ago display (e.g., "5 minutes ago")

#### 2. TeacherLayout.vue
**Location**: `resources/js/Layouts/TeacherLayout.vue`

**Features**:
- Notification bell icon with unread count badge
- Dropdown panel showing all notifications
- Color-coded notification icons based on type:
  - 🟢 Green: Student submissions
  - 🟡 Yellow: Announcements
- Click to navigate to related content
- Mark as read functionality
- Delete notifications
- Auto-refresh every 30 seconds
- Time ago display

### Routes

#### Student Routes
```php
Route::prefix('student')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
```

#### Teacher Routes
```php
Route::prefix('teacher')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
```

## Usage Examples

### Creating Notifications Programmatically

#### Notify Teacher About Submission
```php
use App\Services\NotificationService;

// In your controller
NotificationService::notifyTeacherAboutSubmission($classwork, $student);
```

#### Notify Students About New Material
```php
use App\Services\NotificationService;

// After creating classwork
NotificationService::notifyStudentsAboutClasswork($classwork);
```

#### Custom Notification
```php
use App\Services\NotificationService;

NotificationService::notifyUser(
    $userId,
    'custom',
    'Custom Title',
    'Custom message here',
    [
        'url' => '/some-url',
        'additional_data' => 'value'
    ]
);
```

## UI/UX Features

### Notification Badge
- Shows count when there are unread notifications
- Displays "99+" for counts above 99
- Red background for visibility

### Notification Panel
- Maximum height of 500px with scroll
- Unread notifications have light blue background
- Each notification shows:
  - Icon (type-specific)
  - Title
  - Message
  - Time ago
  - Delete button

### Auto-Refresh
- Polls server every 30 seconds for new notifications
- Updates unread count automatically
- Minimal network traffic (only fetches count)

### Click Actions
- Clicking notification marks it as read
- Navigates to related content (course, classwork, etc.)
- Close dropdown automatically

## Future Enhancements

Potential additions to consider:
1. **Email Notifications**: Send email for important notifications
2. **Push Notifications**: Browser push notifications
3. **Notification Preferences**: Let users choose which notifications to receive
4. **Sound Alerts**: Optional sound when new notification arrives
5. **Admin Announcements**: Dedicated admin interface for creating announcements
6. **Notification Categories**: Filter by type (submissions, materials, announcements)
7. **Archive Feature**: Archive old notifications instead of deleting
8. **Read Receipts**: Track when users viewed notifications

## Testing

### Manual Testing Checklist
- [ ] Teacher receives notification when student submits work
- [ ] Student receives notification when teacher posts new material
- [ ] Unread count updates in real-time
- [ ] Clicking notification navigates to correct page
- [ ] Mark as read functionality works
- [ ] Mark all as read works
- [ ] Delete notification works
- [ ] Auto-refresh updates count every 30 seconds
- [ ] Notification dropdown closes when clicking away
- [ ] Mobile responsive design works

## Maintenance

### Database Cleanup
Consider adding a scheduled job to clean old notifications:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Delete notifications older than 90 days
    $schedule->call(function () {
        Notification::where('created_at', '<', now()->subDays(90))->delete();
    })->daily();
}
```

## Support
For issues or questions, contact the development team.
