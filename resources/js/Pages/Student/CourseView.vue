<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    course: Object,
    courseGrades: Object,
    classworks: Array,
    pendingClassworks: Array,
    completedClassworks: Array,
});

const activeTab = ref('stream');
const showAttachmentModal = ref(false);
const selectedAttachment = ref(null);
const showSubmissionModal = ref(false);
const selectedClasswork = ref(null);
const submissionForm = ref({
    content: '',
    attachments: [],
});

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Get icon for classwork type
const getTypeIcon = (type) => {
    const icons = {
        lesson: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
        assignment: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
        quiz: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
        activity: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />',
    };
    return icons[type] || icons.assignment;
};

const openAttachment = (attachment) => {
    selectedAttachment.value = attachment;
    showAttachmentModal.value = true;
};

const openSubmissionModal = (classwork) => {
    selectedClasswork.value = classwork;
    submissionForm.value = {
        content: classwork.submission?.content || '',
        attachments: [],
    };
    showSubmissionModal.value = true;
};

const handleFileSelect = (event) => {
    submissionForm.value.attachments = Array.from(event.target.files);
};

const submitWork = () => {
    const formData = new FormData();
    formData.append('content', submissionForm.value.content);
    
    submissionForm.value.attachments.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
    });

    // Submit using Inertia
    window.axios.post(`/student/classworks/${selectedClasswork.value.id}/submit`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(() => {
        showSubmissionModal.value = false;
        location.reload();
    }).catch(error => {
        console.error('Submission error:', error);
    });
};
</script>

