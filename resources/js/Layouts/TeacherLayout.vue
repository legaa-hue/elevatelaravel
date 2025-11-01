<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const sidebarOpen = ref(false); // default collapsed; will be set on mount based on viewport
const profileDropdownOpen = ref(false);
const coursesDropdownOpen = ref(false);
const joinedCoursesDropdownOpen = ref(false);
const notificationDropdownOpen = ref(false);
const showCreateCourseModal = ref(false);
const showJoinCourseModal = ref(false);
const myCourses = ref([]);
const joinedCourses = ref([]);
const loadingCourses = ref(false);
const loadingJoinedCourses = ref(false);
const programs = ref([]);
const courseTemplates = ref([]);
const loadingPrograms = ref(false);
const loadingTemplates = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loadingNotifications = ref(false);

const user = computed(() => page.props.auth.user);
const activeAcademicYear = computed(() => page.props.activeAcademicYear);

// Navigation items
        const navigation = [
            { name: 'Dashboard', href: '/teacher/dashboard', route: 'teacher.dashboard' },
            { name: 'Calendar', href: '/teacher/calendar', route: 'teacher.calendar' },
            { name: 'Class Record', href: '/teacher/class-record', route: 'teacher.class-record' },
            { name: 'Reports', href: '/teacher/reports', route: 'teacher.reports' },
        ];// Check if route is active
const isActive = (routeName) => {
    return route().current(routeName);
};

// Load programs from database
const loadPrograms = async () => {
    loadingPrograms.value = true;
    try {
        const response = await axios.get('/teacher/programs/list');
        programs.value = response.data;
    } catch (error) {
        console.error('Failed to load programs:', error);
    } finally {
        loadingPrograms.value = false;
    }
};

// Load course templates for selected program and type
const loadCourseTemplates = async (programId, courseType) => {
    if (!programId || !courseType) {
        courseTemplates.value = [];
        return;
    }
    
    loadingTemplates.value = true;
    try {
        const response = await axios.get(`/teacher/programs/${programId}/course-templates`, {
            params: { course_type: courseType }
        });
        courseTemplates.value = response.data;
    } catch (error) {
        console.error('Failed to load course templates:', error);
        courseTemplates.value = [];
    } finally {
        loadingTemplates.value = false;
    }
};

// Create Course Form
const createCourseForm = ref({
    program_id: null,
    courseType: '',
    course_template_id: null,
    selectedCourse: null,
    title: '',
    section: '',
    description: '',
    units: 3,
    academic_year_id: null,
    processing: false,
    errors: {}
});

// Watch for program or course type changes
watch(() => createCourseForm.value.program_id, () => {
    createCourseForm.value.course_template_id = null;
    createCourseForm.value.selectedCourse = null;
    createCourseForm.value.title = '';
    createCourseForm.value.units = 3;
    
    if (createCourseForm.value.program_id && createCourseForm.value.courseType) {
        loadCourseTemplates(createCourseForm.value.program_id, createCourseForm.value.courseType);
    } else {
        courseTemplates.value = [];
    }
});

watch(() => createCourseForm.value.courseType, () => {
    createCourseForm.value.course_template_id = null;
    createCourseForm.value.selectedCourse = null;
    createCourseForm.value.title = '';
    createCourseForm.value.units = 3;
    
    if (createCourseForm.value.program_id && createCourseForm.value.courseType) {
        loadCourseTemplates(createCourseForm.value.program_id, createCourseForm.value.courseType);
    } else {
        courseTemplates.value = [];
    }
});

// Watch for course selection to auto-fill title and units
const selectCourse = (course) => {
    createCourseForm.value.selectedCourse = course;
    createCourseForm.value.course_template_id = course.id;
    createCourseForm.value.title = `${course.course_code} – ${course.course_name}`;
    createCourseForm.value.units = course.units;
};

// Load programs initially so teacher sees up-to-date list
onMounted(() => {
    loadPrograms();

    // Reload programs when window regains focus (helps pick up admin changes made in another tab)
    const onFocus = () => {
        loadPrograms();
    };
    window.addEventListener('focus', onFocus);

    // cleanup
    onUnmounted(() => {
        window.removeEventListener('focus', onFocus);
    });
});

// Join Course Form
const joinCourseForm = ref({
    join_code: '',
    processing: false,
    errors: {}
});

