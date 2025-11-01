# ElevateGS Deployment Guide

## 🚀 Quick Deployment (Extract and Go)

### Prerequisites
- PHP 8.2 or higher
- MySQL/MariaDB
- Composer
- Node.js & NPM
- Web server (Apache/Nginx)

---

## 📦 Deployment Methods

### Method 1: Automatic Deployment (Recommended)

#### For Linux/Mac:
```bash
chmod +x deploy.sh
./deploy.sh
```

#### For Windows:
```cmd
deploy.bat
```

This will automatically:
✅ Install dependencies
✅ Build frontend
✅ Run migrations
✅ Cache configuration
✅ Set permissions

---

### Method 2: Manual Deployment

#### Step 1: Extract Files
Extract the project to your web server directory (e.g., `/var/www/html/elevategs`)

#### Step 2: Configure Environment
```bash
# Copy and edit environment file
cp .env.production .env
nano .env

# Update these values:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

#### Step 3: Install Dependencies
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

#### Step 4: Setup Database
```bash
# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force
```

#### Step 5: Optimize
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 6: Set Permissions
```bash
# Linux/Mac
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows - Right click folders → Properties → Security → Give write access
```

---

## ⚙️ Queue Configuration (IMPORTANT)

### Option A: Sync Queue (No Worker Needed) ✅ RECOMMENDED

**Best for:** Shared hosting, simple deployments

Update `.env`:
```env
QUEUE_CONNECTION=sync
```

✅ **Emails sent immediately**
✅ **No background worker needed**
✅ **Works automatically**
✅ **Perfect for small-medium traffic**

---

### Option B: Database Queue (Requires Worker)

**Best for:** High traffic, dedicated servers

Update `.env`:
```env
QUEUE_CONNECTION=database
```

**⚠️ Requires Background Worker:**

#### Using Supervisor (Linux - Recommended)

1. Install Supervisor:
```bash
sudo apt-get install supervisor
```

2. Create supervisor config:
```bash
sudo nano /etc/supervisor/conf.d/elevategs-worker.conf
```

3. Add this configuration:
```ini
[program:elevategs-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/elevategs/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/elevategs/storage/logs/worker.log
stopwaitsecs=3600
```

4. Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start elevategs-worker:*
```

5. Check status:
```bash
sudo supervisorctl status
```

---

#### Using Systemd (Linux - Alternative)

1. Create service file:
```bash
sudo nano /etc/systemd/system/elevategs-worker.service
```

2. Add this configuration:
```ini
[Unit]
Description=ElevateGS Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/elevategs/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

3. Enable and start:
```bash
sudo systemctl enable elevategs-worker
sudo systemctl start elevategs-worker
sudo systemctl status elevategs-worker
```

---

#### Using Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Name: "ElevateGS Queue Worker"
4. Trigger: "When the computer starts"
5. Action: "Start a program"
6. Program: `C:\php\php.exe`
7. Arguments: `artisan queue:work --sleep=3 --tries=3`
8. Start in: `C:\path\to\elevategs`
9. Check "Run with highest privileges"

---

#### Using cPanel Cron Job (Shared Hosting)

Add this cron job (runs every minute):
```bash
* * * * * cd /home/username/public_html/elevategs && php artisan queue:work --stop-when-empty > /dev/null 2>&1
```

---

## 🌐 Web Server Configuration

### Apache (.htaccess - Already Included)

Make sure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Virtual Host configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/elevategs/public

    <Directory /var/www/html/elevategs/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/elevategs-error.log
    CustomLog ${APACHE_LOG_DIR}/elevategs-access.log combined
</VirtualHost>
```

---

### Nginx

Configuration file:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/elevategs/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🔒 SSL Certificate (Production)

### Using Let's Encrypt (Free)

```bash
# Install certbot
sudo apt-get install certbot python3-certbot-apache

# Apache
sudo certbot --apache -d yourdomain.com

# Nginx
sudo certbot --nginx -d yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

## 📧 Email Configuration

### Gmail SMTP (Already Configured)

Current settings in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=elevategs24@gmail.com
MAIL_PASSWORD="wbps ifwm ytoe xoqw"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="elevategs24@gmail.com"
MAIL_FROM_NAME="ElevateGS"
```

✅ No changes needed - works out of the box!

### Testing Email

```bash
php artisan tinker
>>> \Illuminate\Support\Facades\Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

---

## 🔍 Troubleshooting

### Issue: "500 Internal Server Error"

**Solutions:**
```bash
# Check permissions
chmod -R 755 storage bootstrap/cache

# Check logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

### Issue: "Emails not sending"

**Solutions:**

1. **If QUEUE_CONNECTION=sync:**
   - Check `.env` mail settings
   - Test with `php artisan tinker`
   - Check Laravel logs

2. **If QUEUE_CONNECTION=database:**
   - Verify queue worker is running
   - Check: `php artisan queue:work`
   - Check jobs table: `SELECT * FROM jobs;`

```bash
# Process pending jobs manually
php artisan queue:work --once
```

---

### Issue: "Page not found / CSS not loading"

**Solutions:**
```bash
# Apache
sudo a2enmod rewrite
sudo systemctl restart apache2

# Rebuild assets
npm run build

# Check DocumentRoot points to /public folder
```

---

### Issue: "Database connection refused"

**Solutions:**
```bash
# Verify MySQL is running
sudo systemctl status mysql

# Check credentials in .env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elevategs
DB_USERNAME=root
DB_PASSWORD=your_password

# Create database if not exists
mysql -u root -p
CREATE DATABASE elevategs;
```

---

## 📋 Post-Deployment Checklist

### Security
- [ ] Set `APP_DEBUG=false` in production
- [ ] Set `APP_ENV=production`
- [ ] Generate new `APP_KEY` for production
- [ ] Use strong database password
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set proper file permissions (755 for folders, 644 for files)
- [ ] Remove `.env.example` or sensitive files from public access

### Configuration
- [ ] Update `APP_URL` to production domain
- [ ] Update `GOOGLE_REDIRECT_URI`
- [ ] Configure queue (sync or database)
- [ ] Test email sending
- [ ] Test Google OAuth login
- [ ] Test push notifications
- [ ] Test file uploads

### Performance
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Enable OPcache in PHP
- [ ] Setup Redis for caching (optional)

### Monitoring
- [ ] Setup error logging
- [ ] Monitor `storage/logs/laravel.log`
- [ ] Setup uptime monitoring
- [ ] Monitor disk space
- [ ] Monitor database size

---

## 🎯 Quick Reference

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize

# Check queue jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart queue workers
php artisan queue:restart

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Check Laravel version
php artisan --version

# List all routes
php artisan route:list

# Generate new application key
php artisan key:generate
```

---

## 📞 Support

### Check System Status
```bash
# Check PHP version
php -v

# Check Laravel version
php artisan --version

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue status
php artisan queue:work --once
```

### Logs Location
- Laravel: `storage/logs/laravel.log`
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- Queue Worker: `storage/logs/worker.log` (if configured)

---

## 🎉 Summary

### For Simple Deployment (Recommended):
1. Extract files
2. Copy `.env.production` to `.env`
3. Update `.env` settings
4. Run `deploy.bat` (Windows) or `deploy.sh` (Linux)
5. Set `QUEUE_CONNECTION=sync` in `.env`
6. Done! ✅

### For Advanced Deployment:
1. Follow manual steps above
2. Configure web server (Apache/Nginx)
3. Setup supervisor/systemd for queue worker
4. Configure SSL certificate
5. Setup monitoring
6. Done! ✅

---

**🎊 Your ElevateGS application is now ready for production!**
