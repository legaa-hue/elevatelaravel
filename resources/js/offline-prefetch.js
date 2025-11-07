import axios from 'axios';
import offlineStorage from './offline-storage';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import inertiaOfflineHandler from './inertia-offline-handler-axios';

/**
 * Prefetch core Inertia pages (JSON payloads + Vue components) so they are available offline
 * without the user visiting them first.
 *
 * @param {Object} options
 * @param {string} [options.role]           Current user role (e.g., 'teacher' | 'student' | 'admin')
 * @param {string} [options.version]        Inertia version string for cache-busting/coherence
 * @param {string[]} [options.extraPaths]   Additional paths to prefetch
 */
export async function prefetchCorePages({ role, version, extraPaths = [] } = {}) {
  const base = window.location.origin;

  // Define default core routes by role
  const common = ['/', '/dashboard'];
  const teacher = ['/teacher/dashboard', '/teacher/calendar', '/teacher/class-record', '/teacher/reports'];
  const student = ['/student/dashboard'];
  const admin = ['/admin/dashboard'];

  let targets = [...common];
  if (role === 'teacher') targets.push(...teacher);
  if (role === 'student') targets.push(...student);
  if (role === 'admin') targets.push(...admin);
  if (Array.isArray(extraPaths) && extraPaths.length) targets.push(...extraPaths);

  // Deduplicate and normalize
  targets = Array.from(new Set(targets.map(p => normalizePath(p))));

  // Also define core components to preload
  const coreComponents = [
    'Dashboard',
    'Teacher/Dashboard',
    'Teacher/Calendar',
    'Teacher/ClassRecord',
    'Teacher/Reports',
    'Student/Dashboard',
    'Admin/Dashboard'
  ];

  // Filter components based on role
  let componentsToLoad = coreComponents.filter(component => {
    if (role === 'teacher') return component.startsWith('Teacher/') || component === 'Dashboard';
    if (role === 'student') return component.startsWith('Student/') || component === 'Dashboard';
    if (role === 'admin') return component.startsWith('Admin/') || component === 'Dashboard';
    return component === 'Dashboard';
  });

  // Prefetch page data (JSON)
  for (const path of targets) {
    try {
      const url = new URL(path, base).href;
      const headers = {
        'X-Inertia': 'true',
        'Accept': 'application/json',
      };
      if (version) headers['X-Inertia-Version'] = String(version);

      const res = await axios.get(url, { headers, withCredentials: true });
      if (res && res.data) {
        await offlineStorage.savePageData(res.data);
        console.log(`📦 Prefetched page data for offline: ${path}`);
      }
    } catch (e) {
      // Non-fatal: continue prefetching others
      console.warn(`⚠️ Prefetch page skipped for ${path}:`, e && (e.message || e));
    }
  }

  // Preload Vue components
  for (const componentName of componentsToLoad) {
    try {
      // Check if already cached
      if (inertiaOfflineHandler.getCachedComponent(componentName)) {
        console.log(`✅ Component already cached: ${componentName}`);
        continue;
      }

      // Load and cache the component
      const component = await resolvePageComponent(
        `./Pages/${componentName}.vue`,
        import.meta.glob('./Pages/**/*.vue')
      );

      inertiaOfflineHandler.cacheComponent(componentName, component);
      console.log(`📦 Prefetched component for offline: ${componentName}`);
    } catch (e) {
      // Non-fatal: continue prefetching others
      console.warn(`⚠️ Component prefetch skipped for ${componentName}:`, e && (e.message || e));
    }
  }
}

function normalizePath(input) {
  try {
    const u = new URL(input, window.location.origin);
    const path = u.pathname || '/';
    return path === '/' ? '/' : path.replace(/\/$/, '');
  } catch {
    const raw = (input || '').replace(window.location.origin, '');
    if (raw === '' || raw === '/') return '/';
    return raw.replace(/\/$/, '');
  }
}
