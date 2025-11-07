// COMPLETE SERVICE WORKER RESET SCRIPT
// Copy and paste this entire script into browser console at https://elevategradschool.com

(async function() {
    console.log('🔄 Starting complete service worker reset...');
    
    // Step 1: Unregister ALL service workers
    const registrations = await navigator.serviceWorker.getRegistrations();
    console.log(`Found ${registrations.length} service worker(s)`);
    
    for (let registration of registrations) {
        console.log('Unregistering:', registration.scope);
        await registration.unregister();
    }
    
    // Step 2: Clear ALL caches
    const cacheNames = await caches.keys();
    console.log(`Found ${cacheNames.length} cache(s):`, cacheNames);
    
    for (let cacheName of cacheNames) {
        console.log('Deleting cache:', cacheName);
        await caches.delete(cacheName);
    }
    
    // Step 3: Clear IndexedDB (offline sync data)
    if (window.indexedDB) {
        const dbs = await window.indexedDB.databases();
        for (let db of dbs) {
            console.log('Deleting database:', db.name);
            window.indexedDB.deleteDatabase(db.name);
        }
    }
    
    console.log('✅ Complete reset done!');
    console.log('🔄 Refreshing page in 2 seconds...');
    
    setTimeout(() => {
        window.location.reload(true);
    }, 2000);
})();
