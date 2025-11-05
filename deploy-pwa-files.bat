@echo off
echo ============================================
echo ElevateGS PWA Files Deployment Guide
echo ============================================
echo.
echo You need to upload the following files to Hostinger:
echo.
echo CRITICAL FILES (MUST UPLOAD):
echo --------------------------------
echo 1. public/manifest.json (NEW FILE - PWA manifest)
echo 2. public/sw.js (Updated service worker)
echo 3. public/workbox-40c80ae4.js (Service worker runtime)
echo 4. public/manifest.webmanifest (Generated manifest)
echo 5. resources/views/app.blade.php (Updated with PWA meta tags)
echo 6. public/build/ (ENTIRE FOLDER - rebuilt assets)
echo.
echo DEPLOYMENT STEPS:
echo --------------------------------
echo 1. Connect to Hostinger via FTP/File Manager
echo 2. Navigate to your application root (domains/elevategradschool.com/public_html)
echo 3. Upload the files listed above
echo 4. Clear browser cache and reload site
echo.
echo After deployment:
echo - Hard refresh: Ctrl + Shift + R
echo - Check DevTools (F12) -^> Application -^> Manifest
echo - Install button should appear in URL bar
echo.
pause