// Open Create Course Modal
const openCreateCourseModal = () => {
    if (activeAcademicYear.value) {
        createCourseForm.value.academic_year_id = activeAcademicYear.value.id;
    }
    loadPrograms();
    showCreateCourseModal.value = true;
};

// Submit Create Course
const submitCreateCourse = () => {
    createCourseForm.value.processing = true;
    createCourseForm.value.errors = {};

    router.post('/teacher/courses', {
        program_id: createCourseForm.value.program_id,
        course_template_id: createCourseForm.value.course_template_id,
        section: createCourseForm.value.section,
        description: createCourseForm.value.description,
        academic_year_id: createCourseForm.value.academic_year_id,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateCourseModal.value = false;
            createCourseForm.value = {
                program_id: null,
                courseType: '',
                course_template_id: null,
                selectedCourse: null,
                title: '',
                section: '',
                description: '',
                units: 3,
                academic_year_id: activeAcademicYear.value?.id || null,
                processing: false,
                errors: {}
            };
        },
        onError: (errors) => {
            createCourseForm.value.errors = errors;
            createCourseForm.value.processing = false;
        },
        onFinish: () => {
            createCourseForm.value.processing = false;
        }
    });
};

// Submit Join Course
const submitJoinCourse = () => {
    joinCourseForm.value.processing = true;
    joinCourseForm.value.errors = {};

    router.post('/teacher/courses/join', {
        join_code: joinCourseForm.value.join_code,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showJoinCourseModal.value = false;
            joinCourseForm.value = {
                join_code: '',
                processing: false,
                errors: {}
            };
        },
        onError: (errors) => {
            joinCourseForm.value.errors = errors;
            joinCourseForm.value.processing = false;
        },
        onFinish: () => {
            joinCourseForm.value.processing = false;
        }
    });
};

// Logout
const logout = () => {
    router.post('/logout');
};

// Get initials for profile
const getInitials = (user) => {
    if (!user) return 'T';
    const first = user.first_name?.[0] || '';
    const last = user.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'T';
};

// Load my courses for dropdown
const loadMyCourses = async () => {
    loadingCourses.value = true;
    try {
        const response = await axios.get('/teacher/my-courses');
        myCourses.value = response.data.courses || [];
    } catch (error) {
        console.error('Failed to load courses', error);
    } finally {
        loadingCourses.value = false;
    }
};

// Toggle courses dropdown
const toggleCoursesDropdown = () => {
    coursesDropdownOpen.value = !coursesDropdownOpen.value;
    if (coursesDropdownOpen.value && myCourses.value.length === 0) {
        loadMyCourses();
    }
};

// Load joined courses
const loadJoinedCourses = async () => {
    loadingJoinedCourses.value = true;
    try {
        const response = await axios.get('/teacher/joined-courses', {
            headers: {
                'Accept': 'application/json'
            }
        });
        joinedCourses.value = response.data;
    } catch (error) {
        console.error('Failed to load joined courses', error);
    } finally {
        loadingJoinedCourses.value = false;
    }
};

// Toggle joined courses dropdown
const toggleJoinedCoursesDropdown = () => {
    joinedCoursesDropdownOpen.value = !joinedCoursesDropdownOpen.value;
    if (joinedCoursesDropdownOpen.value && joinedCourses.value.length === 0) {
        loadJoinedCourses();
    }
};

// Load notifications
const loadNotifications = async () => {
    loadingNotifications.value = true;
    try {
        const response = await axios.get('/teacher/notifications');
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to load notifications', error);
    } finally {
        loadingNotifications.value = false;
    }
};

// Load unread count only
const loadUnreadCount = async () => {
    try {
        const response = await axios.get('/teacher/notifications/unread-count');
        unreadCount.value = response.data.count;
    } catch (error) {
        console.error('Failed to load unread count', error);
    }
};

// Toggle notifications dropdown
const toggleNotifications = () => {
    notificationDropdownOpen.value = !notificationDropdownOpen.value;
    if (notificationDropdownOpen.value) {
        loadNotifications();
    }
};

// Mark notification as read
const markAsRead = async (notificationId) => {
    try {
        await axios.post(`/teacher/notifications/${notificationId}/read`);
        loadNotifications();
    } catch (error) {
        console.error('Failed to mark notification as read', error);
    }
};

