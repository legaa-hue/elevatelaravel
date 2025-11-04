# 📤 Files to Upload to Hostinger

**IMPORTANT:** Don't delete anything on the server. Just upload/replace these files.

---

## 🚀 **QUICK UPLOAD LIST**

### **METHOD 1: Upload Specific Updated Files Only (Faster)**

Upload these files/folders to `public_html/`:

#### 1️⃣ **PWA Icons & Assets** → Upload to `public_html/public/`
```
✅ public/pwa-64x64.png
✅ public/pwa-192x192.png  
✅ public/pwa-512x512.png
✅ public/pwa-maskable-512x512.png
✅ public/screenshot-desktop.png
✅ public/screenshot-mobile.png
✅ public/favicon.ico
```

#### 2️⃣ **Updated Models** → Upload to `public_html/app/Models/`
```
✅ app/Models/Course.php  (admin approval disabled)
```

#### 3️⃣ **Layout Files** → Upload to `public_html/resources/js/Layouts/`
```
✅ resources/js/Layouts/AdminLayout.vue
✅ resources/js/Layouts/TeacherLayout.vue
✅ resources/js/Layouts/StudentLayout.vue
✅ resources/js/Layouts/GuestLayout.vue
```

#### 4️⃣ **Offline Composables** → Upload entire folder to `public_html/resources/js/Composables/`
```
✅ resources/js/Composables/useOfflineSync.js
✅ resources/js/Composables/useTeacherOffline.js
✅ resources/js/Composables/useOfflineFiles.js
```

#### 5️⃣ **Offline Components** → Upload to `public_html/resources/js/Components/`
```
✅ resources/js/Components/OfflineSyncIndicator.vue
✅ resources/js/Components/InstallPWAPrompt.vue
```

#### 6️⃣ **Updated Teacher Pages** → Upload to `public_html/resources/js/Pages/Teacher/`
```
✅ resources/js/Pages/Teacher/Dashboard.vue
✅ resources/js/Pages/Teacher/Calendar.vue
✅ resources/js/Pages/Teacher/MyCourses.vue
✅ resources/js/Pages/Teacher/ClassRecord.vue
✅ resources/js/Pages/Teacher/Gradebook.vue
✅ resources/js/Pages/Teacher/Reports.vue
```

#### 7️⃣ **Configuration Files** → Upload to `public_html/`
```
✅ vite.config.js  (PWA manifest configuration)
✅ package.json  (updated dependencies)
```

#### 8️⃣ **Update Environment** → Edit directly on server
```
📝 Edit public_html/.env (don't upload, edit existing file)
   Update these lines:
   - GOOGLE_REDIRECT_URI=https://elevategradschool.com/auth/google/callback
   - Verify APP_URL=https://elevategradschool.com
   - Verify APP_ENV=production
   - Verify APP_DEBUG=false
```

---

## **METHOD 2: Upload Entire Folders (Easier but Slower)**

If you prefer to upload entire folders:

### Upload these entire folders to `public_html/`:
```
📁 app/Models/                    → Replace entire folder
📁 resources/js/Layouts/          → Replace entire folder
📁 resources/js/Composables/      → Replace entire folder
📁 resources/js/Components/       → Replace entire folder
📁 resources/js/Pages/Teacher/    → Replace entire folder
📁 public/                        → Replace entire folder (includes all PWA icons)
📄 vite.config.js                 → Replace file
📄 package.json                   → Replace file
```

---

## ⚙️ **AFTER UPLOADING - RUN THESE COMMANDS**

### **In Hostinger Terminal (or SSH):**

```bash
# Navigate to your project
cd ~/public_html

# Install/update PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install

# ⭐ BUILD PRODUCTION ASSETS (CRITICAL!)
npm run build

# Run any new migrations
php artisan migrate --force

# Clear and cache everything
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set correct permissions
chmod -R 755 storage bootstrap/cache
```

---

