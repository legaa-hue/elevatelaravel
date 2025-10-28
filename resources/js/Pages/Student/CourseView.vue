<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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
const showClassworkModal = ref(false);
const selectedClasswork = ref(null);
const submissionFiles = ref([]);
const linkSubmission = ref('');
const quizAnswers = ref({});

// File preview modal
const showFilePreviewModal = ref(false);
const previewFileUrl = ref('');
const previewFileName = ref('');

// Form for submission
const submissionForm = useForm({
    content: '',
    attachments: [],
    link: '',
    quiz_answers: {},
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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

// Check if classwork can have file attachments
const canHaveFiles = (type) => {
    return ['assignment', 'activity', 'lesson'].includes(type);
};

// Check if submission can be unsubmitted
const canUnsubmit = computed(() => {
    if (!selectedClasswork.value?.submission) return false;
    // Can't unsubmit if already graded
    return selectedClasswork.value.submission.status !== 'graded' && 
           !selectedClasswork.value.submission.grade;
});

const openClassworkModal = (classwork) => {
    selectedClasswork.value = classwork;
    showClassworkModal.value = true;
    
    // Reset form first
    submissionForm.reset();
    submissionFiles.value = [];
    linkSubmission.value = '';
    quizAnswers.value = {};
    
    // Initialize quiz answers if it's a quiz
    if (classwork.type === 'quiz' && classwork.quiz_questions) {
        classwork.quiz_questions.forEach((question, index) => {
            quizAnswers.value[index] = '';
        });
    }
    
    // If there's an existing submission, populate the form with existing data
    if (classwork.submission) {
        submissionForm.content = classwork.submission.submission_content || classwork.submission.content || '';
        linkSubmission.value = classwork.submission.link || '';
        submissionForm.link = classwork.submission.link || '';
        
        // Populate quiz answers if they exist
        if (classwork.submission.quiz_answers) {
            quizAnswers.value = { ...classwork.submission.quiz_answers };
        }
    }
};

const closeClassworkModal = () => {
    showClassworkModal.value = false;
    selectedClasswork.value = null;
    submissionForm.reset();
    submissionFiles.value = [];
    linkSubmission.value = '';
    quizAnswers.value = {};
};

const openFilePreview = (fileUrl, fileName) => {
    previewFileUrl.value = fileUrl;
    previewFileName.value = fileName;
    showFilePreviewModal.value = true;
};

const closeFilePreview = () => {
    showFilePreviewModal.value = false;
    previewFileUrl.value = '';
    previewFileName.value = '';
};

const handleFileUpload = (event) => {
    submissionFiles.value = Array.from(event.target.files);
};

const removeFile = (index) => {
    submissionFiles.value.splice(index, 1);
};

const submitWork = () => {
    if (!selectedClasswork.value) return;
    
    // Assign files and link to form
    submissionForm.attachments = submissionFiles.value;
    submissionForm.link = linkSubmission.value;
    
    // For quizzes, assign quiz answers
    if (selectedClasswork.value.type === 'quiz') {
        submissionForm.quiz_answers = quizAnswers.value;
    }
    
    submissionForm.post(route('student.classwork.submit', selectedClasswork.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            closeClassworkModal();
            // Show success message from backend
            const successMessage = page.props.flash?.success || 'Work submitted successfully!';
            alert(successMessage);
        },
        onError: (errors) => {
            console.error('Submission error:', errors);
            let errorMsg = 'Failed to submit:\n';
            Object.keys(errors).forEach(key => {
                errorMsg += `${key}: ${errors[key]}\n`;
            });
            alert(errorMsg);
        }
    });
};

const unsubmitWork = () => {
    if (!selectedClasswork.value?.submission) return;
    
    if (confirm('Are you sure you want to unsubmit this work? You will be able to edit and resubmit.')) {
        submissionForm.delete(route('student.classwork.unsubmit', selectedClasswork.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                alert('Submission removed. You can now edit and resubmit.');
            },
            onError: (errors) => {
                console.error('Unsubmit error:', errors);
                alert('Failed to unsubmit. ' + (errors.message || 'Please try again.'));
            }
        });
    }
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
                                        @click="openClassworkModal(classwork)"
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
                                        @click="openClassworkModal(classwork)"
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
                                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition cursor-pointer"
                                @click="openClassworkModal(classwork)"
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
                                        <div class="flex items-center gap-3" @click.stop>
                                            <button
                                                v-if="classwork.type === 'lesson'"
                                                @click="openClassworkModal(classwork)"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View Details
                                            </button>
                                            <button
                                                v-else-if="classwork.has_submission"
                                                @click="openClassworkModal(classwork)"
                                                class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium transition flex items-center gap-2"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                {{ classwork.submission ? 'View Submission' : 'Submit Work' }}
                                            </button>
                                            <button
                                                v-else
                                                @click="openClassworkModal(classwork)"
                                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View Details
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

        <!-- Classwork Detail & Submission Modal -->
        <div v-if="showClassworkModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="closeClassworkModal">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b bg-gradient-to-r from-red-900 to-red-700 text-white">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-white/20 text-white text-xs font-medium rounded-full">
                                {{ selectedClasswork?.type.charAt(0).toUpperCase() + selectedClasswork?.type.slice(1) }}
                            </span>
                            <span v-if="selectedClasswork?.points" class="text-sm text-white/90">
                                {{ selectedClasswork?.points }} points
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold">{{ selectedClasswork?.title }}</h2>
                    </div>
                    <button @click="closeClassworkModal" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-240px)] space-y-6">
                    <!-- Classwork Information Section -->
                    <div class="bg-white rounded-lg border-2 border-gray-200 p-5">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Activity Details
                        </h3>

                        <!-- Description -->
                        <div v-if="selectedClasswork?.description" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Description</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ selectedClasswork.description }}</p>
                        </div>

                        <!-- Due Date -->
                        <div v-if="selectedClasswork?.due_date" class="mb-4">
                            <div class="flex items-center gap-2 text-sm bg-orange-50 p-3 rounded-lg border border-orange-200">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="font-medium text-orange-900">Due: {{ formatDate(selectedClasswork.due_date) }}</span>
                            </div>
                        </div>

                        <!-- Points -->
                        <div v-if="selectedClasswork?.points" class="mb-4">
                            <div class="flex items-center gap-2 text-sm bg-blue-50 p-3 rounded-lg border border-blue-200">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                                <span class="font-medium text-blue-900">Total Points: {{ selectedClasswork.points }}</span>
                            </div>
                        </div>

                        <!-- Rubric Criteria (for Activities/Assignments) -->
                        <div v-if="selectedClasswork?.rubric_criteria && selectedClasswork.rubric_criteria.length > 0">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Grading Criteria</h4>
                            <div class="space-y-2">
                                <div v-for="(criteria, index) in selectedClasswork.rubric_criteria" :key="index"
                                     class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 text-red-900 rounded-full flex items-center justify-center text-sm font-bold">
                                        {{ criteria.points }}
                                    </div>
                                    <p class="text-sm text-gray-700 flex-1 pt-2">{{ criteria.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quiz Questions (for Quizzes) -->
                        <div v-if="selectedClasswork?.quiz_questions && selectedClasswork.quiz_questions.length > 0" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Quiz Questions ({{ selectedClasswork.quiz_questions.length }})</h4>
                            <div class="space-y-4">
                                <div v-for="(question, index) in selectedClasswork.quiz_questions" :key="index"
                                     class="p-4 bg-white rounded-lg border-2"
                                     :class="selectedClasswork.submission?.status === 'graded' && question.correct_answer ? 
                                             (selectedClasswork.submission.quiz_answers?.[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50') : 
                                             'border-gray-300'">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">Question {{ index + 1 }}</span>
                                            <!-- Correct/Incorrect indicator -->
                                            <span v-if="selectedClasswork.submission?.status === 'graded' && question.correct_answer" 
                                                  class="text-xs font-bold px-2 py-1 rounded flex items-center gap-1"
                                                  :class="selectedClasswork.submission.quiz_answers?.[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 
                                                          'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'">
                                                <svg v-if="selectedClasswork.submission.quiz_answers?.[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim()" 
                                                     class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ selectedClasswork.submission.quiz_answers?.[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 'Correct' : 'Incorrect' }}
                                            </span>
                                        </div>
                                        <span class="text-xs font-semibold text-red-900 bg-red-50 px-2 py-1 rounded">{{ question.points }} pts</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-3">{{ question.question }}</p>
                                    
                                    <!-- Show answer if already submitted and graded -->
                                    <div v-if="selectedClasswork.submission && selectedClasswork.submission.quiz_answers && selectedClasswork.submission.quiz_answers[index]">
                                        <div class="border rounded-lg p-3 mb-2"
                                             :class="selectedClasswork.submission.status === 'graded' && question.correct_answer ?
                                                     (selectedClasswork.submission.quiz_answers[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 
                                                      'bg-green-100 border-green-300' : 'bg-red-100 border-red-300') : 
                                                     'bg-blue-50 border-blue-200'">
                                            <p class="text-xs font-medium mb-1"
                                               :class="selectedClasswork.submission.status === 'graded' && question.correct_answer ?
                                                       (selectedClasswork.submission.quiz_answers[index]?.toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 
                                                        'text-green-900' : 'text-red-900') : 
                                                       'text-blue-900'">
                                                Your Answer:
                                            </p>
                                            <p class="text-sm text-gray-900">{{ selectedClasswork.submission.quiz_answers[index] }}</p>
                                        </div>
                                        <!-- Show correct answer if wrong -->
                                        <div v-if="selectedClasswork.submission.status === 'graded' && question.correct_answer && 
                                                   selectedClasswork.submission.quiz_answers[index]?.toLowerCase().trim() !== question.correct_answer.toLowerCase().trim()" 
                                             class="bg-green-100 border border-green-300 rounded-lg p-3">
                                            <p class="text-xs font-medium text-green-900 mb-1">Correct Answer:</p>
                                            <p class="text-sm text-gray-900 font-medium">{{ question.correct_answer }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Answer input if not submitted or can edit -->
                                    <div v-if="!selectedClasswork.submission || (selectedClasswork.submission.status !== 'graded' && !selectedClasswork.submission.grade)">
                                        <label :for="`quiz-answer-${index}`" class="block text-xs font-medium text-gray-700 mb-2">Your Answer:</label>
                                        <textarea
                                            :id="`quiz-answer-${index}`"
                                            v-model="quizAnswers[index]"
                                            rows="3"
                                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-red-900 transition text-sm"
                                            :placeholder="`Type your answer for question ${index + 1}...`"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher's Attachments -->
                        <div v-if="selectedClasswork?.attachments && selectedClasswork.attachments.length > 0" class="mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">File Attachments</h4>
                            <div class="space-y-2">
                                <button v-for="(attachment, index) in selectedClasswork.attachments" :key="index"
                                   @click="openFilePreview(`/storage/${attachment.path}`, attachment.name)"
                                   class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition group w-full text-left">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-700">{{ attachment.name }}</p>
                                        <p class="text-xs text-gray-500">{{ (attachment.size / 1024).toFixed(2) }} KB</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Submission Status -->
                    <div v-if="selectedClasswork?.submission" class="bg-white rounded-lg border-2 p-5"
                         :class="selectedClasswork.submission.status === 'graded' ? 'border-green-300' : 'border-blue-300'">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold flex items-center gap-2" 
                                :class="selectedClasswork.submission.status === 'graded' ? 'text-green-900' : 'text-blue-900'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Your Submission
                            </h3>
                            <span class="px-3 py-1 text-xs font-bold rounded-full"
                                  :class="selectedClasswork.submission.status === 'graded' ? 'bg-green-200 text-green-800' : 'bg-blue-200 text-blue-800'">
                                {{ selectedClasswork.submission.status === 'graded' ? '✓ GRADED' : '✓ SUBMITTED' }}
                            </span>
                        </div>
                        
                        <!-- Submission Content -->
                        <div v-if="selectedClasswork.submission.content || selectedClasswork.submission.submission_content" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Your Response:</h4>
                            <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200">{{ selectedClasswork.submission.content || selectedClasswork.submission.submission_content }}</p>
                        </div>

                        <!-- Submitted Link -->
                        <div v-if="selectedClasswork.submission.link" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Link Submission:</h4>
                            <a :href="selectedClasswork.submission.link" target="_blank" 
                               class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-2 bg-blue-50 p-3 rounded-lg border border-blue-200 group">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span class="font-medium">{{ selectedClasswork.submission.link }}</span>
                            </a>
                        </div>

                        <!-- Submitted Files -->
                        <div v-if="selectedClasswork.submission.attachments && selectedClasswork.submission.attachments.length > 0" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Your Files:</h4>
                            <div class="space-y-2">
                                <button v-for="(file, idx) in selectedClasswork.submission.attachments" :key="idx"
                                   @click="openFilePreview(`/storage/${file.path}`, file.name)"
                                   class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition group w-full text-left">
                                    <svg class="w-5 h-5 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-gray-700 font-medium group-hover:text-blue-700">{{ file.name }}</span>
                                    <svg class="w-4 h-4 text-gray-400 ml-auto group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Quiz Answers (for Quiz submissions) -->
                        <div v-if="selectedClasswork.type === 'quiz' && selectedClasswork.submission.quiz_answers && Object.keys(selectedClasswork.submission.quiz_answers).length > 0" class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Your Quiz Answers:</h4>
                            <div class="space-y-3">
                                <div v-for="(question, index) in selectedClasswork.quiz_questions" :key="index"
                                     class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-xs font-medium text-gray-500">Question {{ index + 1 }}</span>
                                        <span class="text-xs font-semibold text-red-900 bg-red-50 px-2 py-1 rounded">{{ question.points }} pts</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-2">{{ question.question }}</p>
                                    <div class="bg-blue-50 border border-blue-200 rounded p-2 mt-2">
                                        <p class="text-xs font-medium text-blue-900 mb-1">Your Answer:</p>
                                        <p class="text-sm text-gray-900">{{ selectedClasswork.submission.quiz_answers[index] || 'No answer provided' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t" 
                             :class="selectedClasswork.submission.status === 'graded' ? 'border-green-200' : 'border-blue-200'">
                            <p class="text-xs text-gray-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Submitted: {{ formatDate(selectedClasswork.submission.submitted_at) }}
                            </p>
                            
                            <!-- Grade -->
                            <div v-if="selectedClasswork.submission.grade !== null">
                                <span class="text-lg font-bold text-green-700 bg-green-50 px-4 py-2 rounded-lg border-2 border-green-200">
                                    {{ selectedClasswork.submission.grade }}/{{ selectedClasswork.points }}
                                </span>
                            </div>
                        </div>

                        <!-- Feedback -->
                        <div v-if="selectedClasswork.submission.feedback" class="mt-4 pt-4 border-t border-green-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                Teacher's Feedback:
                            </h4>
                            <p class="text-sm text-gray-700 bg-green-50 p-3 rounded-lg border border-green-200">{{ selectedClasswork.submission.feedback }}</p>
                        </div>
                    </div>

                    <!-- Submission Form (Only for non-lessons and if not graded) -->
                    <div v-if="selectedClasswork?.has_submission && 
                              (!selectedClasswork.submission || (selectedClasswork.submission.status !== 'graded' && !selectedClasswork.submission.grade))"
                         class="bg-white rounded-lg border-2 border-red-200 p-5">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ selectedClasswork.submission ? 'Edit Your Submission' : 'Submit Your Work' }}
                        </h3>
                        
                        <!-- Text Response -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Your Response (Optional)</label>
                            <textarea
                                v-model="submissionForm.content"
                                rows="6"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-red-900 transition"
                                placeholder="Type your answer or response here..."
                            ></textarea>
                        </div>

                        <!-- File Upload (only for assignments, activities, lessons) -->
                        <div v-if="canHaveFiles(selectedClasswork.type)" class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Attach Files (Optional)</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 hover:bg-gray-100 transition text-center">
                                <input
                                    type="file"
                                    @change="handleFileUpload"
                                    multiple
                                    id="file-upload"
                                    class="hidden"
                                />
                                <label for="file-upload" class="cursor-pointer">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700">Click to upload files</p>
                                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                    <p class="text-xs text-gray-500 mt-1">Max 10MB per file</p>
                                </label>
                            </div>
                            
                            <!-- Selected Files List -->
                            <div v-if="submissionFiles.length > 0" class="mt-3 space-y-2">
                                <div v-for="(file, index) in submissionFiles" :key="index"
                                     class="flex items-center justify-between bg-white px-4 py-3 rounded-lg border-2 border-gray-200 hover:border-red-300 transition">
                                    <span class="text-sm text-gray-700 flex items-center gap-2 font-medium">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        {{ file.name }}
                                        <span class="text-xs text-gray-500">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                                    </span>
                                    <button type="button" @click="removeFile(index)" 
                                            class="text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Link Submission -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Or Submit a Link (Optional)</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <input
                                    v-model="linkSubmission"
                                    type="url"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-red-900 transition"
                                    placeholder="https://example.com/your-work"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-start gap-1">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Submit a link to your work (Google Docs, GitHub, OneDrive, etc.)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between p-6 border-t bg-gray-50 gap-3">
                    <!-- Unsubmit Button (Only if can unsubmit) -->
                    <button
                        v-if="canUnsubmit"
                        @click="unsubmitWork"
                        class="px-5 py-2.5 text-red-700 bg-red-50 hover:bg-red-100 border-2 border-red-200 hover:border-red-300 rounded-lg text-sm font-semibold transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Unsubmit
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-3">
                        <!-- Close Button -->
                        <button
                            @click="closeClassworkModal"
                            class="px-5 py-2.5 text-gray-700 bg-white hover:bg-gray-100 border-2 border-gray-300 rounded-lg text-sm font-semibold transition"
                        >
                            Close
                        </button>

                        <!-- Submit Button (Only if has submission and not graded) -->
                        <button
                            v-if="selectedClasswork?.has_submission && 
                                  (!selectedClasswork.submission || (selectedClasswork.submission.status !== 'graded' && !selectedClasswork.submission.grade))"
                            @click="submitWork"
                            :disabled="submissionForm.processing"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-900 to-red-700 hover:from-red-800 hover:to-red-600 text-white rounded-lg text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="!submissionForm.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ submissionForm.processing ? 'Submitting...' : (selectedClasswork.submission ? 'Update Submission' : 'Submit Work') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Preview Modal -->
        <div v-if="showFilePreviewModal" 
             class="fixed inset-0 z-[60] overflow-y-auto"
             @click.self="closeFilePreview">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 backdrop-blur-sm" 
                     @click="closeFilePreview"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-6xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-red-900 to-red-700 text-white">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <h2 class="text-xl font-bold truncate">{{ previewFileName }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="previewFileUrl"
                               download
                               class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download
                            </a>
                            <button @click="closeFilePreview"
                                    class="p-2 hover:bg-white/10 rounded-lg transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- File Preview Content -->
                    <div class="bg-gray-100" style="height: 80vh;">
                        <iframe :src="previewFileUrl"
                                class="w-full h-full border-0"
                                frameborder="0">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
