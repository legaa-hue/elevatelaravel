# Hostinger Deployment Guide for PWA Files

## 🏗️ Your Current File Structure

```
c:\Users\lenar\OneDrive\Apps\
├── ElevateGS_LaravelPWA-main/    ← Laravel application (source code)
│   ├── app/
│   ├── database/
│   ├── resources/
│   │   └── views/
│   │       └── app.blade.php     ← UPDATED
│   ├── public/
│   │   ├── manifest.json         ← NEW
│   │   ├── sw.js                 ← UPDATED
│   │   ├── workbox-40c80ae4.js   ← UPDATED
│   │   ├── manifest.webmanifest  ← NEW
│   │   └── build/                ← REBUILT
│   └── ...
└── public_html/                   ← Hostinger public folder (deployed)
    ├── index.php
    ├── .htaccess
    ├── manifest.json              ← NEEDS TO BE ADDED
    ├── sw.js                      ← NEEDS TO BE UPDATED
    ├── workbox-40c80ae4.js        ← NEEDS TO BE ADDED
    ├── manifest.webmanifest       ← NEEDS TO BE ADDED
    └── build/                     ← NEEDS TO BE UPDATED
```

## 📋 Files That Need Uploading

### On Hostinger Server:

```
/domains/elevategradschool.com/
├── public_html/                   ← Public web root
│   ├── manifest.json              ← ADD THIS
│   ├── sw.js                      ← UPDATE THIS
│   ├── workbox-40c80ae4.js        ← ADD THIS
│   ├── manifest.webmanifest       ← ADD THIS
│   └── build/                     ← UPDATE ENTIRE FOLDER
│
└── (Laravel folder)/              ← Your Laravel installation
    └── resources/
        └── views/
            └── app.blade.php      ← UPDATE THIS
```

## 🚀 DEPLOYMENT STEPS

### Step 1: Fix Migration Error (DONE)

The migration error is fixed. You can now run:

```bash
php artisan migrate
```

If it still fails, run:

```bash
php artisan migrate:fresh --seed
```

⚠️ Warning: `migrate:fresh` will drop all tables!

Or just mark it as migrated:

```bash
php artisan migrate --pretend
```

### Step 2: Copy Files Locally (Optional)

Run the deployment script to copy files to your local public_html:

```powershell
cd c:\Users\lenar\OneDrive\Apps\ElevateGS_LaravelPWA-main
.\deploy-to-public-html.ps1
```

This will copy:
- ✅ manifest.json → public_html/
- ✅ sw.js → public_html/
- ✅ workbox-40c80ae4.js → public_html/
- ✅ manifest.webmanifest → public_html/
- ✅ build/ → public_html/build/

### Step 3: Upload to Hostinger

#### Option A: Via Hostinger File Manager

1. **Login to Hostinger**
   - Go to: https://hpanel.hostinger.com
   - Navigate to: File Manager

2. **Navigate to public_html**
   ```
   /domains/elevategradschool.com/public_html/
   ```

3. **Upload Public Files**
   
   Upload these files from `ElevateGS_LaravelPWA-main/public/`:
   - ✅ `manifest.json` → `/public_html/manifest.json`
   - ✅ `sw.js` → `/public_html/sw.js`
   - ✅ `workbox-40c80ae4.js` → `/public_html/workbox-40c80ae4.js`
   - ✅ `manifest.webmanifest` → `/public_html/manifest.webmanifest`

4. **Update Build Folder**
   
   - Delete `/public_html/build/` folder on server
   - Upload entire `ElevateGS_LaravelPWA-main/public/build/` folder

5. **Update Laravel Files**
   
   Navigate to your Laravel installation folder (might be named differently):
   ```
   /domains/elevategradschool.com/(your-laravel-folder)/
   ```
   
   Upload:
   - ✅ `resources/views/app.blade.php` → `(laravel)/resources/views/app.blade.php`

#### Option B: Via FTP (FileZilla/WinSCP)

1. **Connect to FTP**
   - Host: `ftp.elevategradschool.com`
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21

2. **Upload Files**
   
   Navigate to: `/domains/elevategradschool.com/public_html/`
   
   Upload from local `ElevateGS_LaravelPWA-main/public/`:
   - `manifest.json`
   - `sw.js`
   - `workbox-40c80ae4.js`
   - `manifest.webmanifest`
   - `build/` (entire folder)

