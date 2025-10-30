# Quick Setup Checklist

## ⚡ Quick Start - 5 Steps to Complete Setup

### Step 1: Get Google Client Secret
1. Go to: https://console.cloud.google.com/apis/credentials
2. Find OAuth 2.0 Client ID: `291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi`
3. Copy the Client Secret
4. Update `.env`:
   ```
   GOOGLE_CLIENT_SECRET=paste-your-secret-here
   ```

### Step 2: Get reCAPTCHA Keys
1. Go to: https://www.google.com/recaptcha/admin/create
2. Choose **reCAPTCHA v3** (runs invisibly in background)
3. Domains: `localhost`, `127.0.0.1`
4. Copy Site Key and Secret Key
5. Update `.env`:
   ```
   RECAPTCHA_SITE_KEY=paste-site-key-here
   RECAPTCHA_SECRET_KEY=paste-secret-key-here
   VITE_RECAPTCHA_SITE_KEY="${RECAPTCHA_SITE_KEY}"
   ```

### Step 3: Update Gmail Email
Update `.env` with your Gmail address:
```
MAIL_USERNAME=your-email@gmail.com
MAIL_FROM_ADDRESS=your-email@gmail.com
```

### Step 4: Build and Test
```bash
npm install
npm run build
php artisan serve
```

Visit: http://127.0.0.1:8000/login

---

## 🔑 Current Credentials

### Gmail App Password (Already Set)
```
Password: wbps ifwm ytoe xoqw
```
✅ No change needed - Just update MAIL_USERNAME with your Gmail

### Google OAuth Client ID (Already Set)
```
Client ID: 291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com
```
✅ No change needed - Just get the Client Secret

### What You MUST Get:
- [ ] Google Client Secret
- [ ] reCAPTCHA v3 Site Key
- [ ] reCAPTCHA v3 Secret Key

---

## 🧪 Test Account for Quick Testing

After setup, create a test account:

**Teacher Account:**
- Email: teacher@test.com
- Password: password123
- Role: Teacher

**Student Account:**
- Email: student@test.com
- Password: password123
- Role: Student

---

## ✅ Verification Checklist

After completing steps 1-4:

- [ ] Can access login page
- [ ] "Protected by reCAPTCHA" text appears (runs invisibly)
- [ ] Can click "Sign up with Google" (redirects to Google)
- [ ] Can register with email/password
- [ ] "Verify Email" button appears on register page when typing email
- [ ] Can login after registration
- [ ] Teacher redirects to teacher dashboard
- [ ] Student redirects to student dashboard

---

## 🆘 Quick Troubleshooting

**Google OAuth Error?**
- Check Client Secret is correct
- Verify redirect URI in Google Console: `http://127.0.0.1:8000/auth/google/callback`

**reCAPTCHA Not Working?**
- Make sure you chose reCAPTCHA v3 (not v2)
- Check site key in `.env` as `VITE_RECAPTCHA_SITE_KEY`
- Add `localhost` and `127.0.0.1` in reCAPTCHA admin
- Rebuild frontend: `npm run build`

**Email Not Sending?**
- Check Gmail address is correct
- App password is correct (already set)
- 2FA is enabled on Gmail account

**Build Errors?**
```bash
npm install
npm run build
php artisan config:clear
php artisan cache:clear
```

---

## 📞 Support Files

Full documentation:
- `AUTH_SETUP_GUIDE.md` - Complete setup guide
- `AUTH_IMPLEMENTATION_SUMMARY.md` - Feature summary

---

**Time to Complete: ~15 minutes**
**Difficulty: Easy** 🟢

Good luck! 🚀
