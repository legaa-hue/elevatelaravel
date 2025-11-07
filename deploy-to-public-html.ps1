# Hostinger PWA Deployment Script
# This script copies files to public_html (Hostinger structure)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Hostinger PWA Deployment" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Paths
$laravelRoot = "c:\Users\lenar\OneDrive\Apps\ElevateGS_LaravelPWA-main"
$publicHtmlRoot = "c:\Users\lenar\OneDrive\Apps\public_html"

# Check if public_html exists
if (-not (Test-Path $publicHtmlRoot)) {
    Write-Host "❌ Error: public_html folder not found at: $publicHtmlRoot" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please update the script with the correct path to public_html" -ForegroundColor Yellow
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit
}

Write-Host "✅ Found public_html at: $publicHtmlRoot" -ForegroundColor Green
Write-Host ""

# Step 1: Copy public files
Write-Host "Step 1: Copying public files..." -ForegroundColor Yellow

$publicFiles = @(
    "manifest.json",
    "sw.js",
    "workbox-40c80ae4.js",
    "manifest.webmanifest"
)

foreach ($file in $publicFiles) {
    $source = Join-Path $laravelRoot "public\$file"
    $dest = Join-Path $publicHtmlRoot $file
    
    if (Test-Path $source) {
        Copy-Item -Path $source -Destination $dest -Force
        Write-Host "  ✓ Copied $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Missing: $file" -ForegroundColor Red
    }
}

# Step 2: Copy build folder
Write-Host ""
Write-Host "Step 2: Copying build folder..." -ForegroundColor Yellow

$buildSource = Join-Path $laravelRoot "public\build"
$buildDest = Join-Path $publicHtmlRoot "build"

if (Test-Path $buildSource) {
    # Remove old build folder
    if (Test-Path $buildDest) {
        Write-Host "  - Removing old build folder..." -ForegroundColor Gray
        Remove-Item -Path $buildDest -Recurse -Force
    }
    
    # Copy new build folder
    Write-Host "  - Copying new build folder (this may take a moment)..." -ForegroundColor Gray
    Copy-Item -Path $buildSource -Destination $buildDest -Recurse -Force
    
    $fileCount = (Get-ChildItem -Path $buildDest -Recurse -File).Count
    Write-Host "  ✓ Copied build folder ($fileCount files)" -ForegroundColor Green
} else {
    Write-Host "  ✗ Build folder not found!" -ForegroundColor Red
    Write-Host "  Run 'npm run build' first" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host "✅ Local deployment complete!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "FILES UPDATED IN public_html:" -ForegroundColor Cyan
Write-Host "  ✓ manifest.json" -ForegroundColor White
Write-Host "  ✓ sw.js" -ForegroundColor White
Write-Host "  ✓ workbox-40c80ae4.js" -ForegroundColor White
Write-Host "  ✓ manifest.webmanifest" -ForegroundColor White
Write-Host "  ✓ build/ folder" -ForegroundColor White
Write-Host ""
Write-Host "⚠️  IMPORTANT: You still need to update on Hostinger server!" -ForegroundColor Yellow
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Upload the public_html folder to Hostinger via FTP" -ForegroundColor White
Write-Host "2. Also upload: resources/views/app.blade.php (to Laravel folder)" -ForegroundColor White
Write-Host "3. Hard refresh browser: Ctrl + Shift + R" -ForegroundColor White
Write-Host ""
Write-Host "FTP DETAILS:" -ForegroundColor Cyan
Write-Host "  Host: ftp.elevategradschool.com" -ForegroundColor White
Write-Host "  Path: /domains/elevategradschool.com/" -ForegroundColor White
Write-Host "  Upload: public_html/* → public_html/" -ForegroundColor White
Write-Host "  Upload: resources/views/app.blade.php → (Laravel folder)/resources/views/" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
