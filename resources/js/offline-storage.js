// IndexedDB wrapper for offline storage
class OfflineStorage {
    constructor() {
        this.dbName = 'ElevateGS_Offline';
        this.version = 2; // Upgraded to support teacher offline features
        this.db = null;
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores if they don't exist
                if (!db.objectStoreNames.contains('courses')) {
                    const coursesStore = db.createObjectStore('courses', { keyPath: 'id' });
                    coursesStore.createIndex('teacher_id', 'teacher_id', { unique: false });
                    coursesStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                if (!db.objectStoreNames.contains('classwork')) {
                    const classworkStore = db.createObjectStore('classwork', { keyPath: 'id' });
                    classworkStore.createIndex('course_id', 'course_id', { unique: false });
                    classworkStore.createIndex('due_date', 'due_date', { unique: false });
                }

                if (!db.objectStoreNames.contains('submissions')) {
                    const submissionsStore = db.createObjectStore('submissions', { keyPath: 'id' });
                    submissionsStore.createIndex('user_id', 'user_id', { unique: false });
                    submissionsStore.createIndex('classwork_id', 'classwork_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('grades')) {
                    const gradesStore = db.createObjectStore('grades', { keyPath: 'id' });
                    gradesStore.createIndex('user_id', 'user_id', { unique: false });
                    gradesStore.createIndex('course_id', 'course_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('gradebooks')) {
                    const gradebooksStore = db.createObjectStore('gradebooks', { keyPath: 'course_id' });
                    gradebooksStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                if (!db.objectStoreNames.contains('events')) {
                    const eventsStore = db.createObjectStore('events', { keyPath: 'id' });
                    eventsStore.createIndex('user_id', 'user_id', { unique: false });
                    eventsStore.createIndex('date', 'date', { unique: false });
                }

                if (!db.objectStoreNames.contains('students')) {
                    const studentsStore = db.createObjectStore('students', { keyPath: 'id' });
                    studentsStore.createIndex('course_id', 'course_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('reports')) {
                    const reportsStore = db.createObjectStore('reports', { keyPath: 'id' });
                    reportsStore.createIndex('type', 'type', { unique: false });
                    reportsStore.createIndex('cached_at', 'cached_at', { unique: false });
                }

                if (!db.objectStoreNames.contains('materials')) {
                    const materialsStore = db.createObjectStore('materials', { keyPath: 'id' });
                    materialsStore.createIndex('course_id', 'course_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('fileCache')) {
                    const fileCacheStore = db.createObjectStore('fileCache', { keyPath: 'url' });
                    fileCacheStore.createIndex('cached_at', 'cached_at', { unique: false });
                    fileCacheStore.createIndex('course_id', 'course_id', { unique: false });
                }

                if (!db.objectStoreNames.contains('pendingActions')) {
                    const pendingStore = db.createObjectStore('pendingActions', { keyPath: 'id', autoIncrement: true });
                    pendingStore.createIndex('type', 'type', { unique: false });
                    pendingStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                if (!db.objectStoreNames.contains('user')) {
                    db.createObjectStore('user', { keyPath: 'id' });
                }

                if (!db.objectStoreNames.contains('notifications')) {
                    const notificationsStore = db.createObjectStore('notifications', { keyPath: 'id' });
                    notificationsStore.createIndex('created_at', 'created_at', { unique: false });
                }

                if (!db.objectStoreNames.contains('dashboardCache')) {
                    const dashboardStore = db.createObjectStore('dashboardCache', { keyPath: 'key' });
                    dashboardStore.createIndex('cached_at', 'cached_at', { unique: false });
                }
            };
        });
    }

    async save(storeName, data) {
        if (!this.db) await this.init();
        
        // Clone data to remove non-serializable properties (functions, Promises, etc.)
        const clonedData = JSON.parse(JSON.stringify(data));
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(clonedData);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async saveMany(storeName, items) {
        if (!this.db) await this.init();
        
        // Clone items to remove non-serializable properties
        const clonedItems = JSON.parse(JSON.stringify(items));
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            
            clonedItems.forEach(item => store.put(item));

            transaction.oncomplete = () => resolve(true);
            transaction.onerror = () => reject(transaction.error);
        });
    }

    async get(storeName, key) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAll(storeName) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getByIndex(storeName, indexName, value) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async delete(storeName, key) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    }

    async clear(storeName) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    }

    // Pending actions for offline operations
    async addPendingAction(action) {
        const actionWithTimestamp = {
            ...action,
            timestamp: Date.now(),
            synced: false
        };
        return this.save('pendingActions', actionWithTimestamp);
    }

    async getPendingActions() {
        return this.getAll('pendingActions');
    }

    async markActionSynced(id) {
        return this.delete('pendingActions', id);
    }
}

// Export singleton instance
const offlineStorage = new OfflineStorage();
export default offlineStorage;
