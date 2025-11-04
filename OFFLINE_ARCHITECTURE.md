# Teacher Offline Mode - Architecture Overview

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Teacher UI Layer                          │
│  (Dashboard, Calendar, Courses, Classwork, Gradebook, etc.)     │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                   Vue Composables Layer                          │
├─────────────────────┬───────────────────┬───────────────────────┤
│  useOfflineSync()   │ useTeacherOffline()│  useOfflineFiles()   │
│  - Connection       │  - Teacher Ops     │  - File Management   │
│  - Sync Engine      │  - Cache Ops       │  - Downloads         │
│  - Queue Manager    │  - Smart Fetch     │  - Progress          │
└─────────────────────┴───────────────────┴───────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                   OfflineSyncIndicator                           │
│  (Visual Status: Offline, Syncing, Success, Pending)            │
└─────────────────────────────────────────────────────────────────┘
                         │
          ┌──────────────┴──────────────┐
          ↓                             ↓
┌──────────────────────┐      ┌──────────────────────┐
│   IndexedDB Storage  │      │  Service Worker      │
│  - offline-storage   │      │  - Workbox Cache     │
│  - 12 Object Stores  │      │  - Network Caching   │
│  - Pending Actions   │      │  - File Caching      │
└──────────────────────┘      └──────────────────────┘
          │                             │
          └──────────────┬──────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Backend API Layer                           │
│     (Laravel Routes, Controllers, Database)                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow Diagrams

### When Online (Normal Operation)

```
User Action
    │
    ↓
Vue Component
    │
    ├──→ API Request ──→ Backend ──→ Database
    │                      ↓
    │                   Response
    │                      ↓
    ├──→ Cache Data ──→ IndexedDB
    │
    └──→ Download Files ──→ Service Worker Cache
```

### When Offline (Queued Operation)

```
User Action
    │
    ↓
Vue Component
    │
    ├──→ Check isOnline (false)
    │
    ├──→ Save to IndexedDB
    │       ├── Data Store
    │       └── Pending Actions Queue
    │
    ├──→ Show Offline Indicator
    │
    └──→ Notify User ("Saved offline")
```

### When Connection Restored (Auto-Sync)

```
Connection Restored Event
    │
    ↓
useOfflineSync detects online
    │
    ├──→ Get Pending Actions from IndexedDB
    │
    ├──→ Show "Syncing..." Indicator
    │
    ├──→ Process Each Action
    │       │
    │       ├──→ API Request ──→ Backend
    │       │                      ↓
    │       │                   Success
    │       │                      ↓
    │       └──→ Remove from Queue
    │
    ├──→ Update Cached Data
    │
    └──→ Show Success Message
```

---

## 📊 IndexedDB Schema

```
ElevateGS_Offline Database (v2)
│
├── courses [id]
│   ├── teacher_id (index)
│   ├── updated_at (index)
│   └── synced (boolean)
│
├── classwork [id]
│   ├── course_id (index)
│   ├── due_date (index)
│   └── attachments (array)
│
├── events [id]
│   ├── user_id (index)
│   ├── date (index)
│   └── synced (boolean)
│
├── gradebooks [course_id]
│   ├── data (JSON)
│   ├── updated_at (index)
│   └── synced (boolean)
│
├── materials [id]
│   ├── course_id (index)
│   └── attachments (array)
│
├── students [id]
│   └── course_id (index)
│
├── submissions [id]
│   ├── classwork_id (index)
│   └── student_id (index)
│
├── reports [id]
│   ├── type (index)
│   └── cached_at (index)
│
├── fileCache [url]
│   ├── data (base64)
│   ├── cached_at (index)
│   └── course_id (index)
│
├── dashboardCache [key]
│   └── cached_at (index)
│
├── pendingActions [id] (auto-increment)
│   ├── type (index)
│   ├── data (JSON)
│   ├── timestamp (index)
│   └── synced (boolean)
│
├── notifications [id]
│   └── created_at (index)
│
├── grades [id]
│   ├── user_id (index)
│   └── course_id (index)
│
└── user [id]
```

