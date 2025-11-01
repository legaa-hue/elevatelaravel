# 📎 File Upload System - Visual Guide

## 🎨 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     USER INTERFACE                          │
│  ┌────────────────────────────────────────────────────┐    │
│  │       FileUploadManager.vue Component              │    │
│  │  ┌──────────────┐  ┌──────────────┐               │    │
│  │  │ Drag & Drop  │  │ Click Upload │               │    │
│  │  └──────────────┘  └──────────────┘               │    │
│  │  ┌──────────────────────────────────────────────┐ │    │
│  │  │  📤 Progress Bar (0-100%)                    │ │    │
│  │  └──────────────────────────────────────────────┘ │    │
│  │  ┌──────────────────────────────────────────────┐ │    │
│  │  │  Storage: [Local] [Google] [OneDrive]       │ │    │
│  │  └──────────────────────────────────────────────┘ │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                           ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                      API LAYER                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │           /api/files/* endpoints                   │    │
│  │  • POST /upload          • GET /files              │    │
│  │  • POST /upload-multiple • GET /files/{id}         │    │
│  │  • DELETE /files/{id}    • GET /config             │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                           ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                   BUSINESS LOGIC                            │
│  ┌────────────────────────────────────────────────────┐    │
│  │         FileUploadService.php                      │    │
│  │  • Validation          • Upload to storage         │    │
│  │  • Thumbnail creation  • Metadata extraction       │    │
│  │  • File deletion       • Cloud integration         │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                           ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                   STORAGE LAYER                             │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐               │
│  │  Local   │   │  Google  │   │ OneDrive │               │
│  │ Storage  │   │  Drive   │   │          │               │
│  └──────────┘   └──────────┘   └──────────┘               │
└─────────────────────────────────────────────────────────────┘
                           ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                     DATABASE                                │
│  ┌────────────────────────────────────────────────────┐    │
│  │           file_uploads table                       │    │
│  │  • id, uploadable_type, uploadable_id              │    │
│  │  • file details (name, size, type)                 │    │
│  │  • storage info (disk, provider, path)             │    │
│  │  • metadata (thumbnail, dimensions)                │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 File Upload Flow

```
┌─────────────┐
│   User      │
│  Selects    │
│   File      │
└──────┬──────┘
       │
       ↓
┌─────────────────────────┐
│  Frontend Validation    │
│  • Check file type      │
│  • Check file size      │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Upload to Server       │
│  • Show progress bar    │
│  • FormData + Axios     │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Backend Validation     │
│  • Verify file type     │
│  • Verify file size     │
│  • Authenticate user    │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Process File           │
│  • Generate UUID        │
│  • Create thumbnail     │
│  • Extract metadata     │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Save to Storage        │
│  • Local / Cloud        │
│  • Organize by folder   │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Save to Database       │
│  • Create record        │
│  • Link to model        │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Return Response        │
│  • File URL             │
│  • Thumbnail URL        │
│  • File metadata        │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│  Update UI              │
│  • Show file in list    │
│  • Enable actions       │
└─────────────────────────┘
```

---

## 🎯 Component Integration Map

```
┌──────────────────────────────────────────────────────────┐
│                  Your Application                         │
│                                                           │
│  ┌────────────────────────────────────────────────┐     │
│  │  Student: Assignment Submission                │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  Answer Textarea                         │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  📎 FileUploadManager Component          │  │     │
│  │  │    (uploadable_type: ClassworkSubmission)│  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  [Submit Assignment] Button              │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  └────────────────────────────────────────────────┘     │
│                                                           │
│  ┌────────────────────────────────────────────────┐     │
│  │  Teacher: Create Classwork                     │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  Title Input                             │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  Description Textarea                    │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  📎 FileUploadManager Component          │  │     │
│  │  │    (uploadable_type: Classwork)          │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  │  ┌──────────────────────────────────────────┐  │     │
│  │  │  [Create Assignment] Button              │  │     │
│  │  └──────────────────────────────────────────┘  │     │
│  └────────────────────────────────────────────────┘     │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

---

## 💾 Database Relationships

```
┌──────────────────┐
│  Classwork       │
│  ─────────────   │
│  id              │
│  title           │
│  description     │
└────────┬─────────┘
         │ has many
         │ (polymorphic)
         ↓
┌──────────────────┐
│  FileUpload      │◄──────┐
│  ─────────────   │       │
│  id              │       │ belongs to
│  uploadable_type │       │
│  uploadable_id   │───────┘
│  user_id         │
│  file_path       │
│  mime_type       │
│  size            │
└────────┬─────────┘
         │ belongs to
         ↓
┌──────────────────┐
│  User            │
│  ─────────────   │
│  id              │
│  name            │
│  email           │
└──────────────────┘


┌─────────────────────────┐
│  ClassworkSubmission    │
│  ───────────────────    │
│  id                     │
│  classwork_id           │
│  student_id             │
│  submission_content     │
└────────┬────────────────┘
         │ has many
         │ (polymorphic)
         ↓
┌──────────────────┐
│  FileUpload      │
│  ─────────────   │
│  id              │
│  uploadable_type │
│  uploadable_id   │
│  file_path       │
└──────────────────┘
```

---

## 🔄 File Lifecycle

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Uploaded │────▶│  Active  │────▶│  Viewed  │────▶│ Deleted  │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
     │                 │                 │               │
     ↓                 ↓                 ↓               ↓
┌──────────────────────────────────────────────────────────────┐
│  Database States:                                            │
│  • created_at: Timestamp of upload                           │
│  • updated_at: Last modification                             │
│  • deleted_at: Soft delete (NULL = active)                   │
└──────────────────────────────────────────────────────────────┘
```

---

## 📁 File Storage Structure

```
storage/
└── app/
    └── public/
        ├── classworks/
        │   ├── 1/
        │   │   ├── abc123-uuid.pdf
        │   │   ├── def456-uuid.jpg
        │   │   └── thumb_def456-uuid.jpg
        │   └── 2/
        │       └── ghi789-uuid.docx
        └── classworksubmissions/
            ├── 1/
            │   ├── jkl012-uuid.pdf
            │   └── mno345-uuid.png
            └── 2/
                └── pqr678-uuid.mp4

public/
└── storage/ → symlink to storage/app/public
    ├── classworks/
    └── classworksubmissions/
```

---

## 🎨 UI Component Structure

```
FileUploadManager.vue
│
├── Upload Zone
│   ├── Drag & Drop Area
│   ├── Click to Upload
│   ├── Progress Bar
│   └── Upload Spinner
│
├── Storage Selector
│   ├── [Local Storage] button
│   ├── [Google Drive] button
│   └── [OneDrive] button
│
└── File List
    └── For each file:
        ├── Thumbnail/Icon
        ├── File Name
        ├── File Size
        ├── Upload Date
        ├── Uploader Name
        └── Actions
            ├── 👁️ View
            ├── ⬇️ Download
            └── 🗑️ Delete
```

---

## 🔒 Validation Flow

```
┌─────────────────┐
│  File Selected  │
└────────┬────────┘
         │
         ↓
    ┌─────────┐
    │ Is file │ NO
    │ type    │────▶ ❌ Reject
    │ valid?  │
    └────┬────┘
         │ YES
         ↓
    ┌─────────┐
    │ Is file │ NO
    │ size    │────▶ ❌ Reject
    │ valid?  │
    └────┬────┘
         │ YES
         ↓
    ┌─────────┐
    │ Is user │ NO
    │ authed? │────▶ ❌ Reject
    └────┬────┘
         │ YES
         ↓
┌────────────────┐
│ ✅ Process     │
│    Upload      │
└────────────────┘
```

---

## 📊 Supported File Types Matrix

```
┌─────────────┬──────────┬─────────────────────────────┐
│  Category   │ Max Size │  Extensions                 │
├─────────────┼──────────┼─────────────────────────────┤
│  Images     │  10 MB   │  .jpg, .png, .gif,          │
│             │          │  .webp, .svg                │
├─────────────┼──────────┼─────────────────────────────┤
│  Videos     │ 100 MB   │  .mp4, .mpeg, .mov,         │
│             │          │  .avi, .webm                │
├─────────────┼──────────┼─────────────────────────────┤
│  Documents  │  25 MB   │  .pdf, .doc, .docx,         │
│             │          │  .xls, .xlsx, .ppt,         │
│             │          │  .pptx, .txt                │
└─────────────┴──────────┴─────────────────────────────┘
```

---

## 🚀 Quick Integration Code

### 1. In Your Vue Page:
```vue
<FileUploadManager
  uploadable-type="App\Models\ClassworkSubmission"
  :uploadable-id="123"
/>
```

### 2. In Your Controller:
```php
$files = ClassworkSubmission::find(123)->fileUploads;
```

### 3. In Your API:
```bash
curl -X POST /api/files/upload \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@document.pdf" \
  -F "uploadable_type=App\Models\Classwork" \
  -F "uploadable_id=1"
```

---

## 📈 System Metrics

```
┌────────────────────────────────────────────────┐
│  Performance Targets                           │
├────────────────────────────────────────────────┤
│  Upload Speed:    Depends on file size        │
│  Thumbnail Gen:   < 2 seconds                 │
│  File Retrieval:  < 500ms                     │
│  Delete Operation: < 1 second                 │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│  Storage Capacity                              │
├────────────────────────────────────────────────┤
│  Local:     Limited by server disk space      │
│  Google:    15 GB free (more with paid)       │
│  OneDrive:  5 GB free (more with paid)        │
└────────────────────────────────────────────────┘
```

---

## ✅ Feature Checklist

```
Backend:
✅ Database migration
✅ FileUpload model
✅ FileUploadService
✅ FileUploadController
✅ API routes
✅ Validation logic
✅ Thumbnail generation
✅ Cloud storage ready

Frontend:
✅ FileUploadManager component
✅ Drag & drop
✅ Progress indicator
✅ File list display
✅ Download action
✅ Delete action
✅ Storage selector
✅ Error handling

Documentation:
✅ Implementation guide
✅ Quick reference
✅ Visual guide
✅ API documentation
✅ Integration examples
```

---

## 🎓 Learning Resources

```
┌──────────────────────────────────────────┐
│  📚 Documentation Files                  │
├──────────────────────────────────────────┤
│  1. FILE_UPLOAD_IMPLEMENTATION.md        │
│     → Full technical documentation       │
│                                          │
│  2. FILE_UPLOAD_QUICK_REFERENCE.md       │
│     → Quick start guide                  │
│                                          │
│  3. FILE_UPLOAD_SUMMARY.md               │
│     → Implementation summary             │
│                                          │
│  4. FILE_UPLOAD_VISUAL_GUIDE.md          │
│     → This file (visual diagrams)        │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│  🧪 Testing Resources                    │
├──────────────────────────────────────────┤
│  • Demo Page: /file-upload-demo          │
│  • API Endpoint: /api/files/*            │
│  • Example Components: FileUploadDemo    │
└──────────────────────────────────────────┘
```

---

## 🎉 Success!

Your file upload system is **fully implemented** and **ready to use**!

```
     ┌─────────────────────────────────┐
     │   ✅ File Upload System         │
     │   🎉 100% Complete              │
     │   🚀 Production Ready           │
     └─────────────────────────────────┘
              │
              ├─── 📦 Backend Complete
              ├─── 🎨 Frontend Complete
              ├─── 📚 Documentation Complete
              └─── 🧪 Testing Ready
```

**Now you can:**
- Upload files to assignments
- Attach materials to classwork
- Preview images with thumbnails
- Download and manage files
- Integrate with cloud storage (optional)

**Happy uploading! 🚀📎**
