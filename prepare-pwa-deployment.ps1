# PWA Files Deployment Preparation Script
# This script creates a deployment package with all necessary files

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "ElevateGS PWA Deployment Preparation" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$sourceRoot = "c:\Users\lenar\OneDrive\Apps\ElevateGS_LaravelPWA-main"
$deployFolder = "$sourceRoot\deploy-pwa"

# Create deployment folder
Write-Host "Creating deployment folder..." -ForegroundColor Yellow
if (Test-Path $deployFolder) {
    Remove-Item -Path $deployFolder -Recurse -Force
}
New-Item -ItemType Directory -Path $deployFolder | Out-Null
New-Item -ItemType Directory -Path "$deployFolder\public" | Out-Null
New-Item -ItemType Directory -Path "$deployFolder\resources\views" | Out-Null

# Copy critical files
Write-Host "Copying critical files..." -ForegroundColor Yellow

# 1. Manifest
Write-Host "  - manifest.json" -ForegroundColor Gray
Copy-Item "$sourceRoot\public\manifest.json" "$deployFolder\public\manifest.json"

# 2. Service Worker
Write-Host "  - sw.js" -ForegroundColor Gray
Copy-Item "$sourceRoot\public\sw.js" "$deployFolder\public\sw.js"

# 3. Workbox Runtime
Write-Host "  - workbox-40c80ae4.js" -ForegroundColor Gray
Copy-Item "$sourceRoot\public\workbox-40c80ae4.js" "$deployFolder\public\workbox-40c80ae4.js"

# 4. Web Manifest
Write-Host "  - manifest.webmanifest" -ForegroundColor Gray
Copy-Item "$sourceRoot\public\manifest.webmanifest" "$deployFolder\public\manifest.webmanifest"

# 5. Updated Blade Template
Write-Host "  - app.blade.php" -ForegroundColor Gray
Copy-Item "$sourceRoot\resources\views\app.blade.php" "$deployFolder\resources\views\app.blade.php"

# 6. Build folder
Write-Host "  - build/ folder (this may take a moment...)" -ForegroundColor Gray
Copy-Item "$sourceRoot\public\build" "$deployFolder\public\build" -Recurse

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host "✅ Deployment package ready!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "Location: $deployFolder" -ForegroundColor Cyan
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Open Hostinger File Manager or FTP client" -ForegroundColor White
Write-Host "2. Navigate to: domains/elevategradschool.com/public_html/" -ForegroundColor White
Write-Host "3. Upload ALL files from the 'deploy-pwa' folder" -ForegroundColor White
Write-Host "4. Maintain the same folder structure" -ForegroundColor White
Write-Host "5. Hard refresh browser: Ctrl + Shift + R" -ForegroundColor White
Write-Host ""
Write-Host "File count:" -ForegroundColor Cyan
$fileCount = (Get-ChildItem -Path $deployFolder -Recurse -File).Count
Write-Host "  Total files to upload: $fileCount" -ForegroundColor White
Write-Host ""

# Create upload instructions file
$instructions = @"
UPLOAD INSTRUCTIONS FOR HOSTINGER
==================================

Upload the following files from the 'deploy-pwa' folder to your Hostinger server:

SERVER PATH: domains/elevategradschool.com/public_html/

FILES TO UPLOAD:
----------------
✓ public/manifest.json                → /public/manifest.json
✓ public/sw.js                        → /public/sw.js  
✓ public/workbox-40c80ae4.js          → /public/workbox-40c80ae4.js
✓ public/manifest.webmanifest         → /public/manifest.webmanifest
✓ resources/views/app.blade.php       → /resources/views/app.blade.php
✓ public/build/ (ENTIRE FOLDER)       → /public/build/

IMPORTANT:
----------
- Maintain the same folder structure
- Overwrite existing files when prompted
- Make sure file permissions are correct (644 for files, 755 for folders)

AFTER UPLOAD:
-------------
1. Clear your browser cache (Ctrl + Shift + Delete)
2. Hard refresh the site (Ctrl + Shift + R)
3. Check DevTools → Application → Manifest
4. Look for install button in URL bar

VERIFICATION:
-------------
Visit: https://elevategradschool.com/manifest.json
Should show: {"name":"ElevateGS - Grading & Learning Platform"...}

Visit: https://elevategradschool.com/sw.js
Should show: JavaScript code (service worker)
"@

$instructions | Out-File -FilePath "$deployFolder\UPLOAD_INSTRUCTIONS.txt" -Encoding UTF8

Write-Host "📄 Upload instructions saved to: deploy-pwa\UPLOAD_INSTRUCTIONS.txt" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press any key to open deployment folder..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Open the deployment folder
Start-Process explorer.exe -ArgumentList $deployFolder
