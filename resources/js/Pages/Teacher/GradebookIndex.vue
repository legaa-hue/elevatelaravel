<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
    myCourses: Array,
    joinedCourses: Array,
});

const allCourses = [...props.myCourses, ...props.joinedCourses];
</script>

<template>
    <Head title="Gradebook" />

    <TeacherLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gradebook</h1>
                <p class="text-gray-600 mt-1">Select a course to view and manage grades</p>
            </div>

            <!-- Courses Grid -->
            <div v-if="allCourses.length === 0" class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Courses Yet</h3>
                <p class="text-gray-600 mb-4">Create or join a course to start using the gradebook</p>
                <Link 
                    :href="route('teacher.my-courses')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Go to My Courses
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="course in allCourses"
                    :key="course.id"
                    :href="route('teacher.courses.gradebook', course.id)"
                    class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group"
                >
                    <!-- Course Header -->
                    <div class="bg-gradient-to-r from-red-900 to-red-800 p-6 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold mb-1 line-clamp-2 group-hover:underline">{{ course.title }}</h3>
                                <p class="text-red-100 text-sm">{{ course.section }}</p>
                            </div>
                            <svg class="w-6 h-6 text-white opacity-70 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>

                    <!-- Course Info -->
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ course.teacher?.first_name }} {{ course.teacher?.last_name }}</span>
                            </div>
                            
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>{{ course.students_count || 0 }} Students</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span 
                                    class="px-2 py-1 text-xs font-medium rounded"
                                    :class="{
                                        'bg-green-100 text-green-700': course.status === 'Active',
                                        'bg-yellow-100 text-yellow-700': course.status === 'Pending',
                                        'bg-gray-100 text-gray-700': course.status === 'Archived'
                                    }"
                                >
                                    {{ course.status }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Open Gradebook</span>
                                <svg class="w-5 h-5 text-red-600 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </TeacherLayout>
</template>
