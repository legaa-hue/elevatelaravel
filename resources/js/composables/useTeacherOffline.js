import { ref, computed } from 'vue';
import offlineStorage from '../offline-storage';
import { useOfflineSync } from './useOfflineSync';
import { useOfflineFiles } from './useOfflineFiles';

export function useTeacherOffline() {
    const { isOnline, saveOfflineAction, getCachedData, cacheData } = useOfflineSync();
    const { downloadCourseFiles } = useOfflineFiles();

    // Dashboard
    const cacheDashboardData = async (data) => {
        await cacheData('dashboardCache', {
            key: 'dashboard',
            data,
            cached_at: Date.now()
        });
    };

    const getCachedDashboard = async () => {
        const cached = await getCachedData('dashboardCache', 'dashboard');
        return cached?.data || null;
    };

    // Courses
    const createCourseOffline = async (courseData) => {
        // Generate temporary ID
        const tempId = `temp_${Date.now()}`;
        const course = {
            ...courseData,
            id: tempId,
            synced: false,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        // Save to local storage
        await offlineStorage.save('courses', course);

        // Queue for sync
        await saveOfflineAction('create_course', courseData);

        return course;
    };

    const updateCourseOffline = async (courseId, courseData) => {
        // Update local copy
        const course = await offlineStorage.get('courses', courseId);
        if (course) {
            const updated = {
                ...course,
                ...courseData,
                synced: false,
                updated_at: new Date().toISOString()
            };
            await offlineStorage.save('courses', updated);
        }

        // Queue for sync
        await saveOfflineAction('update_course', { id: courseId, ...courseData });

        return course;
    };

    const getCachedCourses = async (teacherId = null) => {
        if (teacherId) {
            return await offlineStorage.getByIndex('courses', 'teacher_id', teacherId);
        }
        return await offlineStorage.getAll('courses');
    };

    const cacheCourses = async (courses) => {
        await offlineStorage.saveMany('courses', courses);
    };

    // Calendar/Events
    const createEventOffline = async (eventData) => {
        const tempId = `temp_event_${Date.now()}`;
        const event = {
            ...eventData,
            id: tempId,
            synced: false,
            created_at: new Date().toISOString()
        };

        await offlineStorage.save('events', event);
        await saveOfflineAction('create_event', eventData);

        return event;
    };

    const updateEventOffline = async (eventId, eventData) => {
        const event = await offlineStorage.get('events', eventId);
        if (event) {
            const updated = { ...event, ...eventData, synced: false };
            await offlineStorage.save('events', updated);
        }

        await saveOfflineAction('update_event', { id: eventId, ...eventData });
        return event;
    };

    const deleteEventOffline = async (eventId) => {
        await offlineStorage.delete('events', eventId);
        await saveOfflineAction('delete_event', { id: eventId });
    };

    const getCachedEvents = async (userId = null) => {
        if (userId) {
            return await offlineStorage.getByIndex('events', 'user_id', userId);
        }
        return await offlineStorage.getAll('events');
    };

    const cacheEvents = async (events) => {
        await offlineStorage.saveMany('events', events);
    };

    // Classwork
    const createClassworkOffline = async (classworkData) => {
        const tempId = `temp_classwork_${Date.now()}`;
        const classwork = {
            ...classworkData,
            id: tempId,
            synced: false,
            created_at: new Date().toISOString()
        };

        await offlineStorage.save('classwork', classwork);
        await saveOfflineAction('create_classwork', classworkData);

        return classwork;
    };

    const updateClassworkOffline = async (classworkId, classworkData) => {
        const classwork = await offlineStorage.get('classwork', classworkId);
        if (classwork) {
            const updated = { ...classwork, ...classworkData, synced: false };
            await offlineStorage.save('classwork', updated);
        }

        await saveOfflineAction('update_classwork', { id: classworkId, ...classworkData });
        return classwork;
    };

    const getCachedClasswork = async (courseId = null) => {
        if (courseId) {
            return await offlineStorage.getByIndex('classwork', 'course_id', courseId);
        }
        return await offlineStorage.getAll('classwork');
    };

    const cacheClasswork = async (classworks) => {
        await offlineStorage.saveMany('classwork', classworks);
    };

    // Materials (special type of classwork)
    const createMaterialOffline = async (materialData) => {
        return await createClassworkOffline({
            ...materialData,
            type: 'material'
        });
    };

    const getCachedMaterials = async (courseId) => {
        const materials = await offlineStorage.getByIndex('materials', 'course_id', courseId);
        return materials;
    };

    const cacheMaterials = async (materials) => {
        await offlineStorage.saveMany('materials', materials);
    };

    // Gradebook
    const updateGradebookOffline = async (courseId, gradebookData) => {
        const gradebook = {
            course_id: courseId,
            data: gradebookData,
            synced: false,
            updated_at: new Date().toISOString()
        };

        await offlineStorage.save('gradebooks', gradebook);
        await saveOfflineAction('update_gradebook', { course_id: courseId, gradebook: gradebookData });

        return gradebook;
    };

    const getCachedGradebook = async (courseId) => {
        const gradebook = await offlineStorage.get('gradebooks', courseId);
        return gradebook?.data || null;
    };

    const cacheGradebook = async (courseId, gradebookData) => {
        await offlineStorage.save('gradebooks', {
            course_id: courseId,
            data: gradebookData,
            synced: true,
            updated_at: new Date().toISOString()
        });
    };

    // People/Students
    const getCachedStudents = async (courseId) => {
        return await offlineStorage.getByIndex('students', 'course_id', courseId);
    };

    const cacheStudents = async (students, courseId) => {
        const studentsWithCourse = students.map(s => ({
            ...s,
            course_id: courseId
        }));
        await offlineStorage.saveMany('students', studentsWithCourse);
    };

    // Submissions
    const gradeSubmissionOffline = async (submissionId, gradeData) => {
        const submission = await offlineStorage.get('submissions', submissionId);
        if (submission) {
            const updated = {
                ...submission,
                ...gradeData,
                synced: false,
                graded_at: new Date().toISOString()
            };
            await offlineStorage.save('submissions', updated);
        }

        await saveOfflineAction('grade_submission', {
            submission_id: submissionId,
            ...gradeData
        });

        return submission;
    };

    const getCachedSubmissions = async (classworkId = null) => {
        if (classworkId) {
            return await offlineStorage.getByIndex('submissions', 'classwork_id', classworkId);
        }
        return await offlineStorage.getAll('submissions');
    };

    const cacheSubmissions = async (submissions) => {
        await offlineStorage.saveMany('submissions', submissions);
    };

    // Reports
    const cacheReport = async (reportType, reportData) => {
        await offlineStorage.save('reports', {
            id: reportType,
            type: reportType,
            data: reportData,
            cached_at: Date.now()
        });
    };

    const getCachedReport = async (reportType) => {
        const report = await offlineStorage.get('reports', reportType);
        return report?.data || null;
    };

    // Sync indicators
    const hasUnsyncedChanges = async () => {
        const pending = await offlineStorage.getPendingActions();
        return pending.length > 0;
    };

    const getUnsyncedCount = async () => {
        const pending = await offlineStorage.getPendingActions();
        return pending.length;
    };

    // Helper to determine if data is stale (older than 5 minutes)
    const isDataStale = (cachedAt, maxAge = 5 * 60 * 1000) => {
        if (!cachedAt) return true;
        return Date.now() - cachedAt > maxAge;
    };

    // Smart data fetcher (tries online first, falls back to cache)
    const getSmartData = async (fetchFunction, cacheKey, cacheFunction) => {
        if (isOnline.value) {
            try {
                const data = await fetchFunction();
                if (data && cacheFunction) {
                    await cacheFunction(data);
                }
                return { data, source: 'online' };
            } catch (error) {
                console.warn('Online fetch failed, using cache:', error);
            }
        }

        // Fallback to cache
        const cached = await getCachedData(cacheKey);
        return {
            data: cached,
            source: 'cache',
            stale: isDataStale(cached?.cached_at)
        };
    };

    return {
        // Dashboard
        cacheDashboardData,
        getCachedDashboard,

        // Courses
        createCourseOffline,
        updateCourseOffline,
        getCachedCourses,
        cacheCourses,

        // Events
        createEventOffline,
        updateEventOffline,
        deleteEventOffline,
        getCachedEvents,
        cacheEvents,

        // Classwork
        createClassworkOffline,
        updateClassworkOffline,
        getCachedClasswork,
        cacheClasswork,

        // Materials
        createMaterialOffline,
        getCachedMaterials,
        cacheMaterials,

        // Gradebook
        updateGradebookOffline,
        getCachedGradebook,
        cacheGradebook,

        // People
        getCachedStudents,
        cacheStudents,

        // Submissions
        gradeSubmissionOffline,
        getCachedSubmissions,
        cacheSubmissions,

        // Reports
        cacheReport,
        getCachedReport,

        // Utilities
        hasUnsyncedChanges,
        getUnsyncedCount,
        isDataStale,
        getSmartData,
        downloadCourseFiles
    };
}
