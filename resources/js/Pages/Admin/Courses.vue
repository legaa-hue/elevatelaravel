<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    courses: Object,
    summary: Object,
    academicYears: Array,
    instructors: Array,
    activeAcademicYear: Object,
    filters: Object,
});

// Filters
const filters = ref({
    academic_year: props.filters.academic_year || '',
    instructor: props.filters.instructor || '',
    status: props.filters.status || '',
    program: props.filters.program || '',
    search: props.filters.search || '',
});

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const selectedCourse = ref(null);

// Forms
const createForm = ref({
    title: '',
    section: '',
    description: '',
    teacher_id: '',
    academic_year_id: props.activeAcademicYear?.id || '',
    status: 'Active',
});

const editForm = ref({
    id: null,
    title: '',
    section: '',
    description: '',
    teacher_id: '',
    academic_year_id: '',
    status: '',
});

// Watch filters and apply
watch(filters, (newFilters) => {
    router.get(route('admin.courses.index'), newFilters, {
        preserveState: true,
        preserveScroll: true,
    });
}, { deep: true });

// Reset filters
const resetFilters = () => {
    filters.value = {
        academic_year: '',
        instructor: '',
        status: '',
        program: '',
        search: '',
    };
};

// Open modals
const openCreateModal = () => {
    createForm.value = {
        title: '',
        section: '',
        description: '',
        teacher_id: '',
        academic_year_id: props.activeAcademicYear?.id || '',
        status: 'Active',
    };
    showCreateModal.value = true;
};

const openEditModal = (course) => {
    editForm.value = {
        id: course.id,
        title: course.title,
        section: course.section,
        description: course.description,
        teacher_id: course.instructorId,
        academic_year_id: course.academicYearId,
        status: course.status,
    };
    showEditModal.value = true;
};

const openViewModal = (course) => {
    selectedCourse.value = course;
    showViewModal.value = true;
};

// Submit actions
const submitCreate = () => {
    router.post(route('admin.courses.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            // Reload the page data to show the new course
            router.reload({ only: ['courses', 'summary'] });
        },
    });
};

const submitEdit = () => {
    router.put(route('admin.courses.update', editForm.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            // Reload the page data to show updated course
            router.reload({ only: ['courses', 'summary'] });
        },
    });
};

const archiveCourse = (courseId) => {
    if (confirm('Are you sure you want to archive this course?')) {
        router.post(route('admin.courses.archive', courseId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page data to update course status
                router.reload({ only: ['courses', 'summary'] });
            }
        });
    }
};

const restoreCourse = (courseId) => {
    if (confirm('Are you sure you want to restore this course?')) {
        router.post(route('admin.courses.restore', courseId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page data to update course status
                router.reload({ only: ['courses', 'summary'] });
            }
        });
    }
};

const deleteCourse = (courseId) => {
    if (confirm('Are you sure you want to permanently delete this course? This action cannot be undone.')) {
        router.delete(route('admin.courses.destroy', courseId), {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page data to remove deleted course
                router.reload({ only: ['courses', 'summary'] });
            }
        });
    }
};

// Status badge color
const getStatusColor = (status) => {
    const colors = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Pending': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'Inactive': 'bg-red-100 text-red-800 border-red-200',
        'Archived': 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};
</script>

