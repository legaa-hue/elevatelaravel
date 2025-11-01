#!/bin/bash

# ElevateGS Production Deployment Script
# This script automates the deployment process

echo "🚀 Starting ElevateGS Deployment..."

# 1. Install Composer Dependencies (Production)
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# 2. Install NPM Dependencies and Build
echo "📦 Installing NPM dependencies..."
npm install
echo "🔨 Building frontend assets..."
npm run build

# 3. Clear and Cache Configuration
echo "⚙️ Optimizing configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run Migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 5. Set Permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 6. Generate Application Key (if not set)
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# 7. Generate VAPID Keys for Push Notifications (if not exists)
if [ ! -f "config/webpush.php" ] || ! grep -q "VAPID_PUBLIC_KEY" .env; then
    echo "🔔 Generating VAPID keys for push notifications..."
    php artisan webpush:vapid
fi

# 8. Clear all caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 9. Queue Worker Setup Check
echo "✅ Deployment Complete!"
echo ""
echo "📋 Post-Deployment Checklist:"
echo "================================"
echo "1. Update .env with production settings"
echo "2. Set APP_ENV=production"
echo "3. Set APP_DEBUG=false"
echo "4. Update APP_URL to your domain"
echo "5. Update database credentials"
echo "6. Update GOOGLE_REDIRECT_URI"
echo ""
echo "📬 Queue Configuration:"
if grep -q "QUEUE_CONNECTION=sync" .env; then
    echo "✅ Queue is set to 'sync' - emails will be sent immediately"
    echo "   No queue worker needed!"
else
    echo "⚠️  Queue is set to 'database' - You need to setup a worker:"
    echo "   Option 1: Change to 'sync' in .env (easier)"
    echo "   Option 2: Setup supervisor/systemd (advanced)"
fi
echo ""
echo "🎉 ElevateGS is ready!"
