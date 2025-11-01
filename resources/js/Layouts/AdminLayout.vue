<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const sidebarOpen = ref(true);
const showProfileDropdown = ref(false);
const page = usePage();

// Get pending courses count from shared props
const pendingCoursesCount = computed(() => page.props.pendingCoursesCount || 0);

// Load sidebar state from localStorage or default to open
onMounted(() => {
    const savedState = localStorage.getItem('adminSidebarOpen');
    if (savedState !== null) {
        sidebarOpen.value = savedState === 'true';
    } else {
        // Default to open on desktop, closed on mobile
        sidebarOpen.value = window.innerWidth >= 768;
    }
});

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('adminSidebarOpen', sidebarOpen.value);
};

const toggleProfileDropdown = () => {
    showProfileDropdown.value = !showProfileDropdown.value;
};

const logout = () => {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = route('logout');
        // Or use Inertia's delete method:
        // router.post(route('logout'));
    }
};

const menuItems = [
    { 
        name: 'Admin Dashboard', 
        route: 'admin.dashboard',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>`
    },
    { 
        name: 'Calendar', 
        route: 'admin.calendar',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>`
    },
    { 
        name: 'Class Record', 
        route: 'admin.class-record',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>`
    },
    { 
        name: 'User Management', 
        route: 'admin.users',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>`
    },
    { 
        name: 'Academic Year', 
        route: 'admin.academic-year',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
        </svg>`
    },
    { 
        name: 'Courses', 
        route: 'admin.courses.index',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>`
    },
    { 
        name: 'Audit Logs', 
        route: 'admin.audit-logs',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>`
    },
    { 
        name: 'Reports', 
        route: 'admin.reports',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>`
    },
];

const isCurrentRoute = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile overlay when sidebar open -->
        <div v-if="sidebarOpen" @click="toggleSidebar" class="fixed inset-0 z-30 bg-black bg-opacity-40 lg:hidden"></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-40 h-screen transition-transform duration-300 ease-in-out bg-white border-r border-gray-200 shadow-sm lg:translate-x-0',
                // On small screens: when closed hide off-canvas (-translate-x-full). When open show (translate-x-0 w-64).
                // On large screens (lg:), always translate-x-0, but width toggles between w-64 (open) and w-20 (collapsed).
                sidebarOpen ? 'translate-x-0 w-64 lg:w-64' : '-translate-x-full w-64 lg:w-20'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 bg-white">
                <div v-if="sidebarOpen" class="flex items-center">
                    <span class="text-xl font-bold">
                        <span class="text-gray-900">Elevate</span><span class="text-red-900">GS</span>
                    </span>
                </div>
                <button
                    @click="toggleSidebar"
                    class="p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-900 transition-colors"
                    :class="!sidebarOpen ? 'mx-auto' : ''"
                    aria-label="Toggle Sidebar"
                >
                    <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto bg-white">
                <Link
                    v-for="item in menuItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex items-center px-3 py-3 rounded-lg transition-all duration-200 group relative',
                        isCurrentRoute(item.route)
                            ? 'bg-red-900 text-white font-medium shadow-md'
                            : 'text-gray-900 hover:bg-gray-100 hover:text-red-900'
                    ]"
                >
                    <!-- Icon -->
                    <span 
                        v-html="item.icon" 
                        :class="[
                            'flex-shrink-0',
                            isCurrentRoute(item.route) ? 'text-white' : 'text-gray-700'
                        ]"
                    ></span>
                    
                    <!-- Label (visible when expanded) -->
                    <span
                        v-if="sidebarOpen"
                        class="ml-3 text-sm font-medium"
                    >
                        {{ item.name }}
                    </span>

                    <!-- Tooltip (visible when collapsed) -->
                    <div
                        v-if="!sidebarOpen"
                        class="fixed left-20 px-3 py-2 bg-white text-gray-900 text-sm font-medium rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap pointer-events-none"
                        :style="{ zIndex: 9999 }"
                    >
                        {{ item.name }}
                        <div class="absolute top-1/2 right-full -translate-y-1/2 border-[6px] border-transparent border-r-white"></div>
                        <div class="absolute top-1/2 right-full -translate-y-1/2 mr-[1px] border-[6px] border-transparent border-r-gray-200"></div>
                    </div>
                </Link>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-200 bg-white">
                <div v-if="sidebarOpen" class="text-xs text-center font-bold">
                    <span class="text-gray-900">Elevate</span><span class="text-red-900">GS</span>
                    <div class="text-[10px] text-gray-600 font-medium mt-0.5">Admin Panel</div>
                </div>
                <div v-else class="text-center">
                    <div class="text-base font-bold">
                        <span class="text-gray-900">E</span><span class="text-red-900">GS</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div
            :class="[
                'transition-all duration-300 ease-in-out min-h-screen bg-white',
                // Use left margin only on large screens; on small screens main should be full width
                sidebarOpen ? 'lg:ml-64 ml-0' : 'lg:ml-20 ml-0'
            ]"
        >
            <!-- Top Navigation Bar -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-30 shadow-sm">
                <div class="flex items-center">
                    <!-- Mobile hamburger: visible on small screens to open sidebar -->
                    <button @click="toggleSidebar" class="inline-flex items-center justify-center p-2 mr-3 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-900 lg:hidden" aria-label="Open sidebar">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-base md:text-xl font-semibold text-gray-900">Admin Dashboard</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <Link
                        :href="route('admin.courses.index')"
                        class="p-2 rounded-lg hover:bg-gray-100 relative transition-colors"
                        aria-label="Course Approval Notifications"
                    >
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            v-if="pendingCoursesCount > 0"
                            class="absolute top-0 right-0 flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-red-900 rounded-full border-2 border-white"
                        >
                            {{ pendingCoursesCount }}
                        </span>
                    </Link>

                    <!-- User Menu -->
                    <div class="relative">
                        <button
                            @click="toggleProfileDropdown"
                            class="flex items-center space-x-3 pl-3 border-l border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors"
                        >
                            <div class="text-right hidden md:block">
                                <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-600">Administrator</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-red-900 flex items-center justify-center text-white font-semibold shadow-md">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="showProfileDropdown"
                            @click.away="showProfileDropdown = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                        >
                            <!-- Mobile: Show user info -->
                            <div class="md:hidden px-4 py-3 border-b border-gray-200">
                                <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-600">Administrator</p>
                            </div>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition-colors"
                            >
                                <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 bg-white">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Ensure sidebar hamburger is hidden on mobile */
@media (max-width: 1023px) {
    /* keep it visible on mobile; no-op removed */
}
</style>
