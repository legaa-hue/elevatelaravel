<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);
const joinedCoursesDropdownOpen = ref(false);
const showJoinCourseModal = ref(false);
const joinedCourses = ref([]);
const loadingJoinedCourses = ref(false);

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
