# ElevateGS Test Accounts

## Admin Account
- **Email**: admin@elevategs.com
- **Password**: admin123
- **Role**: admin
- **Access**: Full admin dashboard and all features

## Teacher Account
- **Email**: teacher@elevategs.com
- **Password**: teacher123
- **Role**: teacher
- **Access**: Teacher dashboard and course management

## Student Account
- **Email**: student@elevategs.com
- **Password**: student123
- **Role**: student
- **Access**: Student dashboard and enrolled courses

---

## Admin Dashboard Access

After logging in as admin, navigate to:
- **URL**: http://127.0.0.1:8000/admin/dashboard

## Features Available to Admin:
- 🏠 Admin Dashboard - Overview and metrics
- 📅 Calendar - Events and announcements
- 📘 Class Record - Grade management
- 📚 Courses - Course management
- 👥 User Management - User administration
- 📋 Audit Logs - System activity tracking
- 🎓 Academic Year - Academic year configuration
- 📊 Reports - Generate reports

## Database Roles

The system now supports three roles:
1. **admin** - Full system access
2. **teacher** - Course creation and management
3. **student** - Course enrollment and learning

---

## Re-seeding Database

To recreate these accounts:
```bash
php artisan migrate:fresh --seed
```

Or just seed without migration:
```bash
php artisan db:seed
```
