# Laravel Vue Inertia PWA

A modern Progressive Web Application built with Laravel 12, Vue 3, Inertia.js, and Vite PWA.

## 🚀 Features

- ⚡ **Single Page Application** - Smooth navigation with Inertia.js
- 📱 **Progressive Web App** - Installable on any device with offline support
- 🎨 **Modern UI** - Beautiful Tailwind CSS design
- 🔐 **Authentication** - Complete auth system with Laravel Breeze
- 🔥 **Hot Module Replacement** - Instant updates during development
- 📦 **Service Worker** - Automatic asset caching for offline use

## 🛠️ Tech Stack

- **Backend:** Laravel 12.35.1
- **Frontend:** Vue 3.4
- **SPA Framework:** Inertia.js 2.0
- **Build Tool:** Vite 5.4
- **Styling:** Tailwind CSS 3.2
- **PWA:** Vite PWA Plugin
- **Authentication:** Laravel Breeze

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- npm or yarn

## 🚦 Installation

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd ElevateGS_LaravelPWA
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

## 🎬 Running the Application

You need two terminals running simultaneously:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Vite Dev Server:**
```bash
npm run dev
```

Then visit: `http://127.0.0.1:8000`

## 🏗️ Building for Production

```bash
npm run build
```

This generates:
- Optimized JavaScript and CSS bundles
- Service worker (`public/build/sw.js`)
- PWA manifest (`public/build/manifest.webmanifest`)
- All assets in `public/build/`

## 📱 PWA Features

- **Install Button** - One-click installation on any device
- **Offline Support** - Works without internet after first visit
- **Auto-updates** - Service worker updates automatically
- **Native Feel** - Runs fullscreen when installed

### Installing the PWA

**Desktop (Chrome/Edge):**
- Click the install icon in the address bar
- Or use the "Install App" button on the landing page

**Android:**
- Tap menu → "Install app"
- Or use the install button

**iOS (Safari):**
- Tap Share → "Add to Home Screen"

## 📁 Project Structure

```
├── app/
│   └── Http/
│       └── Middleware/
│           └── HandleInertiaRequests.php
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Landing.vue
│   │   │   ├── Dashboard.vue
│   │   │   └── Auth/
│   │   └── app.js
│   ├── css/
│   │   └── app.css
│   └── views/
│       └── app.blade.php
├── routes/
│   └── web.php
├── public/
│   └── build/
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## 🔧 Configuration Files

- **vite.config.js** - Vite and PWA configuration
- **tailwind.config.js** - Tailwind CSS settings
- **app/Http/Middleware/HandleInertiaRequests.php** - Inertia shared props

## 🎨 Available Pages

- `/` - Landing page with PWA install button
- `/login` - User login
- `/register` - User registration
- `/dashboard` - User dashboard (authenticated)
- `/profile` - User profile (authenticated)

## 🔐 Authentication

Built with Laravel Breeze, includes:
- Login
- Registration
- Password reset
- Email verification
- Profile management

## 📚 Documentation

- [Setup Guide](SETUP_GUIDE.md) - Detailed setup instructions
- [Native App Guide](NATIVE_APP_GUIDE.md) - Convert to native mobile app

## 🐛 Troubleshooting

**Assets not loading?**
- Make sure both Laravel and Vite servers are running

**Service worker not updating?**
- Clear browser cache or test in incognito mode

**npm errors?**
- Try `npm install --legacy-peer-deps`

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- Laravel
- Vue.js
- Inertia.js
- Tailwind CSS
- Vite PWA Plugin

---

Made with ❤️ using Laravel, Vue, and Inertia.js