// Mark all as read
const markAllAsRead = async () => {
    try {
        await axios.post('/teacher/notifications/read-all');
        loadNotifications();
    } catch (error) {
        console.error('Failed to mark all as read', error);
    }
};

// Delete notification
const deleteNotification = async (notificationId) => {
    try {
        await axios.delete(`/teacher/notifications/${notificationId}`);
        loadNotifications();
    } catch (error) {
        console.error('Failed to delete notification', error);
    }
};

// Navigate to notification URL
const handleNotificationClick = async (notification) => {
    if (!notification.is_read) {
        await markAsRead(notification.id);
    }
    if (notification.data?.url) {
        router.visit(notification.data.url);
        notificationDropdownOpen.value = false;
    }
};

// Get notification icon based on type
const getNotificationIcon = (type) => {
    switch (type) {
        case 'submission':
            return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
        case 'announcement':
            return 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z';
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

// Time ago helper
const timeAgo = (date) => {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + ' years ago';
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + ' months ago';
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + ' days ago';
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + ' hours ago';
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + ' minutes ago';
    return Math.floor(seconds) + ' seconds ago';
};

// Poll for new notifications every 30 seconds
let notificationInterval;

onMounted(() => {
    // Open sidebar by default on desktop (lg >= 1024px), keep collapsed on mobile
    try {
        sidebarOpen.value = window.matchMedia('(min-width: 1024px)').matches;
    } catch (e) {
        // fallback: keep collapsed
        sidebarOpen.value = false;
    }

    loadMyCourses();
    loadJoinedCourses();
    loadUnreadCount();
    
    // Poll for new notifications every 30 seconds
    notificationInterval = setInterval(() => {
        loadUnreadCount();
    }, 30000);
});

onUnmounted(() => {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Toast Notification -->
        <transition name="fade">
            <div v-if="showToast" class="fixed top-6 right-6 z-[9999] bg-green-600 text-white px-6 py-3 rounded shadow-lg flex items-center gap-3 animate-fade-in">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ toastMessage }}</span>
                <button @click="hideToast" class="ml-4 text-white hover:text-gray-200 focus:outline-none">&times;</button>
            </div>
        </transition>
        <!-- Mobile Sidebar Overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="[
            'fixed top-0 left-0 h-full bg-white shadow-xl text-gray-900 transform transition-all duration-300 ease-in-out border-r border-gray-200',
            sidebarOpen ? 'w-64 z-50' : 'w-20 z-40',
            'lg:translate-x-0',
            !sidebarOpen && '-translate-x-full lg:translate-x-0'
        ]">
            <!-- Logo -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 h-16">
                <div v-if="sidebarOpen" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-900 to-red-700 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-md">
                        E
                    </div>
                    <div>
                        <h1 class="text-lg font-bold bg-gradient-to-r from-black to-red-900 bg-clip-text text-transparent">ElevateGS</h1>
                        <p class="text-xs text-gray-600">Teacher Portal</p>
                    </div>
                </div>
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    :class="[
                        'text-gray-600 hover:text-red-900 hover:bg-red-50 p-2 rounded-lg transition',
                        !sidebarOpen && 'mx-auto'
                    ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="px-3 py-6 space-y-2 overflow-y-auto h-[calc(100vh-180px)]">
                <!-- Regular Navigation Links -->
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group relative',
                        isActive(item.route)
                            ? 'bg-red-50 text-red-900 shadow-sm font-semibold'
                            : 'text-gray-700 hover:bg-gray-100 hover:text-red-900'
                    ]"
                >
                    <!-- Dashboard Icon -->
                    <svg v-if="item.name === 'Dashboard'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <!-- Calendar Icon -->
                    <svg v-else-if="item.name === 'Calendar'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <!-- Class Record Icon -->
                    <svg v-else-if="item.name === 'Class Record'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <!-- Reports Icon -->
                    <svg v-else-if="item.name === 'Reports'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    
                    <span v-if="sidebarOpen" class="text-sm">{{ item.name }}</span>
                    
                    <!-- Tooltip for collapsed sidebar -->
                    <div v-if="!sidebarOpen" class="fixed left-[88px] px-3 py-2 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 z-[9999] shadow-lg">
                        {{ item.name }}
                        <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                    </div>
                </Link>

                <!-- My Courses Dropdown -->
                <div class="mb-2">
                    <button
                        @click="toggleCoursesDropdown"
                        :class="[
                            'w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group relative',
                            'text-gray-700 hover:bg-gray-100 hover:text-red-900'
                        ]"
                    >
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span v-if="sidebarOpen" class="text-sm flex-1 text-left">My Courses</span>
                        <svg v-if="sidebarOpen" class="w-4 h-4 transition-transform" :class="coursesDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        
                        <!-- Tooltip for collapsed sidebar -->
                        <div v-if="!sidebarOpen" class="fixed left-[88px] px-3 py-2 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 z-[9999] shadow-lg">
                            My Courses
                            <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                        </div>
                    </button>

                    <!-- Courses Dropdown List -->
                    <div v-if="coursesDropdownOpen && sidebarOpen" class="ml-3 mt-2 space-y-1 max-h-60 overflow-y-auto">
                        <div v-if="loadingCourses" class="px-3 py-4 text-center text-gray-500 text-xs">
                            <svg class="animate-spin h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs mt-1">Loading...</p>
                        </div>

                        <div v-else-if="myCourses.length === 0" class="px-3 py-4 text-center text-gray-500 text-xs">
                            <p>No courses yet</p>
                        </div>

                        <Link
                            v-else
                            v-for="course in myCourses"
                            :key="course.id"
                            :href="`/teacher/courses/${course.id}`"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition text-sm text-gray-700"
                        >
                            <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ course.title.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate">{{ course.title }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ course.section }}</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Joined Courses Dropdown -->
                <div class="mb-2">
                    <button
                        @click="toggleJoinedCoursesDropdown"
                        :class="[
                            'w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group relative',
                            'text-gray-700 hover:bg-gray-100 hover:text-red-900'
                        ]"
                    >
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span v-if="sidebarOpen" class="text-sm flex-1 text-left">Joined Courses</span>
                        <svg v-if="sidebarOpen" class="w-4 h-4 transition-transform" :class="joinedCoursesDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        
                        <!-- Tooltip for collapsed sidebar -->
                        <div v-if="!sidebarOpen" class="fixed left-[88px] px-3 py-2 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 z-[9999] shadow-lg">
                            Joined Courses
                            <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                        </div>
                    </button>

                    <!-- Joined Courses Dropdown List -->
                    <div v-if="joinedCoursesDropdownOpen && sidebarOpen" class="ml-3 mt-2 space-y-1 max-h-60 overflow-y-auto">
                        <div v-if="loadingJoinedCourses" class="px-3 py-4 text-center text-gray-500 text-xs">
                            <svg class="animate-spin h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs mt-1">Loading...</p>
                        </div>

                        <div v-else-if="joinedCourses.length === 0" class="px-3 py-4 text-center text-gray-500 text-xs">
                            <p>No joined courses</p>
                        </div>

                        <Link
                            v-else
                            v-for="course in joinedCourses"
                            :key="course.id"
                            :href="`/teacher/courses/${course.id}`"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition text-sm text-gray-700"
                        >
                            <div class="w-6 h-6 bg-green-600 rounded flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ course.title.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate">{{ course.title }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ course.section }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Academic Year Info -->
            <div v-if="activeAcademicYear" class="absolute bottom-4 left-3 right-3 bg-red-50 border border-red-200 rounded-lg p-3 text-xs">
                <p class="text-gray-600 font-medium" :class="sidebarOpen ? '' : 'text-center'">{{ sidebarOpen ? 'Active Year:' : 'Year' }}</p>
                <p v-if="sidebarOpen" class="text-red-900 font-bold">{{ activeAcademicYear.year_name }}</p>
                <p v-else class="text-red-900 font-bold text-center">{{ activeAcademicYear.year_name.split('-')[0] }}</p>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="[
            'transition-all duration-300',
            sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'
        ]">
            <!-- Top Navigation Bar -->
            <header class="fixed top-0 right-0 z-30 bg-white shadow-sm border-b border-gray-200" :class="[
                'transition-all duration-300',
                sidebarOpen ? 'left-0 lg:left-64' : 'left-0 lg:left-20'
            ]">
                <div class="flex items-center justify-between px-4 py-3">
                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page Title (Mobile) -->
                    <h2 class="lg:hidden text-lg font-semibold text-gray-900">Teacher Portal</h2>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 ml-auto">
                        <button
                            @click="openCreateCourseModal"
                            class="hidden sm:flex items-center gap-2 px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg font-medium transition shadow-sm"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden md:inline">Create Course</span>
                        </button>

                        <button
                            @click="showJoinCourseModal = true"
                            class="hidden sm:flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition shadow-sm"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span class="hidden md:inline">Join Course</span>
                        </button>

                        <!-- Mobile Action Buttons -->
                        <button @click="openCreateCourseModal" class="sm:hidden p-2 bg-red-900 text-white rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>

                        <button @click="showJoinCourseModal = true" class="sm:hidden p-2 bg-yellow-500 text-white rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </button>

                        <!-- Notification Icon -->
                        <div class="relative">
                            <button
                                @click="toggleNotifications"
                                class="relative p-2 text-gray-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors"
                                title="Notifications"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <!-- Notification Badge -->
                                <span v-if="unreadCount > 0" class="absolute top-1 right-1 min-w-[18px] h-[18px] bg-red-600 text-white text-xs font-bold rounded-full flex items-center justify-center px-1">
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </button>

                            <!-- Notifications Dropdown -->
                            <div
                                v-if="notificationDropdownOpen"
                                @click.away="notificationDropdownOpen = false"
                                class="absolute right-0 mt-2 w-96 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-[500px] overflow-hidden flex flex-col"
                            >
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                                    <h3 class="font-semibold text-gray-900">Notifications</h3>
                                    <button
                                        v-if="unreadCount > 0"
                                        @click="markAllAsRead"
                                        class="text-xs text-red-900 hover:text-red-700 font-medium"
                                    >
                                        Mark all as read
                                    </button>
                                </div>

                                <!-- Notifications List -->
                                <div class="overflow-y-auto flex-1">
                                    <div v-if="loadingNotifications" class="p-8 text-center text-gray-500">
                                        <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="text-sm">Loading notifications...</p>
                                    </div>

                                    <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500">
                                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <p class="text-sm font-medium">No notifications yet</p>
                                        <p class="text-xs mt-1">We'll notify you when something new arrives</p>
                                    </div>

                                    <div v-else>
                                        <div
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            @click="handleNotificationClick(notification)"
                                            :class="[
                                                'px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors',
                                                !notification.is_read && 'bg-blue-50'
                                            ]"
                                        >
                                            <div class="flex gap-3">
                                                <div :class="[
                                                    'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                                                    notification.type === 'submission' ? 'bg-green-100 text-green-700' :
                                                    notification.type === 'announcement' ? 'bg-yellow-100 text-yellow-700' :
                                                    'bg-gray-100 text-gray-700'
                                                ]">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getNotificationIcon(notification.type)" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <p class="text-sm font-semibold text-gray-900">{{ notification.title }}</p>
                                                        <button
                                                            @click.stop="deleteNotification(notification.id)"
                                                            class="text-gray-400 hover:text-red-600 transition-colors"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">{{ timeAgo(notification.created_at) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Dropdown -->
                        <div class="relative">
                            <button
                                @click="profileDropdownOpen = !profileDropdownOpen"
                                class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition"
                            >
                                <div v-if="user && user.profile_picture" class="w-10 h-10 rounded-full overflow-hidden border-2 border-gray-200">
                                    <img 
                                        :src="user.profile_picture.startsWith('http') ? user.profile_picture : `/storage/${user.profile_picture}`" 
                                        :alt="`${user.first_name} ${user.last_name}`" 
                                        class="w-full h-full object-cover" 
                                    />
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-red-900 to-red-700 text-white flex items-center justify-center font-bold text-sm shadow-md">
                                    {{ getInitials(user) }}
                                </div>
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ user?.first_name || 'Teacher' }} {{ user?.last_name || '' }}
                                    </p>
                                    <p class="text-xs text-gray-500">Teacher</p>
                                </div>
                                <svg class="hidden md:block w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div v-if="profileDropdownOpen" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ user?.first_name || 'Teacher' }} {{ user?.last_name || '' }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">{{ user?.email || '' }}</p>
                                </div>
                                <Link :href="route('profile.edit')" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile Settings
                                </Link>
                                <button @click="logout" class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="pt-20 p-6 bg-gray-50 min-h-screen">
                <slot />
            </main>
        </div>

        <!-- Create Course Modal -->
        <div v-if="showCreateCourseModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCreateCourseModal = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Create New Course</h3>
                        <button @click="showCreateCourseModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateCourse" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Program *</label>
                            <select
                                v-model="createCourseForm.program_id"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            >
                                <option :value="null">Select Program</option>
                                <option v-for="program in programs" :key="program.id" :value="program.id">
                                    {{ program.name }}
                                </option>
                            </select>
                            <p v-if="createCourseForm.errors.program_id" class="mt-1 text-sm text-red-600">{{ createCourseForm.errors.program_id }}</p>
                        </div>

                        <div v-if="createCourseForm.program_id">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Type *</label>
                            <select
                                v-model="createCourseForm.courseType"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            >
                                <option value="">Select Course Type</option>
                                <option value="Basic Course">Basic Courses</option>
                                <option value="Major Course">Major Courses</option>
                                <option value="Thesis">Thesis Courses</option>
                            </select>
                        </div>

                        <div v-if="createCourseForm.courseType">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Course *</label>
                            <div v-if="loadingTemplates" class="text-center py-4 text-gray-500">
                                Loading courses...
                            </div>
                            <div v-else-if="courseTemplates.length > 0" class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg">
                                <button
                                    v-for="course in courseTemplates"
                                    :key="course.id"
                                    type="button"
                                    @click="selectCourse(course)"
                                    :class="[
                                        'w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-200 last:border-b-0 transition',
                                        createCourseForm.selectedCourse?.id === course.id ? 'bg-red-50 border-l-4 border-l-red-900' : ''
                                    ]"
                                >
                                    <div class="font-medium text-gray-900">{{ course.course_code }}</div>
                                    <div class="text-sm text-gray-600">{{ course.course_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ course.units }} units</div>
                                </button>
                            </div>
                            <div v-else class="text-center py-4 text-gray-500">
                                No courses available for this program and type.
                            </div>
                        </div>

                        <div v-if="createCourseForm.selectedCourse">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Title (Auto-filled)</label>
                            <input
                                v-model="createCourseForm.title"
                                type="text"
                                readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                            />
                            <p v-if="createCourseForm.errors.course_template_id" class="mt-1 text-sm text-red-600">{{ createCourseForm.errors.course_template_id }}</p>
                        </div>

                        <div v-if="createCourseForm.selectedCourse">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Units (Auto-filled)</label>
                            <input
                                v-model="createCourseForm.units"
                                type="number"
                                readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                            />
                        </div>

                        <div v-if="createCourseForm.selectedCourse">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                            <input
                                v-model="createCourseForm.section"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                placeholder="e.g., Section A"
                            />
                            <p v-if="createCourseForm.errors.section" class="mt-1 text-sm text-red-600">{{ createCourseForm.errors.section }}</p>
                        </div>

                        <div v-if="createCourseForm.selectedCourse">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <input
                                :value="activeAcademicYear?.year_name || 'No active year'"
                                type="text"
                                readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                            />
                            <p class="mt-1 text-xs text-gray-500">Academic year is set by admin</p>
                        </div>

                        <div v-if="createCourseForm.selectedCourse">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea
                                v-model="createCourseForm.description"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                placeholder="Course description..."
                            ></textarea>
                            <p v-if="createCourseForm.errors.description" class="mt-1 text-sm text-red-600">{{ createCourseForm.errors.description }}</p>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="showCreateCourseModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="createCourseForm.processing || !activeAcademicYear || !createCourseForm.selectedCourse"
                                class="flex-1 px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ createCourseForm.processing ? 'Creating...' : 'Create Course' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Join Course Modal -->
        <div v-if="showJoinCourseModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="showJoinCourseModal = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Join Course</h3>
                        <button @click="showJoinCourseModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitJoinCourse" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Join Code *</label>
                            <input
                                v-model="joinCourseForm.join_code"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-center text-2xl font-bold tracking-wider uppercase"
                                placeholder="ABC123"
                                maxlength="10"
                            />
                            <p v-if="joinCourseForm.errors.join_code" class="mt-1 text-sm text-red-600">{{ joinCourseForm.errors.join_code }}</p>
                            <p class="mt-1 text-xs text-gray-500">Enter the course join code provided by the course owner</p>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="showJoinCourseModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="joinCourseForm.processing"
                                class="flex-1 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ joinCourseForm.processing ? 'Joining...' : 'Join Course' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for sidebar */
nav::-webkit-scrollbar {
    width: 6px;
}

nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>
