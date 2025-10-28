# Converting to Native App with Capacitor

## Option 1: Ionic Capacitor (Recommended)

Capacitor allows you to package your PWA as a native iOS/Android app with access to native device features.

### Installation

```powershell
# Install Capacitor
npm install @capacitor/core @capacitor/cli

# Initialize Capacitor
npx cap init
# When prompted:
# App name: LaravelPWA
# App ID: com.yourcompany.laravelpwa
# Web asset directory: public/build

# Add platforms
npm install @capacitor/android @capacitor/ios
npx cap add android
npx cap add ios
```

### Configuration

Update `capacitor.config.json`:

```json
{
  "appId": "com.yourcompany.laravelpwa",
  "appName": "LaravelPWA",
  "webDir": "public/build",
  "server": {
    "androidScheme": "https",
    "iosScheme": "https"
  },
  "plugins": {
    "SplashScreen": {
      "launchShowDuration": 2000
    }
  }
}
```

### Build and Deploy

```powershell
# Build your Laravel app
npm run build

# Copy web assets to native projects
npx cap sync

# Open in Android Studio
npx cap open android

# Open in Xcode (macOS only)
npx cap open ios
```

### Add Native Features

```powershell
# Camera access
npm install @capacitor/camera

# Push notifications
npm install @capacitor/push-notifications

# Geolocation
npm install @capacitor/geolocation

# Local storage
npm install @capacitor/preferences
```

**Example using Camera:**

```vue
<script setup>
import { Camera, CameraResultType } from '@capacitor/camera';

const takePicture = async () => {
  const image = await Camera.getPhoto({
    quality: 90,
    allowEditing: true,
    resultType: CameraResultType.Uri
  });
  
  // Use image.webPath
  console.log(image.webPath);
};
</script>
```

---

## Option 2: Electron (For Desktop Apps)

Package as a desktop app for Windows, macOS, and Linux.

### Installation

```powershell
npm install --save-dev electron electron-builder

# Install Vite Electron plugin
npm install --save-dev vite-plugin-electron
```

### Create Electron Main Process

**File: `electron/main.js`**

```javascript
const { app, BrowserWindow } = require('electron');
const path = require('path');

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
    }
  });

  // In development
  if (process.env.VITE_DEV_SERVER_URL) {
    win.loadURL(process.env.VITE_DEV_SERVER_URL);
  } else {
    // In production
    win.loadFile(path.join(__dirname, '../public/build/index.html'));
  }
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});
```

### Update package.json

```json
{
  "main": "electron/main.js",
  "scripts": {
    "electron:dev": "electron .",
    "electron:build": "electron-builder"
  },
  "build": {
    "appId": "com.yourcompany.laravelpwa",
    "productName": "LaravelPWA",
    "directories": {
      "output": "dist-electron"
    },
    "files": [
      "electron/**/*",
      "public/build/**/*"
    ],
    "win": {
      "target": "nsis"
    },
    "mac": {
      "target": "dmg"
    },
    "linux": {
      "target": "AppImage"
    }
  }
}
```

---

## Option 3: Tauri (Lightweight Alternative to Electron)

Tauri uses native system webviews (smaller, faster than Electron).

### Installation

```powershell
# Install Tauri CLI
cargo install tauri-cli
# Or via npm
npm install --save-dev @tauri-apps/cli

# Initialize Tauri
npm run tauri init
```

### Configuration

**File: `src-tauri/tauri.conf.json`**

```json
{
  "build": {
    "beforeDevCommand": "npm run dev",
    "beforeBuildCommand": "npm run build",
    "devPath": "http://localhost:8000",
    "distDir": "../public/build"
  },
  "package": {
    "productName": "LaravelPWA",
    "version": "1.0.0"
  },
  "tauri": {
    "allowlist": {
      "all": false,
      "shell": {
        "all": false,
        "open": true
      }
    },
    "windows": [
      {
        "fullscreen": false,
        "height": 800,
        "resizable": true,
        "title": "LaravelPWA",
        "width": 1200
      }
    ]
  }
}
```

### Build

```powershell
# Development
npm run tauri dev

# Production build
npm run tauri build
```

---

## Comparison Table

| Feature | PWA | Capacitor | Electron | Tauri |
|---------|-----|-----------|----------|-------|
| **Size** | Smallest | Medium | Large (100MB+) | Small |
| **Installation** | ✅ Already done | Medium effort | Medium effort | Medium effort |
| **iOS App Store** | ❌ No | ✅ Yes | ❌ No | ❌ No |
| **Android Play Store** | Limited | ✅ Yes | ❌ No | ❌ No |
| **Desktop (Win/Mac/Linux)** | ✅ Yes | ❌ No | ✅ Yes | ✅ Yes |
| **Native APIs** | Limited | ✅ Full access | ✅ Full access | ✅ Full access |
| **Offline Support** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Performance** | Excellent | Excellent | Good | Excellent |
| **Cost** | Free | Free | Free | Free |
| **Distribution** | Web only | App stores | Direct download | Direct download |

---

## Recommended Approach

### For Your Use Case:

**Start with PWA (already working!)** → Then add Capacitor if you need:
- App store distribution
- Native device features (camera, contacts, etc.)
- Better iOS support

### Why?

1. **PWA is already implemented** - works on all platforms now
2. **No additional build process** needed
3. **Updates instantly** - no app store approval delays
4. **Smaller download** - users get latest version automatically
5. **Cross-platform** - works everywhere

### When to Add Capacitor:

- Need app store presence
- Require native APIs (camera, push notifications, etc.)
- Want offline-first database (SQLite)
- Need background tasks
- Want better iOS integration

---

## Quick Start for Capacitor

If you want to try Capacitor now:

```powershell
# 1. Install Capacitor
npm install @capacitor/core @capacitor/cli @capacitor/android

# 2. Initialize
npx cap init "LaravelPWA" "com.elevategs.laravelpwa" --web-dir="public/build"

# 3. Add Android platform
npx cap add android

# 4. Build your app
npm run build

# 5. Sync assets
npx cap sync

# 6. Open in Android Studio
npx cap open android
```

Then in Android Studio, click the green "Run" button to test on emulator or device!

---

## Native Features You Can Add

Once you have Capacitor set up:

### Camera
```javascript
import { Camera } from '@capacitor/camera';
const photo = await Camera.getPhoto();
```

### Push Notifications
```javascript
import { PushNotifications } from '@capacitor/push-notifications';
await PushNotifications.register();
```

### Geolocation
```javascript
import { Geolocation } from '@capacitor/geolocation';
const position = await Geolocation.getCurrentPosition();
```

### App Store Ready!

After Capacitor setup, you can:
1. Generate signed APK/AAB for Google Play
2. Generate signed IPA for Apple App Store
3. Submit to stores with your Laravel Vue app as a native app!

---

## Summary

**You already have a native-like app through PWA!** 

For true native app store distribution or advanced native features, add Capacitor. It's the easiest path forward from where you are now.
