<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';

const page = usePage();

const props = defineProps({
    stats: Object,
    joinedCourses: Array,
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
    <Head title="Student Dashboard" />

    <StudentLayout>
        <div class="space-y-6">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <h1 class="text-2xl md:text-3xl font-bold">Welcome back, {{ user.first_name }} {{ user.last_name }}! 👋</h1>
                <p class="mt-2 text-red-100">{{ greeting }}! Ready to continue your learning journey?</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Joined Courses</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.joinedCourses }}</p>
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
                            <p class="text-sm font-medium text-gray-600">Pending Tasks</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.pendingTasks }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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

                    <div v-if="joinedCourses.length > 0" class="space-y-4">
                        <div
                            v-for="course in joinedCourses"
                            :key="course.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ course.title }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ course.section }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ course.teacher_name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                            {{ course.units }} Units
                                        </span>
                                    </div>
                                </div>
                                <a
                                    :href="`/student/courses/${course.id}`"
                                    class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition"
                                >
                                    Open
                                </a>
                            </div>

                            <!-- Progress Preview -->
                            <div class="border-t border-gray-200 pt-3 mt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-700">Course Progress</span>
                                    <span class="text-xs font-semibold text-red-900">{{ course.progress || 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-gradient-to-r from-red-900 to-red-700 h-2 rounded-full transition-all duration-300"
                                        :style="{ width: (course.progress || 0) + '%' }"
                                    ></div>
                                </div>
                                <div class="grid grid-cols-3 gap-2 mt-3">
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ course.completed_tasks || 0 }}</p>
                                        <p class="text-xs text-gray-500">Completed</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-yellow-600">{{ course.pending_tasks || 0 }}</p>
                                        <p class="text-xs text-gray-500">Pending</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-green-600">{{ course.current_grade || 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">Grade</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="mt-2">No courses joined yet. Join a course to get started!</p>
                    </div>
                </div>

                <!-- Calendar Preview & Announcements -->
                <div class="space-y-6">
                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Upcoming Events</h2>
                            <a href="/student/calendar" class="text-sm text-red-900 hover:text-red-700 font-medium">View All →</a>
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
    </StudentLayout>
</template>
