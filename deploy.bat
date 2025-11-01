@echo off
REM ElevateGS Production Deployment Script for Windows
REM This script automates the deployment process

echo.
echo ========================================
echo  ElevateGS Production Deployment
echo ========================================
echo.

REM 1. Install Composer Dependencies (Production)
echo [1/9] Installing Composer dependencies...
call composer install --optimize-autoloader --no-dev
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed
    pause
    exit /b %errorlevel%
)

REM 2. Install NPM Dependencies
echo [2/9] Installing NPM dependencies...
call npm install
if %errorlevel% neq 0 (
    echo ERROR: NPM install failed
    pause
    exit /b %errorlevel%
)

REM 3. Build Frontend Assets
echo [3/9] Building frontend assets...
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: Build failed
    pause
    exit /b %errorlevel%
)

REM 4. Clear Configuration
echo [4/9] Clearing configuration cache...
php artisan config:clear

REM 5. Cache Configuration
echo [5/9] Caching configuration...
php artisan config:cache
php artisan route:cache
php artisan view:cache

REM 6. Run Migrations
echo [6/9] Running database migrations...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo WARNING: Migrations may have failed
)

REM 7. Generate Application Key if needed
echo [7/9] Checking application key...
findstr /C:"APP_KEY=base64:" .env >nul
if %errorlevel% neq 0 (
    echo Generating application key...
    php artisan key:generate --force
)

REM 8. Clear all caches
echo [8/9] Clearing all caches...
php artisan cache:clear
php artisan view:clear
php artisan route:clear

REM 9. Done
echo [9/9] Deployment complete!
echo.
echo ========================================
echo  Post-Deployment Checklist
echo ========================================
echo.
echo 1. Update .env with production settings
echo 2. Set APP_ENV=production
echo 3. Set APP_DEBUG=false
echo 4. Update APP_URL to your domain
echo 5. Update database credentials
echo 6. Update GOOGLE_REDIRECT_URI
echo.
echo ========================================
echo  Queue Configuration
echo ========================================
echo.
findstr /C:"QUEUE_CONNECTION=sync" .env >nul
if %errorlevel% equ 0 (
    echo [OK] Queue is set to 'sync'
    echo      Emails will be sent immediately
    echo      No queue worker needed!
) else (
    echo [WARNING] Queue is set to 'database'
    echo           You need to setup a worker or change to 'sync'
)
echo.
echo ========================================
echo  Deployment Complete!
echo ========================================
echo.
pause
