                                                                                                                                                                                                                                                                                                                                                                                                                                                                <script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);
const joinedCoursesDropdownOpen = ref(false);
const notificationDropdownOpen = ref(false);
const showJoinCourseModal = ref(false);
const joinedCourses = ref([]);
const loadingJoinedCourses = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loadingNotifications = ref(false);

const user = computed(() => page.props.auth.user);
const activeAcademicYear = computed(() => page.props.activeAcademicYear);

// Navigation items for student
const navigation = [
    { name: 'Dashboard', href: '/student/dashboard', route: 'student.dashboard' },
    { name: 'Calendar', href: '/student/calendar', route: 'student.calendar' },
    { name: 'Progress Tracking', href: '/student/progress', route: 'student.progress' },
];

// Check if route is active
const isActive = (routeName) => {
    return route().current(routeName);
};

// Join Course Form
const joinCourseForm = ref({
    join_code: '',
    processing: false,
    errors: {}
});

// Load joined courses
const loadJoinedCourses = async () => {
    loadingJoinedCourses.value = true;
    try {
        const response = await axios.get('/student/joined-courses', {
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

// Submit Join Course
const submitJoinCourse = () => {
    joinCourseForm.value.processing = true;
    joinCourseForm.value.errors = {};

    router.post('/student/courses/join', {
        join_code: joinCourseForm.value.join_code
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showJoinCourseModal.value = false;
            joinCourseForm.value.join_code = '';
            loadJoinedCourses();
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

// Toggle sidebar with persistence
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('studentSidebarOpen', sidebarOpen.value.toString());
};

// Load notifications
const loadNotifications = async () => {
    loadingNotifications.value = true;
    try {
        const response = await axios.get('/student/notifications');
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
        const response = await axios.get('/student/notifications/unread-count');
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
        await axios.post(`/student/notifications/${notificationId}/read`);
        loadNotifications();
    } catch (error) {
        console.error('Failed to mark notification as read', error);
    }
};

// Mark all as read
const markAllAsRead = async () => {
    try {
        await axios.post('/student/notifications/read-all');
        loadNotifications();
    } catch (error) {
        console.error('Failed to mark all as read', error);
    }
};

// Delete notification
const deleteNotification = async (notificationId) => {
    try {
        await axios.delete(`/student/notifications/${notificationId}`);
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
        case 'material':
        case 'classwork':
            return 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
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
    // Check localStorage first, then default to open on desktop
    const savedState = localStorage.getItem('studentSidebarOpen');
    if (savedState !== null) {
        sidebarOpen.value = savedState === 'true';
    } else {
        // Default to open on desktop
        sidebarOpen.value = window.matchMedia('(min-width: 1024px)').matches;
    }

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
                        <p class="text-xs text-gray-600">Student Portal</p>
                    </div>
                </div>
                <button 
                    @click="toggleSidebar" 
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

                    <!-- Progress Tracking Icon -->
                    <svg v-else-if="item.name === 'Progress Tracking'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>

                    <!-- Label (visible when sidebar is open) -->
                    <span v-if="sidebarOpen" class="font-medium">{{ item.name }}</span>
                    
                    <!-- Tooltip (visible when sidebar is closed) -->
                    <span v-if="!sidebarOpen" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        {{ item.name }}
                    </span>
                </Link>

                <!-- Joined Courses Dropdown -->
                <div>
                    <button 
                        @click="toggleJoinedCoursesDropdown"
                        :class="[
                            'w-full flex items-center justify-between gap-3 px-3 py-3 rounded-lg transition-all duration-200 group relative',
                            'text-gray-700 hover:bg-gray-100 hover:text-red-900'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span v-if="sidebarOpen" class="font-medium">Joined Courses</span>
                        </div>
                        <svg v-if="sidebarOpen" :class="['w-4 h-4 transition-transform', joinedCoursesDropdownOpen && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        
                        <!-- Tooltip when closed -->
                        <span v-if="!sidebarOpen" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            Joined Courses
                        </span>
                    </button>

                    <!-- Dropdown Content -->
                    <div v-if="joinedCoursesDropdownOpen && sidebarOpen" class="mt-2 ml-9 space-y-1">
                        <div v-if="loadingJoinedCourses" class="px-3 py-2 text-sm text-gray-500">
                            Loading...
                        </div>
                        <Link
                            v-else-if="joinedCourses.length > 0"
                            v-for="course in joinedCourses"
                            :key="course.id"
                            :href="`/student/courses/${course.id}`"
                            class="block px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-900 rounded-lg transition"
                        >
                            {{ course.title }}
                        </Link>
                        <div v-else class="px-3 py-2 text-sm text-gray-500">
                            No courses joined yet
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User Section at Bottom -->
            <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-gray-200 bg-white">
                <div v-if="sidebarOpen" class="space-y-2">
                    <!-- User Info -->
                    <div class="flex items-center gap-3 px-3 py-2">
                        <div class="w-8 h-8 bg-red-900 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            {{ user.first_name?.[0] }}{{ user.last_name?.[0] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="text-xs text-gray-500">Student</p>
                        </div>
                    </div>

                    <!-- Academic Year -->
                    <div v-if="activeAcademicYear" class="px-3 py-2 bg-red-50 rounded-lg">
                        <p class="text-xs font-medium text-red-900">Academic Year</p>
                        <p class="text-sm font-bold text-red-900">{{ activeAcademicYear }}</p>
                    </div>

                    <!-- Logout Button -->
                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-900 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </div>
                <div v-else class="flex justify-center">
                    <button
                        @click="logout"
                        class="p-2 text-red-900 hover:bg-red-50 rounded-lg transition-colors group relative"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            Logout
                        </span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="['transition-all duration-300 min-h-screen', sidebarOpen ? 'lg:ml-64' : 'lg:ml-20']">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30 h-16">
                <div class="flex items-center justify-between px-4 h-full">
                    <div class="flex items-center gap-4">
                        <button 
                            @click="toggleSidebar"
                            class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Join Course Button -->
                        <button
                            @click="showJoinCourseModal = true"
                            class="flex items-center gap-2 px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg transition font-medium text-sm shadow-md"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span>Join Course</span>
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
                                                    notification.type === 'announcement' ? 'bg-yellow-100 text-yellow-700' :
                                                    notification.type === 'material' ? 'bg-blue-100 text-blue-700' :
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
                                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <div class="w-8 h-8 bg-red-900 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    {{ user.first_name?.[0] }}{{ user.last_name?.[0] }}
                                </div>
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
                                    <p class="text-xs text-gray-500">Student</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="profileDropdownOpen"
                                @click.away="profileDropdownOpen = false"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                            >
                                <Link
                                    href="/profile"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Profile
                                </Link>
                                <button
                                    @click="logout"
                                    class="w-full text-left px-4 py-2 text-sm text-red-900 hover:bg-red-50"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 md:p-6">
                <slot />
            </main>
        </div>

        <!-- Join Course Modal -->
        <div v-if="showJoinCourseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showJoinCourseModal = false"></div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course Join Code</label>
                        <input
                            v-model="joinCourseForm.join_code"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Enter 6-digit code"
                            maxlength="6"
                            required
                        />
                        <p v-if="joinCourseForm.errors.join_code" class="mt-1 text-sm text-red-600">
                            {{ joinCourseForm.errors.join_code }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showJoinCourseModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="joinCourseForm.processing"
                            class="flex-1 px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition disabled:opacity-50"
                        >
                            {{ joinCourseForm.processing ? 'Joining...' : 'Join Course' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