## 📋 **STEP-BY-STEP UPLOAD PROCESS**

### **Step 1: Backup Current Site**
1. In Hostinger File Manager, select `public_html/`
2. Click "Compress" → Creates `public_html.zip`
3. Download `public_html.zip` as backup
4. Download current `.env` file

### **Step 2: Upload PWA Icons**
1. Navigate to `public_html/public/`
2. Click "Upload"
3. Upload all 7 PWA icon files:
   - pwa-64x64.png
   - pwa-192x192.png
   - pwa-512x512.png
   - pwa-maskable-512x512.png
   - screenshot-desktop.png
   - screenshot-mobile.png
   - favicon.ico

### **Step 3: Upload Updated Code**
1. Navigate to `public_html/app/Models/`
2. Upload `Course.php` (replace existing)

3. Navigate to `public_html/resources/js/Layouts/`
4. Upload all 4 layout files (replace existing)

5. Navigate to `public_html/resources/js/`
6. Create `Composables/` folder if not exists
7. Upload all 3 composable files

8. Navigate to `public_html/resources/js/Components/`
9. Upload 2 offline component files

10. Navigate to `public_html/resources/js/Pages/Teacher/`
11. Upload all 6 teacher page files (replace existing)

### **Step 4: Upload Configuration**
1. Navigate to `public_html/`
2. Upload `vite.config.js` (replace)
3. Upload `package.json` (replace)

### **Step 5: Update .env**
1. In File Manager, click on `public_html/.env`
2. Click "Edit"
3. Verify/update these lines:
```env
APP_URL=https://elevategradschool.com
GOOGLE_REDIRECT_URI=https://elevategradschool.com/auth/google/callback
APP_ENV=production
APP_DEBUG=false
```
4. Save

### **Step 6: Open Terminal**
1. In Hostinger panel, find "Advanced" → "Terminal" or "SSH Access"
2. Run the commands from section above

### **Step 7: Test**
1. Visit https://elevategradschool.com
2. Check for install button in header
3. Test PWA installation
4. Test offline mode
5. Create a course (should be Active immediately)

---

## 🎯 **QUICK CHECKLIST**

- [ ] Backup current site
- [ ] Upload 7 PWA icon files to `public/`
- [ ] Upload `Course.php` to `app/Models/`
- [ ] Upload 4 layout files to `resources/js/Layouts/`
- [ ] Upload 3 composable files to `resources/js/Composables/`
- [ ] Upload 2 component files to `resources/js/Components/`
- [ ] Upload 6 teacher pages to `resources/js/Pages/Teacher/`
- [ ] Upload `vite.config.js`
- [ ] Upload `package.json`
- [ ] Edit `.env` file on server
- [ ] Run `npm install`
- [ ] Run `npm run build` ⭐ **CRITICAL**
- [ ] Run `php artisan config:cache`
- [ ] Test site

---

## 🚨 **CRITICAL: Don't Forget!**

### **The Most Important Command:**
```bash
npm run build
```

This command:
- Compiles all Vue components
- Generates service worker
- Creates PWA manifest
- Optimizes assets
- **Makes PWA work!**

Without this, your changes won't be visible!

---

## 📦 **Alternative: Upload ZIP**

If you prefer, you can:

1. **Compress these folders locally:**
   - resources/js/Layouts/
   - resources/js/Composables/
   - resources/js/Components/
   - resources/js/Pages/Teacher/
   - app/Models/
   - public/ (just the PWA icons)

2. **Upload ZIP to server**

3. **Extract in correct locations**

4. **Run build commands**

---

## 🎊 **Summary**

**Upload:** ~30 files (or 7 folders)  
**Edit:** 1 file (.env)  
**Run:** 6 commands in terminal  
**Time:** 20-30 minutes  
**Result:** Fully functional PWA with offline mode! 🚀

---

**Start with METHOD 1 (specific files) for faster, more precise updates.**
