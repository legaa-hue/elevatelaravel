<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { ref, computed } from 'vue';

const page = usePage();

// Copy to clipboard function
const copiedCode = ref(null);
const copyJoinCode = (code) => {
    navigator.clipboard.writeText(code).then(() => {
        copiedCode.value = code;
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    });
};

const props = defineProps({
    stats: Object,
    recentCourses: Array,
    upcomingEvents: Array,
    recentAnnouncements: Array,
});

const user = computed(() => page.props.auth.user);

// Get greeting based on time
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 18) return 'Good Afternoon';
    return 'Good Evening';
});

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Format time
const formatTime = (timeString) => {
    if (!timeString) return 'All Day';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};
</script>

<template>
    <Head title="Teacher Dashboard" />

    <TeacherLayout>
        <div class="space-y-6">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <h1 class="text-2xl md:text-3xl font-bold">Welcome back, {{ user.first_name }} {{ user.last_name }}! 👋</h1>
                <p class="mt-2 text-red-100">{{ greeting }}! Ready to inspire your students today?</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">My Courses</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.myCourses }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Joined Courses</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.joinedCourses }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalStudents }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Upcoming Events</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.upcomingEvents }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Courses -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Recent Courses</h2>
                    </div>

                    <div v-if="recentCourses.length > 0" class="space-y-4">
                        <div
                            v-for="course in recentCourses"
                            :key="course.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ course.title }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ course.section }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            {{ course.students_count || 0 }} Students
                                        </span>
                                        <span class="flex items-center gap-1">
                                            Code: {{ course.join_code }}
                                            <button
                                                @click="copyJoinCode(course.join_code)"
                                                class="ml-1 p-1 hover:bg-gray-100 rounded transition-colors"
                                                :title="copiedCode === course.join_code ? 'Copied!' : 'Copy code'"
                                            >
                                                <svg 
                                                    v-if="copiedCode === course.join_code" 
                                                    class="w-4 h-4 text-green-600" 
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <svg 
                                                    v-else 
                                                    class="w-4 h-4 text-gray-600 hover:text-red-900" 
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                <a
                                    :href="`/teacher/courses/${course.id}`"
                                    class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition"
                                >
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="mt-2">No courses yet. Create your first course!</p>
                    </div>
                </div>

                <!-- Calendar Preview & Announcements -->
                <div class="space-y-6">
                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Upcoming Events</h2>
                            <a href="/teacher/calendar" class="text-sm text-red-900 hover:text-red-700 font-medium">View All →</a>
                        </div>

                        <div v-if="upcomingEvents.length > 0" class="space-y-3">
                            <div
                                v-for="event in upcomingEvents"
                                :key="event.id"
                                class="border-l-4 border-red-900 pl-3 py-2"
                            >
                                <h4 class="font-semibold text-sm text-gray-900">{{ event.title }}</h4>
                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-600">
                                    <span>{{ formatDate(event.date) }}</span>
                                    <span>•</span>
                                    <span>{{ formatTime(event.time) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-6 text-gray-500 text-sm">
                            No upcoming events
                        </div>
                    </div>

                    <!-- Recent Announcements -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Announcements</h2>

                        <div v-if="recentAnnouncements.length > 0" class="space-y-3">
                            <div
                                v-for="announcement in recentAnnouncements"
                                :key="announcement.id"
                                class="border border-gray-200 rounded-lg p-3"
                            >
                                <h4 class="font-semibold text-sm text-gray-900">{{ announcement.title }}</h4>
                                <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ announcement.description }}</p>
                                <p class="text-xs text-gray-500 mt-2">{{ formatDate(announcement.date) }}</p>
                            </div>
                        </div>

                        <div v-else class="text-center py-6 text-gray-500 text-sm">
                            No announcements
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
