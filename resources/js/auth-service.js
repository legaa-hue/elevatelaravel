import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import offlineStorage from './offline-storage';

// JWT token management
const AUTH_TOKEN_KEY = 'elevategs_jwt_token';
const AUTH_REMEMBER_KEY = 'elevategs_remember';

class AuthService {
    constructor() {
        this.token = ref(null);
        this.user = ref(null);
        this.isOnline = ref(navigator.onLine);
        this.init();
    }

    init() {
        // Load token from storage
        const token = this.getStoredToken();
        if (token) {
            this.token.value = token;
            this.validateToken();
        }

        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline.value = true;
            this.syncPendingActions();
        });

        window.addEventListener('offline', () => {
            this.isOnline.value = false;
        });
    }

    getStoredToken() {
        // Check localStorage first (remember me)
        let token = localStorage.getItem(AUTH_TOKEN_KEY);
        if (token) return token;

        // Check sessionStorage (no remember me)
        token = sessionStorage.getItem(AUTH_TOKEN_KEY);
        return token;
    }

    storeToken(token, remember = false) {
        this.token.value = token;

        if (remember) {
            localStorage.setItem(AUTH_TOKEN_KEY, token);
            localStorage.setItem(AUTH_REMEMBER_KEY, 'true');
        } else {
            sessionStorage.setItem(AUTH_TOKEN_KEY, token);
            localStorage.removeItem(AUTH_REMEMBER_KEY);
        }
    }

    clearToken() {
        this.token.value = null;
        this.user.value = null;
        localStorage.removeItem(AUTH_TOKEN_KEY);
        localStorage.removeItem(AUTH_REMEMBER_KEY);
        sessionStorage.removeItem(AUTH_TOKEN_KEY);
    }

    async validateToken() {
        if (!this.token.value) return false;

        try {
            const response = await fetch('/api/auth/me', {
                headers: {
                    'Authorization': `Bearer ${this.token.value}`,
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.user.value = data.user;
                
                // Store user offline
                await offlineStorage.save('user', data.user);
                
                return true;
            } else {
                this.clearToken();
                return false;
            }
        } catch (error) {
            // If offline, try to load user from IndexedDB
            if (!this.isOnline.value) {
                const users = await offlineStorage.getAll('user');
                if (users.length > 0) {
                    this.user.value = users[0];
                    return true;
                }
            }
            return false;
        }
    }

    async login(email, password, remember = false) {
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email, password, remember })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.storeToken(data.token, remember);
                this.user.value = data.user;
                
                // Store user offline
                await offlineStorage.save('user', data.user);
                
                return { success: true, user: data.user };
            } else {
                return { success: false, message: data.message, code: data.code, email: data.email };
            }
        } catch (error) {
            return { success: false, message: 'Network error. Please check your connection.' };
        }
    }

    async register(userData) {
        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                return { success: true, message: data.message };
            } else {
                return { success: false, errors: data.errors };
            }
        } catch (error) {
            return { success: false, message: 'Network error. Please check your connection.' };
        }
    }

    async logout() {
        if (this.isOnline.value && this.token.value) {
            try {
                await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token.value}`,
                        'Accept': 'application/json',
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        this.clearToken();
        
        // Clear offline data
        await offlineStorage.clear('user');
        await offlineStorage.clear('courses');
        await offlineStorage.clear('classwork');
        await offlineStorage.clear('submissions');
        await offlineStorage.clear('grades');
        await offlineStorage.clear('notifications');
    }

    async refreshToken() {
        if (!this.token.value) return false;

        try {
            const response = await fetch('/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token.value}`,
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                const remember = localStorage.getItem(AUTH_REMEMBER_KEY) === 'true';
                this.storeToken(data.token, remember);
                return true;
            }
        } catch (error) {
            console.error('Token refresh error:', error);
        }

        return false;
    }

    // Queue action for later sync when offline
    async queueAction(action) {
        await offlineStorage.addPendingAction(action);
    }

    // Sync pending actions when online
    async syncPendingActions() {
        if (!this.isOnline.value || !this.token.value) return;

        const actions = await offlineStorage.getPendingActions();

        for (const action of actions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method || 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token.value}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(action.data)
                });

                if (response.ok) {
                    // Mark as synced and remove from queue
                    await offlineStorage.markActionSynced(action.id);
                }
            } catch (error) {
                console.error('Sync error for action:', action, error);
            }
        }
    }

    // Computed properties
    get isAuthenticated() {
        return computed(() => !!this.token.value && !!this.user.value);
    }

    get currentUser() {
        return computed(() => this.user.value);
    }

    get isOffline() {
        return computed(() => !this.isOnline.value);
    }
}

// Export singleton instance
const authService = new AuthService();
export default authService;
