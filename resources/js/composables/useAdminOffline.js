import { ref, computed } from 'vue';
import offlineStorage from '../offline-storage';
import { useOfflineSync } from './useOfflineSync';

export function useAdminOffline() {
    const { isOnline, saveOfflineAction, getCachedData, cacheData } = useOfflineSync();

    // Dashboard
    const cacheDashboardData = async (data) => {
        await cacheData('dashboardCache', {
            key: 'admin_dashboard',
            data,
            cached_at: Date.now()
        });
    };

    const getCachedDashboard = async () => {
        const cached = await getCachedData('dashboardCache', 'admin_dashboard');
        return cached?.data || null;
    };

    // Programs
    const createProgramOffline = async (programData) => {
        const tempId = `temp_${Date.now()}`;
        const program = {
            ...programData,
            id: tempId,
            synced: false,
            version: 1,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        await offlineStorage.save('programs', program);
        await saveOfflineAction('create_program', programData);

        return program;
    };

    const updateProgramOffline = async (programId, programData) => {
        const program = await offlineStorage.get('programs', programId);
        if (program) {
            const updated = {
                ...program,
                ...programData,
                version: (program.version || 1) + 1,
                updated_at: new Date().toISOString()
            };

            await offlineStorage.save('programs', updated);
            await saveOfflineAction('update_program', { id: programId, ...programData, version: program.version });
        }
    };

    const deleteProgramOffline = async (programId) => {
        await saveOfflineAction('delete_program', { id: programId });
    };

    const getCachedPrograms = async () => {
        const db = await offlineStorage.getDB();
        const tx = db.transaction('programs', 'readonly');
        const store = tx.objectStore('programs');
        return await store.getAll();
    };

    const cachePrograms = async (programs) => {
        for (const program of programs) {
            await offlineStorage.save('programs', {
                ...program,
                cached_at: Date.now()
            });
        }
    };

    // Course Templates
    const createCourseTemplateOffline = async (templateData) => {
        const tempId = `temp_${Date.now()}`;
        const template = {
            ...templateData,
            id: tempId,
            synced: false,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        await offlineStorage.save('courseTemplates', template);
        await saveOfflineAction('create_course_template', templateData);

        return template;
    };

    const updateCourseTemplateOffline = async (templateId, templateData) => {
        const template = await offlineStorage.get('courseTemplates', templateId);
        if (template) {
            const updated = {
                ...template,
                ...templateData,
                updated_at: new Date().toISOString()
            };

            await offlineStorage.save('courseTemplates', updated);
            await saveOfflineAction('update_course_template', { id: templateId, ...templateData });
        }
    };

    const getCachedCourseTemplates = async (programId = null) => {
        const db = await offlineStorage.getDB();
        const tx = db.transaction('courseTemplates', 'readonly');
        const store = tx.objectStore('courseTemplates');

        if (programId) {
            const index = store.index('program_id');
            return await index.getAll(programId);
        }

        return await store.getAll();
    };

    const cacheCourseTemplates = async (templates) => {
        for (const template of templates) {
            await offlineStorage.save('courseTemplates', {
                ...template,
                cached_at: Date.now()
            });
        }
    };

    // Users (read-only caching)
    const cacheUsers = async (users) => {
        const db = await offlineStorage.getDB();

        // Check if 'users' store exists, if not, we'll skip
        if (!db.objectStoreNames.contains('users')) {
            console.warn('Users object store not available in offline storage');
            return;
        }

        const tx = db.transaction('users', 'readwrite');
        const store = tx.objectStore('users');

        for (const user of users) {
            await store.put({
                ...user,
                cached_at: Date.now()
            });
        }
    };

    const getCachedUsers = async (role = null) => {
        const db = await offlineStorage.getDB();

        if (!db.objectStoreNames.contains('users')) {
            return [];
        }

        const tx = db.transaction('users', 'readonly');
        const store = tx.objectStore('users');
        const allUsers = await store.getAll();

        if (role) {
            return allUsers.filter(user => user.role === role);
        }

        return allUsers;
    };

    // Reports (read-only caching)
    const cacheReport = async (reportType, reportData) => {
        await offlineStorage.save('reports', {
            type: reportType,
            data: reportData,
            cached_at: Date.now()
        });
    };

    const getCachedReport = async (reportType) => {
        const db = await offlineStorage.getDB();
        const tx = db.transaction('reports', 'readonly');
        const store = tx.objectStore('reports');
        const index = store.index('type');
        const reports = await index.getAll(reportType);

        if (reports.length > 0) {
            // Return most recent
            return reports.sort((a, b) => b.cached_at - a.cached_at)[0];
        }

        return null;
    };

    // Academic Years
    const cacheAcademicYears = async (years) => {
        // Store in dashboardCache for now
        await cacheData('dashboardCache', {
            key: 'academic_years',
            data: years,
            cached_at: Date.now()
        });
    };

    const getCachedAcademicYears = async () => {
        const cached = await getCachedData('dashboardCache', 'academic_years');
        return cached?.data || [];
    };

    // Smart data fetching (online-first, cache fallback)
    const fetchProgramsWithCache = async (forceFetch = false) => {
        if (isOnline.value || forceFetch) {
            try {
                const response = await fetch('/admin/programs');
                if (response.ok) {
                    const data = await response.json();
                    const programs = data.programs || data;
                    await cachePrograms(programs);
                    return programs;
                }
            } catch (error) {
                console.warn('Failed to fetch programs online, using cache:', error);
            }
        }

        return await getCachedPrograms();
    };

    const fetchCourseTemplatesWithCache = async (programId = null, forceFetch = false) => {
        if (isOnline.value || forceFetch) {
            try {
                const url = programId ? `/admin/programs/${programId}/templates` : '/admin/course-templates';
                const response = await fetch(url);
                if (response.ok) {
                    const data = await response.json();
                    const templates = data.templates || data;
                    await cacheCourseTemplates(templates);
                    return templates;
                }
            } catch (error) {
                console.warn('Failed to fetch templates online, using cache:', error);
            }
        }

        return await getCachedCourseTemplates(programId);
    };

    return {
        // State
        isOnline,

        // Dashboard
        cacheDashboardData,
        getCachedDashboard,

        // Programs
        createProgramOffline,
        updateProgramOffline,
        deleteProgramOffline,
        getCachedPrograms,
        cachePrograms,
        fetchProgramsWithCache,

        // Course Templates
        createCourseTemplateOffline,
        updateCourseTemplateOffline,
        getCachedCourseTemplates,
        cacheCourseTemplates,
        fetchCourseTemplatesWithCache,

        // Users
        cacheUsers,
        getCachedUsers,

        // Reports
        cacheReport,
        getCachedReport,

        // Academic Years
        cacheAcademicYears,
        getCachedAcademicYears
    };
}