<template>
    <Head :title="course.title" />

    <StudentLayout>
        <div class="space-y-6">
            <!-- Course Header -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 text-white">
                <h1 class="text-3xl font-bold">{{ course.title }}</h1>
                <p class="mt-2 text-red-100">{{ course.section }}</p>
                <div class="mt-4 flex items-center gap-6 text-sm">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ course.teacher_name }}
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        {{ course.units }} Units
                    </span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button
                            @click="activeTab = 'stream'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'stream'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Stream
                        </button>
                        <button
                            @click="activeTab = 'classwork'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'classwork'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Classwork
                        </button>
                        <button
                            @click="activeTab = 'people'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'people'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            People
                        </button>
                        <button
                            @click="activeTab = 'grades'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'grades'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Grades
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Stream Tab -->
                    <div v-if="activeTab === 'stream'" class="space-y-6">
                        <!-- Two Column Layout: To-Do (Left) and Completed (Right) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- To-Do Section (Left) -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    To-Do ({{ pendingClassworks.length }})
                                </h3>
                                <div v-if="pendingClassworks.length > 0" class="space-y-3">
                                    <div
                                        v-for="classwork in pendingClassworks"
                                        :key="classwork.id"
                                        class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition cursor-pointer"
                                        @click="classwork.type === 'lesson' ? openAttachment(classwork) : openSubmissionModal(classwork)"
                                    >
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="{ backgroundColor: classwork.color_code + '20', color: classwork.color_code }">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getTypeIcon(classwork.type)"></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 truncate">{{ classwork.title }}</h4>
                                            <p class="text-sm text-gray-500">{{ classwork.type.charAt(0).toUpperCase() + classwork.type.slice(1) }}</p>
                                            <div v-if="classwork.due_date" class="text-xs text-red-600 mt-1">
                                                Due: {{ classwork.due_date }}
                                            </div>
                                        </div>
                                        <div v-if="classwork.points" class="text-sm font-medium text-gray-900 flex-shrink-0">
                                            {{ classwork.points }} pts
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm">All caught up!</p>
                                </div>
                            </div>

                            <!-- Completed Section (Right) -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Completed ({{ completedClassworks.length }})
                                </h3>
                                <div v-if="completedClassworks.length > 0" class="space-y-3">
                                    <div
                                        v-for="classwork in completedClassworks"
                                        :key="classwork.id"
                                        class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition cursor-pointer bg-gray-50"
                                        @click="openSubmissionModal(classwork)"
                                    >
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="{ backgroundColor: classwork.color_code + '20', color: classwork.color_code }">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getTypeIcon(classwork.type)"></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 truncate">{{ classwork.title }}</h4>
                                            <p class="text-xs text-gray-500">{{ classwork.submission?.submitted_at }}</p>
                                        </div>
                                        <div v-if="classwork.submission?.grade" class="text-sm font-semibold text-green-600 flex-shrink-0">
                                            {{ classwork.submission.grade }}/{{ classwork.points }}
                                        </div>
                                        <div v-else class="text-xs text-gray-500 flex-shrink-0">
                                            Pending
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="mt-2 text-sm">No completed work yet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Classwork Tab -->
                    <div v-if="activeTab === 'classwork'" class="space-y-6">
                        <!-- Filter by Type (Optional) -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <button class="px-4 py-2 text-sm font-medium bg-red-900 text-white rounded-lg">
                                All
                            </button>
                            <button class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                Lessons
                            </button>
                            <button class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                Assignments
                            </button>
                            <button class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                Quizzes
                            </button>
                            <button class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                Activities
                            </button>
                        </div>

                        <!-- Classwork List -->
                        <div v-if="classworks.length > 0" class="space-y-4">
                            <div
                                v-for="classwork in classworks"
                                :key="classwork.id"
                                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :style="{ backgroundColor: classwork.color_code + '20', color: classwork.color_code }">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getTypeIcon(classwork.type)"></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">{{ classwork.title }}</h3>
                                                <p class="text-sm text-gray-500">{{ classwork.created_by_name }} • {{ classwork.created_at }}</p>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span v-if="classwork.points" class="text-sm font-medium text-gray-700">{{ classwork.points }} pts</span>
                                                <span v-if="classwork.submission?.status === 'graded'" class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                                                    Graded
                                                </span>
                                                <span v-else-if="classwork.submission?.status === 'submitted'" class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                                                    Submitted
                                                </span>
                                                <span v-else-if="classwork.has_submission" class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm font-medium rounded-full">
                                                    Not submitted
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <p v-if="classwork.description" class="text-gray-700 mb-3">{{ classwork.description }}</p>
                                        
                                        <div v-if="classwork.due_date" class="text-sm text-gray-600 mb-3">
                                            <strong>Due:</strong> {{ classwork.due_date }}
                                        </div>

                                        <!-- Attachments -->
                                        <div v-if="classwork.attachments && classwork.attachments.length > 0" class="mb-4">
                                            <p class="text-sm font-medium text-gray-700 mb-2">Attachments:</p>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="(attachment, index) in classwork.attachments"
                                                    :key="index"
                                                    @click="openAttachment(attachment)"
                                                    class="flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                    {{ attachment.name }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-3">
                                            <button
                                                v-if="classwork.type === 'lesson'"
                                                @click="openAttachment(classwork)"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition"
                                            >
                                                View Lesson
                                            </button>
                                            <button
                                                v-else-if="classwork.has_submission"
                                                @click="openSubmissionModal(classwork)"
                                                class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition"
                                            >
                                                {{ classwork.submission ? 'View Submission' : 'Submit Work' }}
                                            </button>
                                            <span v-if="classwork.submission?.grade" class="text-sm font-semibold text-gray-700">
                                                Grade: {{ classwork.submission.grade }}/{{ classwork.points }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">No classwork posted yet</p>
                        </div>
                    </div>

                    <!-- People Tab -->
                    <div v-if="activeTab === 'people'" class="space-y-6">
                        <!-- Teacher Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Teacher</h3>
                            <div class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
                                <div class="w-10 h-10 bg-red-900 rounded-full flex items-center justify-center text-white font-semibold">
                                    {{ course.teacher_name.split(' ')[0][0] }}{{ course.teacher_name.split(' ')[1]?.[0] || '' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ course.teacher_name }}</p>
                                    <p class="text-sm text-gray-500">Course Instructor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Students Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Classmates</h3>
                            <div class="text-center py-8 text-gray-500 border border-gray-200 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="mt-2">Loading classmates...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Grades Tab -->
                    <div v-if="activeTab === 'grades'" class="space-y-6">
                        <!-- Overall Grade -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Overall Grade</h3>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-bold text-gray-900">{{ courseGrades?.overall_percentage || '0.00' }}%</span>
                                <span class="text-sm text-gray-500">Earned {{ courseGrades?.total_earned || 0 }}/{{ courseGrades?.total_points || 0 }} pts</span>
                            </div>
                        </div>

                        <!-- Term Grades -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Term Grades</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-1">Midterm</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ courseGrades?.midterm || '—' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-1">Tentative Final</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ courseGrades?.tentative_final || '—' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-1">Final</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ courseGrades?.final || '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Category Breakdown -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Category Breakdown</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percent</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="category in courseGrades?.categories || []" :key="category.name">
                                            <td class="px-4 py-4 text-sm text-gray-900">{{ category.name }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ category.earned }}/{{ category.total }} pts</td>
                                            <td class="px-4 py-4 text-sm text-gray-900">{{ category.percentage }}</td>
                                        </tr>
                                        <tr v-if="!courseGrades?.categories || courseGrades.categories.length === 0">
                                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                                No grades available yet
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- My Gradebook -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    My Gradebook
                                </h3>
                                <div class="flex items-center gap-2">
                                    <button class="px-3 py-1.5 text-sm font-medium text-red-900 bg-red-50 rounded hover:bg-red-100 transition">
                                        All
                                    </button>
                                    <button class="px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded transition">
                                        Graded
                                    </button>
                                    <button class="px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded transition">
                                        Missing
                                    </button>
                                    <button class="px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded transition">
                                        Submitted
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">Organized by assignment type</p>
                            
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No assignments yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachment Modal -->
        <div v-if="showAttachmentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="showAttachmentModal = false">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden" @click.stop>
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">{{ selectedAttachment?.name || 'View Attachment' }}</h3>
                    <button @click="showAttachmentModal = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                    <div v-if="selectedAttachment?.path">
                        <iframe
                            :src="`/storage/${selectedAttachment.path}`"
                            class="w-full h-[600px] border border-gray-200 rounded-lg"
                            frameborder="0"
                        ></iframe>
                        <div class="mt-4 flex justify-center">
                            <a
                                :href="`/storage/${selectedAttachment.path}`"
                                target="_blank"
                                download
                                class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition"
                            >
                                Download File
                            </a>
                        </div>
                    </div>
                    <div v-else-if="selectedAttachment?.description" class="prose max-w-none">
                        <div v-html="selectedAttachment.description"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Modal -->
        <div v-if="showSubmissionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="showSubmissionModal = false">
            <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-hidden" @click.stop>
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">{{ selectedClasswork?.title }}</h3>
                    <button @click="showSubmissionModal = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-160px)]">
                    <!-- Classwork Details -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm font-medium rounded-full">
                                {{ selectedClasswork?.type.charAt(0).toUpperCase() + selectedClasswork?.type.slice(1) }}
                            </span>
                            <span v-if="selectedClasswork?.points" class="text-sm text-gray-600">
                                {{ selectedClasswork?.points }} points
                            </span>
                        </div>
                        <p v-if="selectedClasswork?.description" class="text-gray-700 mb-3">{{ selectedClasswork?.description }}</p>
                        <p v-if="selectedClasswork?.due_date" class="text-sm text-gray-600">
                            <strong>Due:</strong> {{ selectedClasswork?.due_date }}
                        </p>
                    </div>

                    <!-- Existing Submission -->
                    <div v-if="selectedClasswork?.submission" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="font-semibold text-blue-900 mb-2">Your Submission</h4>
                        <p class="text-sm text-gray-700 mb-2">{{ selectedClasswork.submission.content }}</p>
                        <p class="text-xs text-gray-600">Submitted: {{ selectedClasswork.submission.submitted_at }}</p>
                        <div v-if="selectedClasswork.submission.grade" class="mt-3 pt-3 border-t border-blue-200">
                            <p class="text-sm font-semibold text-green-700">Grade: {{ selectedClasswork.submission.grade }}/{{ selectedClasswork.points }}</p>
                            <p v-if="selectedClasswork.submission.feedback" class="text-sm text-gray-700 mt-1">
                                <strong>Feedback:</strong> {{ selectedClasswork.submission.feedback }}
                            </p>
                        </div>
                    </div>

                    <!-- Submission Form (if not submitted or resubmitting) -->
                    <div v-if="!selectedClasswork?.submission || selectedClasswork?.submission?.status === 'draft'">
                        <h4 class="font-semibold text-gray-900 mb-3">Submit Your Work</h4>
                        <textarea
                            v-model="submissionForm.content"
                            rows="6"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            placeholder="Type your answer or response here..."
                        ></textarea>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attach Files</label>
                            <input
                                type="file"
                                @change="handleFileSelect"
                                multiple
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-900 hover:file:bg-red-100"
                            />
                            <p class="text-xs text-gray-500 mt-1">You can attach multiple files (max 10MB each)</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-4 border-t bg-gray-50">
                    <button
                        @click="showSubmissionModal = false"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg text-sm font-medium transition"
                    >
                        Cancel
                    </button>
                    <button
                        v-if="!selectedClasswork?.submission || selectedClasswork?.submission?.status === 'draft'"
                        @click="submitWork"
                        class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition"
                    >
                        Submit Work
                    </button>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
