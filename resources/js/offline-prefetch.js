import axios from 'axios';
import offlineStorage from './offline-storage';

/**
 * Prefetch core Inertia pages (JSON payloads) so they are available offline
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
        console.log(`📦 Prefetched page for offline: ${path}`);
      }
    } catch (e) {
      // Non-fatal: continue prefetching others
      console.warn(`⚠️ Prefetch skipped for ${path}:`, e && (e.message || e));
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
