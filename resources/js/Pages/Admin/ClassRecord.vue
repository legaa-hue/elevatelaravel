<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    courses: {
        type: Array,
        default: () => []
    }
});

// Modal state
const showPdfModal = ref(false);
const selectedCourse = ref(null);
const pdfUrl = ref('');

// Search and filter
const searchQuery = ref('');
const statusFilter = ref('all');
const programFilter = ref('all');
const academicYearFilter = ref('all');

// Get unique programs
const programs = computed(() => {
    const uniquePrograms = [...new Set(props.courses.map(c => c.program))];
    return uniquePrograms.filter(p => p && p !== 'N/A');
});

// Get unique academic years
const academicYears = computed(() => {
    const uniqueYears = [...new Set(props.courses.map(c => c.academic_year))];
    return uniqueYears.filter(y => y && y !== 'N/A');
});

// Filtered courses
const filteredCourses = computed(() => {
    let filtered = props.courses;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(course =>
            course.title.toLowerCase().includes(query) ||
            course.section.toLowerCase().includes(query) ||
            course.teacher.first_name.toLowerCase().includes(query) ||
            course.teacher.last_name.toLowerCase().includes(query)
        );
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(course => course.status === statusFilter.value);
    }

    // Program filter
    if (programFilter.value !== 'all') {
        filtered = filtered.filter(course => course.program === programFilter.value);
    }

    // Academic Year filter
    if (academicYearFilter.value !== 'all') {
        filtered = filtered.filter(course => course.academic_year === academicYearFilter.value);
    }

    return filtered;
});

// View gradebook
const viewGradeSheet = (course) => {
    selectedCourse.value = course;
    pdfUrl.value = route('admin.class-record.grade-sheet.pdf', course.id);
    showPdfModal.value = true;
};

const closePdfModal = () => {
    showPdfModal.value = false;
    pdfUrl.value = '';
    selectedCourse.value = null;
};

const downloadPdf = () => {
    window.location.href = route('admin.class-record.grade-sheet.download', selectedCourse.value.id);
};
</script>

<template>
    <Head title="Class Records" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h1 class="text-2xl md:text-3xl font-bold">Class Records & Gradebooks</h1>
                </div>
                <p class="text-red-100">View and access gradebooks for all courses</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Courses -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Courses</p>
                            <p class="text-3xl font-bold text-red-900 mt-2">{{ courses.length }}</p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Courses -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Courses</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">{{ courses.filter(c => c.status === 'Active').length }}</p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Students -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">{{ courses.reduce((sum, c) => sum + c.students_count, 0) }}</p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Programs -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Programs</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2">{{ programs.length }}</p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search
                        </label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Course name, teacher..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        >
                            <option value="all">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                        <select
                            v-model="programFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        >
                            <option value="all">All Programs</option>
                            <option v-for="program in programs" :key="program" :value="program">
                                {{ program }}
                            </option>
                        </select>
                    </div>

                    <!-- Academic Year Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                        <select
                            v-model="academicYearFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        >
                            <option value="all">All Years</option>
                            <option v-for="year in academicYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between text-sm">
                    <span class="text-gray-600">
                        Showing <span class="font-semibold text-red-900">{{ filteredCourses.length }}</span> of 
                        <span class="font-semibold text-red-900">{{ courses.length }}</span> courses
                    </span>
                    <button
                        v-if="searchQuery || statusFilter !== 'all' || programFilter !== 'all' || academicYearFilter !== 'all'"
                        @click="searchQuery = ''; statusFilter = 'all'; programFilter = 'all'; academicYearFilter = 'all'"
                        class="text-red-600 hover:text-red-700 font-medium flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Courses List -->
            <div v-if="filteredCourses.length === 0" class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Courses Found</h3>
                <p class="text-gray-600">Try adjusting your filters</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="course in filteredCourses"
                    :key="course.id"
                    class="bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group cursor-pointer border border-gray-100"
                    @click="viewGradeSheet(course)"
                >
                    <!-- Course Header with Gradient -->
                    <div class="bg-gradient-to-br from-red-900 via-red-800 to-red-700 p-6 text-white relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="white" stroke-width="0.5"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#grid)" />
                            </svg>
                        </div>
                        
                        <div class="relative flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold mb-2 line-clamp-2 group-hover:underline">{{ course.title }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                                        {{ course.section }}
                                    </span>
                                    <span 
                                        class="px-3 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-500/90 text-white': course.status === 'Active',
                                            'bg-yellow-500/90 text-white': course.status === 'Pending',
                                            'bg-gray-500/90 text-white': course.status === 'Archived'
                                        }"
                                    >
                                        {{ course.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Course Info -->
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <!-- Teacher -->
                            <div class="flex items-start gap-3">
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Teacher</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ course.teacher.first_name }} {{ course.teacher.last_name }}</p>
                                </div>
                            </div>
                            
                            <!-- Students Count -->
                            <div class="flex items-start gap-3">
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Students</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ course.students_count }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <!-- Program -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="font-medium text-gray-700">{{ course.program }}</span>
                            </div>

                            <!-- Academic Year -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="font-medium text-gray-700">{{ course.academic_year }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button
                            @click.stop="viewGradeSheet(course)"
                            class="w-full bg-gradient-to-r from-red-900 to-red-800 hover:from-red-800 hover:to-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group-hover:shadow-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>View Grade Sheet</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- PDF Modal -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showPdfModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                    @click="closePdfModal"
                >
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="showPdfModal"
                            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl h-[90vh] flex flex-col"
                            @click.stop
                        >
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-red-900 to-red-700 rounded-t-2xl">
                                <div class="flex items-center gap-3 text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <h3 class="text-xl font-bold">Grade Sheet</h3>
                                        <p class="text-sm text-red-100" v-if="selectedCourse">{{ selectedCourse.title }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="downloadPdf"
                                        class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all"
                                        title="Download PDF"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span class="hidden sm:inline">Download</span>
                                    </button>
                                    <button
                                        @click="closePdfModal"
                                        class="p-2 hover:bg-white/20 backdrop-blur-sm text-white rounded-lg transition-all"
                                        title="Close"
                                    >
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- PDF Viewer -->
                            <div class="flex-1 overflow-hidden bg-gray-100 rounded-b-2xl">
                                <iframe
                                    v-if="pdfUrl"
                                    :src="pdfUrl"
                                    class="w-full h-full border-0"
                                    title="Grade Sheet PDF"
                                />
                                <div v-else class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <p class="text-gray-600">Loading PDF...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </div>
    </AdminLayout>
</template>