---

## 🎯 Component Integration Flow

```
TeacherLayout.vue
    │
    ├── OfflineSyncIndicator (global indicator)
    │       │
    │       └── Watches: isOnline, isSyncing, syncStatus
    │
    └── Router View
            │
            ├── Dashboard.vue
            │       └── Uses: cacheDashboardData, getCachedDashboard
            │
            ├── Calendar.vue
            │       └── Uses: createEventOffline, cacheEvents
            │
            ├── Courses/Index.vue
            │       └── Uses: createCourseOffline, getCachedCourses
            │
            ├── Courses/Show.vue
            │       └── Uses: downloadCourseFiles, createMaterialOffline
            │
            ├── Classwork/*.vue
            │       └── Uses: createClassworkOffline, downloadAttachments
            │
            ├── Gradebook.vue
            │       └── Uses: updateGradebookOffline, getCachedGradebook
            │
            └── Reports.vue
                    └── Uses: cacheReport, getCachedReport
```

---

## 🔐 Sync Action Types

```javascript
Supported Action Types:
├── create_course
├── update_course
├── create_event
├── update_event
├── delete_event
├── create_classwork
├── update_classwork
├── create_material
├── update_gradebook
├── grade_submission
└── custom (with endpoint)
```

---

## 📁 File Caching Strategy

```
Service Worker Cache Strategy:
│
├── API Routes (NetworkFirst)
│   ├── Timeout: 10s
│   └── Fallback: Cache
│
├── Teacher Data (NetworkFirst)
│   ├── TTL: 10 minutes
│   └── Max Entries: 100
│
├── File Attachments (CacheFirst)
│   ├── TTL: 30 days
│   ├── Max Entries: 300
│   └── Max Size: 50MB per file
│
├── Submissions (CacheFirst)
│   ├── TTL: 30 days
│   └── Max Entries: 200
│
└── Static Assets (StaleWhileRevalidate)
    ├── TTL: 7 days
    └── Max Entries: 100
```

---

## 🎬 Sequence Diagrams

### Create Course Offline → Sync

```
User          Component          Composable        IndexedDB         API
 │                │                  │                 │              │
 │  Click Create  │                  │                 │              │
 │───────────────→│                  │                 │              │
 │                │  Check Online    │                 │              │
 │                │─────────────────→│                 │              │
 │                │  (Offline)       │                 │              │
 │                │←─────────────────│                 │              │
 │                │  Save Offline    │                 │              │
 │                │─────────────────→│  Save Course    │              │
 │                │                  │────────────────→│              │
 │                │                  │  Queue Action   │              │
 │                │                  │────────────────→│              │
 │  "Saved        │                  │                 │              │
 │   Offline"     │                  │                 │              │
 │←───────────────│                  │                 │              │
 │                │                  │                 │              │
 │  [Connection Restored]            │                 │              │
 │                │                  │                 │              │
 │                │  Auto Sync       │                 │              │
 │                │─────────────────→│  Get Pending    │              │
 │                │                  │────────────────→│              │
 │                │                  │  Actions        │              │
 │                │                  │←────────────────│              │
 │                │                  │  POST /courses  │              │
 │                │                  │─────────────────────────────→│
 │                │                  │  Success        │              │
 │                │                  │←─────────────────────────────│
 │                │                  │  Remove Queue   │              │
 │                │                  │────────────────→│              │
 │  "Synced ✓"    │                  │                 │              │
 │←───────────────│                  │                 │              │
```

### View Cached Data Offline

