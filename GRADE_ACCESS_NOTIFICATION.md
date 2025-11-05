# Grade Access Request Notification Implementation

## Summary

Implemented a complete notification system for when students request access to view their grades.

## What Was Added

### 1. **New Notification Class**
**File:** `app/Notifications/GradeAccessRequestNotification.php`

Sends notifications to teachers when a student requests grade access:
- **Push notification** with title "Grade Access Request"
- **Database notification** stored in Laravel's notifications table
- **Action button** links to the course view page
- **Includes:** Student name, course name, direct link to review request

### 2. **Updated Student Controller**
**File:** `app/Http/Controllers/Student/CourseController.php`

Modified `requestGradeAccess()` method to:
- Load the course
- Find the teacher
- Send notification using `$teacher->notify(new GradeAccessRequestNotification(...))`
- Show success message to student

### 3. **Updated Notification Controller**
**File:** `app/Http/Controllers/NotificationController.php`

Fixed to use Laravel's built-in notification system instead of custom model:
- `index()` - Get all notifications
- `unreadCount()` - Get count of unread notifications
- `markAsRead()` - Mark single notification as read
- `markAllAsRead()` - Mark all as read
- `destroy()` - Delete notification

### 4. **Added Notification Routes**
**File:** `routes/web.php`

Added routes in teacher middleware group:
```php
GET  /teacher/notifications                    - Get all notifications
GET  /teacher/notifications/unread-count       - Get unread count
POST /teacher/notifications/{id}/read          - Mark as read
POST /teacher/notifications/mark-all-read      - Mark all as read
DELETE /teacher/notifications/{id}             - Delete notification
```

## How It Works

### Flow:
1. **Student clicks "Request Access"** on their grades page
2. **System updates** `grade_access_requested = true` in `joined_courses` table
3. **Notification sent** to teacher via Laravel's notification system
4. **Teacher receives:**
   - Push notification (if subscribed to web push)
   - Database notification (shown in bell icon dropdown)
5. **Teacher clicks notification** → Goes to course view page
6. **Teacher sees request panel** with student's request
7. **Teacher can approve or deny** the request

### Notification Data Structure:
```json
{
    "type": "grade_access_request",
    "student_id": 123,
    "student_name": "John Doe",
    "course_id": 456,
    "course_name": "Advanced Mathematics",
    "message": "John Doe has requested access to view their grades in Advanced Mathematics",
    "url": "/teacher/courses/456"
}
```

## Testing

1. **Login as student** → Go to a course
2. **Click "Request Access to Grades"** button
3. **Login as teacher** (the course teacher)
4. **Click bell icon** in header
5. **Should see:** "John Doe has requested access to view their grades in [Course Name]"
6. **Click notification** → Goes to course view
7. **See request panel** at top of page with approve/deny buttons

## Files Modified

- ✅ `app/Notifications/GradeAccessRequestNotification.php` (NEW)
- ✅ `app/Http/Controllers/Student/CourseController.php` (UPDATED)
- ✅ `app/Http/Controllers/NotificationController.php` (UPDATED)
- ✅ `routes/web.php` (UPDATED)

## Deployment

Upload these files to Hostinger:
1. `app/Notifications/GradeAccessRequestNotification.php`
2. `app/Http/Controllers/Student/CourseController.php`
3. `app/Http/Controllers/NotificationController.php`
4. `routes/web.php`

## Notes

- Teacher layout (`TeacherLayout.vue`) already has notification bell icon and dropdown
- Notification system uses Laravel's built-in `notifications` table
- Push notifications require web push subscription
- Database notifications work without push subscription