3. **Upload Laravel File**
   
   Navigate to: `/domains/elevategradschool.com/(your-laravel-folder)/`
   
   Upload:
   - `resources/views/app.blade.php`

### Step 4: Verify Deployment

After uploading, test these URLs in your browser:

1. **Test Manifest**
   ```
   https://elevategradschool.com/manifest.json
   ```
   Should show JSON with app details

2. **Test Service Worker**
   ```
   https://elevategradschool.com/sw.js
   ```
   Should show JavaScript code

3. **Test Workbox**
   ```
   https://elevategradschool.com/workbox-40c80ae4.js
   ```
   Should show JavaScript code

### Step 5: Clear Cache & Test

1. **Clear Browser Cache**
   - Press `Ctrl + Shift + Delete`
   - Select "Cached images and files"
   - Click "Clear data"

2. **Hard Refresh**
   - Press `Ctrl + Shift + R`

3. **Check DevTools**
   - Press `F12`
   - Go to **Application** tab
   - Click **Manifest** → Should show app details
   - Click **Service Workers** → Should show "activated and running"

4. **Look for Install Button**
   - Desktop Chrome: Right side of URL bar (⊕ icon)
   - Mobile Chrome: Bottom banner

## 🐛 TROUBLESHOOTING

### Migration Error Persists

**Solution 1: Skip the migration**
```bash
# Mark it as migrated without running
INSERT INTO migrations (migration, batch) VALUES ('2025_11_04_221909_create_push_subscriptions_table', 1);
```

**Solution 2: Drop and recreate**
```bash
php artisan tinker
>>> Schema::dropIfExists('push_subscriptions');
>>> exit
php artisan migrate
```

### Install Button Not Showing

**Check Requirements:**
1. Open DevTools (F12) → Application → Manifest
2. Verify all fields are populated
3. Check Console for errors

**Common Issues:**
- ❌ Manifest 404 → File not uploaded
- ❌ SW not active → Clear cache and refresh
- ❌ Already installed → Uninstall first

### Can't Navigate Offline

**Verify Service Worker:**
```javascript
// In browser console:
navigator.serviceWorker.getRegistration().then(reg => {
    console.log('Active:', reg?.active);
    console.log('Scope:', reg?.scope);
});
```

**Check Caches:**
```javascript
// In browser console:
caches.keys().then(console.log);
```

Should show caches like:
- `pages-cache-v1`
- `js-modules-cache-v1`
- `images-cache-v2`

## 📁 QUICK UPLOAD CHECKLIST

Before going to Hostinger, make sure you have these files ready:

### From Laravel Project:
- [ ] `public/manifest.json`
- [ ] `public/sw.js`
- [ ] `public/workbox-40c80ae4.js`
- [ ] `public/manifest.webmanifest`
- [ ] `public/build/` (entire folder)
- [ ] `resources/views/app.blade.php`

### Upload To Hostinger:
- [ ] Upload 4 files to `/public_html/`
- [ ] Upload `build/` folder to `/public_html/build/`
- [ ] Upload `app.blade.php` to Laravel's `/resources/views/`

### After Upload:
- [ ] Test https://elevategradschool.com/manifest.json
- [ ] Test https://elevategradschool.com/sw.js
- [ ] Clear browser cache
- [ ] Hard refresh (Ctrl + Shift + R)
- [ ] Check for install button

## 🎯 SUCCESS CRITERIA

When everything works:

✅ `https://elevategradschool.com/manifest.json` returns JSON  
✅ `https://elevategradschool.com/sw.js` returns JavaScript  
✅ DevTools → Application → Manifest shows app details  
✅ DevTools → Application → Service Workers shows "activated"  
✅ Install button appears in URL bar  
✅ Can navigate pages while offline  

## 💡 TIPS

1. **Use FileZilla** for faster uploads (better than web interface)
2. **Backup first** before overwriting files
3. **Test locally** in public_html before uploading
4. **Clear cache** after every deployment
5. **Check Console** for errors if something doesn't work

---

Need help? Check:
- PWA_INSTALL_TROUBLESHOOTING.md (detailed debugging)
- Console errors in DevTools
- Network tab to see failed requests
