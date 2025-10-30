# SSL Certificate Fix for Google OAuth

## Problem Identified
The error was: `cURL error 60: SSL certificate problem: unable to get local issuer certificate`

This is a common issue on Windows where PHP doesn't have the Certificate Authority (CA) bundle to verify SSL certificates.

## What Was Done

### 1. Downloaded CA Certificate Bundle
Downloaded the latest CA certificate bundle from curl.se:
```
Location: C:\Users\lenar\cacert.pem
```

### 2. Updated PHP Configuration
Modified `C:\php84\php.ini` to use the certificate bundle:

**Changes made:**
```ini
; Before:
;curl.cainfo =
;openssl.cafile=

; After:
curl.cainfo = "C:\Users\lenar\cacert.pem"
openssl.cafile="C:\Users\lenar\cacert.pem"
```

**Backup created:** `C:\php84\php.ini.backup_[timestamp]`

## Required Actions

### ⚠️ IMPORTANT: Restart PHP/Laravel Server

The php.ini changes won't take effect until you restart the PHP processes.

**Steps:**

1. **Stop the running Laravel server:**
   - If you're running `php artisan serve`, press `Ctrl+C` in that terminal
   - Or close the terminal running the server

2. **Start the Laravel server again:**
   ```powershell
   php artisan serve
   ```

3. **Test Google OAuth:**
   - Go to http://127.0.0.1:8000/login
   - Click "Sign in with Google"
   - It should now work without SSL errors!

## Verification

After restarting the server, you can verify the SSL certificate is configured:

```powershell
php -i | Select-String "curl.cainfo|openssl.cafile"
```

You should see:
```
curl.cainfo => C:\Users\lenar\cacert.pem => C:\Users\lenar\cacert.pem
openssl.cafile => C:\Users\lenar\cacert.pem => C:\Users\lenar\cacert.pem
```

## What This Fixes

- ✅ Google OAuth authentication
- ✅ Any other HTTPS requests from PHP (Socialite, Guzzle, etc.)
- ✅ Composer package downloads
- ✅ Laravel HTTP client requests
- ✅ Email sending via SMTP with TLS/SSL

## Testing Google OAuth

1. **Register Page (New User):**
   - Go to `/register`
   - Select Teacher or Student role
   - Click "Sign up with Google"
   - Should authenticate and create account

2. **Login Page (Existing User):**
   - Go to `/login`
   - Click "Sign in with Google"
   - Should detect your role and redirect to dashboard

## If Still Having Issues

Check the Laravel logs after testing:
```powershell
Get-Content .\storage\logs\laravel.log -Tail 50
```

The detailed error logging we added earlier will show any remaining issues.

## Rollback (If Needed)

If you need to revert the changes:
```powershell
# Restore the backup
Copy-Item "C:\php84\php.ini.backup_[timestamp]" "C:\php84\php.ini" -Force

# Restart server
```

## Summary

✅ CA certificate bundle downloaded  
✅ PHP configuration updated  
⚠️ **Action Required: Restart Laravel server**  
🧪 Ready to test Google OAuth

---

**Note:** This is a permanent fix. You won't need to do this again unless you reinstall PHP or the certificate bundle expires (which happens every few years, and you'll just need to download a new one).