```
User          Component          Composable        IndexedDB      Service Worker
 │                │                  │                 │                 │
 │  Load Page     │                  │                 │                 │
 │───────────────→│                  │                 │                 │
 │                │  Check Online    │                 │                 │
 │                │─────────────────→│                 │                 │
 │                │  (Offline)       │                 │                 │
 │                │←─────────────────│                 │                 │
 │                │  Get Cached      │                 │                 │
 │                │─────────────────→│  Query Store    │                 │
 │                │                  │────────────────→│                 │
 │                │                  │  Return Data    │                 │
 │                │  Data            │←────────────────│                 │
 │                │←─────────────────│                 │                 │
 │                │  Get Files       │                 │                 │
 │                │─────────────────────────────────────────────────→│
 │                │                  │                 │  Cached Files   │
 │                │  Files           │                 │                 │
 │                │←─────────────────────────────────────────────────│
 │  Display       │                  │                 │                 │
 │  Cached Data   │                  │                 │                 │
 │←───────────────│                  │                 │                 │
```

---

## 🚦 State Management

```
Application States:

Online + No Pending
├── Status: All Good ✓
├── Icon: cloud_done
└── Action: Normal operations

Online + Has Pending
├── Status: Syncing...
├── Icon: cloud_upload (click to sync)
└── Action: Show pending count

Syncing
├── Status: Uploading changes
├── Icon: sync (spinning)
└── Action: Show progress

Offline
├── Status: You're offline
├── Icon: cloud_off
└── Action: Queue operations

Sync Success
├── Status: Successfully synced X changes
├── Icon: check_circle
├── Duration: 5 seconds
└── Action: Auto-dismiss

Sync Error
├── Status: Sync failed
├── Icon: error
├── Duration: 5 seconds
└── Action: Will retry
```

---

## 🎨 UI States by Section

```
Dashboard
├── Online: Fresh data
├── Offline: "📦 Cached data" banner
└── Syncing: Normal display

Calendar
├── Online: Normal calendar
├── Offline: "Changes sync when online"
├── Creating: Save to queue
└── Syncing: Show progress

Courses
├── Online: Full access
├── Offline: View only cached
├── Creating: Queue for sync
└── Files: Auto-download

Gradebook
├── Online: Real-time save
├── Offline: "⚠ Unsaved changes"
├── Editing: Local changes
└── Syncing: Upload on connection

Classwork
├── Online: Full CRUD
├── Offline: Create/edit queued
├── Files: Background download
└── Syncing: Show indicator
```

---

## 🔢 Performance Metrics

```
Operation           | Target Time    | Actual (Typical)
--------------------|----------------|------------------
Cache Read          | < 50ms         | ~10-20ms
Cache Write         | < 100ms        | ~30-50ms
File Download       | Varies         | Background
Sync Single Action  | < 2s           | ~500ms-1s
Sync Multiple       | < 10s          | Varies
IndexedDB Query     | < 50ms         | ~5-15ms
Service Worker      | < 100ms        | ~20-50ms
```

---

## 📦 Storage Estimates

```
Data Type            | Avg Size     | Max Count  | Total
---------------------|--------------|------------|--------
Course               | ~2KB         | 20         | ~40KB
Event                | ~1KB         | 100        | ~100KB
Classwork            | ~3KB         | 200        | ~600KB
Gradebook            | ~50KB        | 20         | ~1MB
Submission           | ~5KB         | 500        | ~2.5MB
Student              | ~1KB         | 1000       | ~1MB
File (cached)        | ~500KB       | 300        | ~150MB
Dashboard            | ~10KB        | 1          | ~10KB
Reports              | ~20KB        | 10         | ~200KB
---------------------|--------------|------------|--------
Total (typical user) |              |            | ~155MB
```

---

## 🛠️ Development Tools

```
Browser DevTools:

Application Tab
├── IndexedDB → ElevateGS_Offline
│   └── View all stores and data
├── Service Workers
│   └── Check activation status
└── Cache Storage
    └── View cached files

Network Tab
├── Throttling → Offline
└── Test offline mode

Console
├── Monitor sync operations
└── Debug errors
```

---

This architecture provides a robust, scalable offline-first experience for teachers!