<template>
    <Head title="Admin - Courses Management" />

    <AdminLayout>
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Courses Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage all courses across the system</p>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="openCreateModal"
                        class="flex items-center justify-center px-4 py-2 bg-red-900 text-white rounded-md hover:bg-red-800 transition-colors font-medium text-sm sm:text-base w-full sm:w-auto"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Course
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600">Total Courses</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ summary.totalCourses }}</p>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-50 rounded-full flex items-center justify-center">
                        <span class="text-lg sm:text-xl">📚</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600">Active Courses</p>
                        <p class="text-xl sm:text-2xl font-bold text-green-600 mt-1">{{ summary.activeCourses }}</p>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-green-50 rounded-full flex items-center justify-center">
                        <span class="text-lg sm:text-xl">🟢</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600">Ongoing Courses</p>
                        <p class="text-xl sm:text-2xl font-bold text-blue-600 mt-1">{{ summary.ongoingCourses }}</p>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-50 rounded-full flex items-center justify-center">
                        <span class="text-lg sm:text-xl">🔵</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-600 mt-1">{{ summary.completedCourses }}</p>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gray-50 rounded-full flex items-center justify-center">
                        <span class="text-lg sm:text-xl">⚪</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5 col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600">Total Instructors</p>
                        <p class="text-xl sm:text-2xl font-bold text-red-900 mt-1">{{ summary.totalInstructors }}</p>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-red-50 rounded-full flex items-center justify-center">
                        <span class="text-lg sm:text-xl">👨‍🏫</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Panel -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 sm:p-5 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
                <!-- Academic Year Filter -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Academic Year</label>
                    <select
                        v-model="filters.academic_year"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm"
                    >
                        <option value="">All Years</option>
                        <option
                            v-for="year in academicYears"
                            :key="year.id"
                            :value="year.id"
                        >
                            {{ year.year_name }} {{ year.status === 'Active' ? '🟢' : '' }}
                        </option>
                    </select>
                </div>

                <!-- Instructor Filter -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Instructor</label>
                    <select
                        v-model="filters.instructor"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm"
                    >
                        <option value="">All Instructors</option>
                        <option
                            v-for="instructor in instructors"
                            :key="instructor.id"
                            :value="instructor.id"
                        >
                            {{ instructor.name }}
                        </option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Status</label>
                    <select
                        v-model="filters.status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>

                <!-- Program Filter -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Program</label>
                    <select
                        v-model="filters.program"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm"
                    >
                        <option value="">All Programs</option>
                        <option value="ADMINISTRATION">MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION & SUPERVISION</option>
                        <option value="MATHEMATICS">MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS</option>
                        <option value="SCIENCE">MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE</option>
                        <option value="FILIPINO">MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO</option>
                        <option value="MAPEH">MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH</option>
                        <option value="TLE">MASTER OF ARTS IN EDUCATION MAJOR IN TLE</option>
                        <option value="HISTORY">MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY</option>
                        <option value="ENGLISH">MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH</option>
                        <option value="PRESCHOOL">MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION</option>
                        <option value="GUIDANCE">MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE & COUNSELING</option>
                        <option value="ALTERNATIVE">MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM</option>
                        <option value="SPECIAL">MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="sm:col-span-2">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Search</label>
                    <div class="relative">
                        <input
                            type="text"
                            v-model="filters.search"
                            placeholder="Search by course code, title, or section..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900 pl-10 text-sm"
                        />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="mt-3 sm:mt-4 flex justify-end">
                <button
                    @click="resetFilters"
                    class="w-full sm:w-auto text-sm text-gray-600 hover:text-red-900 font-medium py-2 px-4 border border-gray-300 rounded-md sm:border-0"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Courses Table -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code / Title</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Instructor</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Section</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Academic Year</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Enrolled Students</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="courses.data.length === 0">
                            <td colspan="7" class="px-3 sm:px-6 py-8 text-center text-gray-500 text-sm">
                                No courses found
                            </td>
                        </tr>
                        <tr v-for="course in courses.data" :key="course.id" class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-4">
                                <div class="text-xs sm:text-sm font-medium text-gray-900">{{ course.courseCode }}</div>
                                <div class="text-xs sm:text-sm text-gray-500">{{ course.title }}</div>
                                <!-- Show instructor on mobile -->
                                <div class="text-xs text-gray-500 mt-1 md:hidden">
                                    👨‍🏫 {{ course.instructor }}
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900 hidden md:table-cell">{{ course.instructor }}</td>
                            <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900 hidden lg:table-cell">{{ course.section }}</td>
                            <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900 hidden xl:table-cell">{{ course.academicYear }}</td>
                            <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900 hidden lg:table-cell">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ course.enrolledStudents }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-6 py-4">
                                <span :class="getStatusColor(course.status)" class="px-2 py-1 text-xs font-semibold rounded-full border">
                                    {{ course.status }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-sm">
                                <div class="flex space-x-1 sm:space-x-2">
                                    <button
                                        @click="openViewModal(course)"
                                        class="text-blue-600 hover:text-blue-800 font-medium"
                                        title="View Details"
                                    >
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openEditModal(course)"
                                        class="text-yellow-600 hover:text-yellow-800 font-medium"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="course.status !== 'Archived'"
                                        @click="archiveCourse(course.id)"
                                        class="text-gray-600 hover:text-gray-800 font-medium"
                                        title="Archive"
                                    >
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="course.status === 'Archived'"
                                        @click="restoreCourse(course.id)"
                                        class="text-green-600 hover:text-green-800 font-medium"
                                        title="Restore"
                                    >
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteCourse(course.id)"
                                        class="text-red-600 hover:text-red-800 font-medium"
                                        title="Delete"
                                    >
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="courses.links && courses.links.length > 3" class="bg-gray-50 px-3 sm:px-6 py-3 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                        Showing <span class="font-medium">{{ courses.from }}</span> to <span class="font-medium">{{ courses.to }}</span> of <span class="font-medium">{{ courses.total }}</span> results
                    </div>
                    <div class="flex flex-wrap justify-center gap-1 sm:gap-2">
                        <Link
                            v-for="(link, index) in courses.links"
                            :key="index"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-2 sm:px-3 py-1 rounded-md text-xs sm:text-sm',
                                link.active ? 'bg-red-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            :disabled="!link.url"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Course Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Add New Course</h2>
                        <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Title *</label>
                            <input
                                type="text"
                                v-model="createForm.title"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                                placeholder="e.g., EDUCN 204 – Statistics in Education"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                            <input
                                type="text"
                                v-model="createForm.section"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                                placeholder="e.g., A, B, 101"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instructor *</label>
                            <select
                                v-model="createForm.teacher_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="">Select Instructor</option>
                                <option v-for="instructor in instructors" :key="instructor.id" :value="instructor.id">
                                    {{ instructor.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year *</label>
                            <select
                                v-model="createForm.academic_year_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="">Select Academic Year</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.year_name }} {{ year.status === 'Active' ? '(Active)' : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select
                                v-model="createForm.status"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                v-model="createForm.description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                                placeholder="Course description..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-red-900 text-white rounded-md hover:bg-red-800"
                            >
                                Create Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Edit Course</h2>
                        <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Title *</label>
                            <input
                                type="text"
                                v-model="editForm.title"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                            <input
                                type="text"
                                v-model="editForm.section"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instructor *</label>
                            <select
                                v-model="editForm.teacher_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="">Select Instructor</option>
                                <option v-for="instructor in instructors" :key="instructor.id" :value="instructor.id">
                                    {{ instructor.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year *</label>
                            <select
                                v-model="editForm.academic_year_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="">Select Academic Year</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.year_name }} {{ year.status === 'Active' ? '(Active)' : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select
                                v-model="editForm.status"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-900 focus:ring-red-900"
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-red-900 text-white rounded-md hover:bg-red-800"
                            >
                                Update Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Course Details Modal -->
        <div v-if="showViewModal && selectedCourse" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Course Details</h2>
                        <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Course Code</label>
                                <p class="text-base font-semibold text-gray-900">{{ selectedCourse.courseCode }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <p>
                                    <span :class="getStatusColor(selectedCourse.status)" class="px-2 py-1 text-xs font-semibold rounded-full border">
                                        {{ selectedCourse.status }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Course Title</label>
                            <p class="text-base font-semibold text-gray-900">{{ selectedCourse.title }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Section</label>
                                <p class="text-base text-gray-900">{{ selectedCourse.section }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Join Code</label>
                                <p class="text-base font-mono font-bold text-red-900">{{ selectedCourse.joinCode }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Instructor</label>
                                <p class="text-base text-gray-900">{{ selectedCourse.instructor }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Academic Year</label>
                                <p class="text-base text-gray-900">{{ selectedCourse.academicYear }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Enrolled Students</label>
                            <p class="text-base text-gray-900">{{ selectedCourse.enrolledStudents }} students</p>
                        </div>

                        <div v-if="selectedCourse.description">
                            <label class="text-sm font-medium text-gray-600">Description</label>
                            <p class="text-base text-gray-900">{{ selectedCourse.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Date Created</label>
                                <p class="text-base text-gray-900">{{ selectedCourse.dateCreated }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Last Updated</label>
                                <p class="text-base text-gray-900">{{ selectedCourse.lastUpdated }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                        <button
                            @click="showViewModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
