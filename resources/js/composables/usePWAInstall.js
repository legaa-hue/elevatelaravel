import { ref, onMounted } from 'vue';

export function usePWAInstall() {
    const deferredPrompt = ref(null);
    const canInstall = ref(false);
    const isInstalled = ref(false);

    onMounted(() => {
        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            isInstalled.value = true;
            console.log('✅ PWA is already installed');
        }

        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('🔔 beforeinstallprompt event fired');
            // Prevent the mini-infobar from appearing
            e.preventDefault();
            // Save the event for later
            deferredPrompt.value = e;
            canInstall.value = true;
        });

        // Listen for successful installation
        window.addEventListener('appinstalled', () => {
            console.log('✅ PWA was installed successfully');
            isInstalled.value = true;
            canInstall.value = false;
            deferredPrompt.value = null;
        });
    });

    const showInstallPrompt = async () => {
        if (!deferredPrompt.value) {
            console.warn('⚠️ Install prompt not available');
            return false;
        }

        // Show the install prompt
        deferredPrompt.value.prompt();

        // Wait for the user's response
        const { outcome } = await deferredPrompt.value.userChoice;
        
        console.log(`User response: ${outcome}`);

        if (outcome === 'accepted') {
            console.log('✅ User accepted the install prompt');
        } else {
            console.log('❌ User dismissed the install prompt');
        }

        // Clear the deferredPrompt
        deferredPrompt.value = null;
        canInstall.value = false;

        return outcome === 'accepted';
    };

    return {
        canInstall,
        isInstalled,
        showInstallPrompt
    };
}
