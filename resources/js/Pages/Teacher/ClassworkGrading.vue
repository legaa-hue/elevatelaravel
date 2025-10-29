<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
    course: Object,
    classwork: Object,
    submissions: Array,
    students: Array, // All enrolled students
});

const selectedSubmission = ref(null);
const showGradingModal = ref(false);
const showFilePreview = ref(false);
const previewFile = ref(null);
const fileBlob = ref(null);
const isLoadingFile = ref(false);
const fileLoadError = ref(null);

const gradingForm = useForm({
    grade: null,
    feedback: '',
    rubric_scores: {},
    status: 'graded',
});

const submittedCount = computed(() => {
    return props.submissions.filter(s => s.status === 'submitted' || s.status === 'graded').length;
});

const gradedCount = computed(() => {
    return props.submissions.filter(s => s.status === 'graded').length;
});

const openGradingModal = (submission) => {
    selectedSubmission.value = submission;
    gradingForm.grade = submission.grade || null;
    gradingForm.feedback = submission.feedback || '';
    gradingForm.rubric_scores = submission.rubric_scores || {};
    gradingForm.status = 'graded';
    showGradingModal.value = true;
};

const closeGradingModal = () => {
    showGradingModal.value = false;
    selectedSubmission.value = null;
    gradingForm.reset();
};

const calculateTotalFromRubric = () => {
    if (!props.classwork.rubric_criteria || props.classwork.rubric_criteria.length === 0) {
        return;
    }
    
    const total = props.classwork.rubric_criteria.reduce((sum, criteria) => {
        const score = parseInt(gradingForm.rubric_scores[criteria.id]) || 0;
        return sum + score;
    }, 0);
    
    gradingForm.grade = total;
    // Auto-correct if exceeds max
    if (gradingForm.grade > props.classwork.points) {
        gradingForm.grade = props.classwork.points;
    }
};

const validateRubricScore = (criteriaId, maxPoints) => {
    // Auto-correct rubric score if it exceeds the maximum points for that criteria
    const score = gradingForm.rubric_scores[criteriaId];
    if (score > maxPoints) {
        gradingForm.rubric_scores[criteriaId] = maxPoints;
    }
    if (score < 0) {
        gradingForm.rubric_scores[criteriaId] = 0;
    }
    // Recalculate total after validation
    calculateTotalFromRubric();
};

const validateGradeInput = () => {
    // Auto-correct grade if it exceeds the maximum points
    if (gradingForm.grade > props.classwork.points) {
        gradingForm.grade = props.classwork.points;
    }
    // Ensure grade is not negative
    if (gradingForm.grade < 0) {
        gradingForm.grade = 0;
    }
};

const submitGrade = () => {
    if (!selectedSubmission.value) return;
    
    gradingForm.post(route('teacher.courses.classwork.submissions.grade', {
        course: props.course.id,
        classwork: props.classwork.id,
        submission: selectedSubmission.value.id
    }), {
        preserveScroll: true,
        onSuccess: () => {
            closeGradingModal();
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        'draft': 'bg-gray-200 text-gray-700',
        'submitted': 'bg-blue-500 text-white',
        'graded': 'bg-green-500 text-white',
        'returned': 'bg-purple-500 text-white',
    };
    return colors[status] || 'bg-gray-200 text-gray-700';
};

const getTypeBorderClass = (type) => {
    const colors = {
        'lesson': 'border-t-4 border-blue-500',
        'assignment': 'border-t-4 border-yellow-500',
        'quiz': 'border-t-4 border-red-500',
        'activity': 'border-t-4 border-green-500',
    };
    return colors[type] || 'border-t-4 border-blue-500';
};

const openFilePreview = async (attachment) => {
    // Handle both string filenames and attachment objects
    const filename = typeof attachment === 'object' ? attachment.name : attachment;
    const filepath = typeof attachment === 'object' ? `/storage/${attachment.path}` : `/storage/submissions/${attachment}`;
    
    // Set file info
    previewFile.value = {
        name: filename,
        path: filepath
    };
    showFilePreview.value = true;
    isLoadingFile.value = true;
    fileBlob.value = null;
    fileLoadError.value = null;

    try {
        // Fetch the actual file
        const response = await fetch(previewFile.value.path);
        
        if (!response.ok) {
            throw new Error(`File not found (${response.status})`);
        }

        const blob = await response.blob();
        
        // Create object URL for the blob
        const objectUrl = URL.createObjectURL(blob);
        fileBlob.value = objectUrl;
        
        // Cache the file for offline use using Cache API
        if ('caches' in window) {
            const cache = await caches.open('submission-files-v1');
            await cache.put(previewFile.value.path, new Response(blob));
        }
        
        // Also download to device storage for persistent offline access
        downloadFileForOffline(filename, blob);
        
    } catch (error) {
        console.error('Error loading file:', error);
        fileLoadError.value = error.message;
        
        // Try to load from cache if online fetch failed
        if ('caches' in window) {
            try {
                const cache = await caches.open('submission-files-v1');
                const cachedResponse = await cache.match(previewFile.value.path);
                if (cachedResponse) {
                    const blob = await cachedResponse.blob();
                    fileBlob.value = URL.createObjectURL(blob);
                    fileLoadError.value = null;
                }
            } catch (cacheError) {
                console.error('Error loading from cache:', cacheError);
            }
        }
    } finally {
        isLoadingFile.value = false;
    }
};

const downloadFileForOffline = (filename, blob) => {
    try {
        // Store in IndexedDB for persistent offline storage
        const dbRequest = indexedDB.open('OfflineFilesDB', 1);
        
        dbRequest.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('files')) {
                db.createObjectStore('files', { keyPath: 'filename' });
            }
        };
        
        dbRequest.onsuccess = (event) => {
            const db = event.target.result;
            const transaction = db.transaction(['files'], 'readwrite');
            const store = transaction.objectStore('files');
            
            store.put({
                filename: filename,
                blob: blob,
                timestamp: Date.now()
            });
        };
    } catch (error) {
        console.error('Error storing file offline:', error);
    }
};

const closeFilePreview = () => {
    showFilePreview.value = false;
    
    // Clean up object URL to free memory
    if (fileBlob.value) {
        URL.revokeObjectURL(fileBlob.value);
        fileBlob.value = null;
    }
    
    previewFile.value = null;
    fileLoadError.value = null;
};

const getFileExtension = (filename) => {
    // Handle if filename is an object (attachment with name property)
    const name = typeof filename === 'object' ? filename.name : filename;
    if (!name || typeof name !== 'string') return '';
    return name.split('.').pop().toLowerCase();
};

const isImage = (filename) => {
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    return imageExts.includes(getFileExtension(filename));
};

const isPDF = (filename) => {
    return getFileExtension(filename) === 'pdf';
};

const isText = (filename) => {
    const textExts = ['txt', 'md', 'json', 'xml', 'html', 'css', 'js', 'php', 'py'];
    return textExts.includes(getFileExtension(filename));
};

const getFileIcon = (filename) => {
    const ext = getFileExtension(filename);
    if (isImage(filename)) return '🖼️';
    if (isPDF(filename)) return '📄';
    if (isText(filename)) return '📝';
    if (['doc', 'docx'].includes(ext)) return '📘';
    if (['xls', 'xlsx'].includes(ext)) return '📗';
    if (['ppt', 'pptx'].includes(ext)) return '📙';
    if (['zip', 'rar', '7z'].includes(ext)) return '📦';
    return '📎';
};
</script>

<template>
    <Head :title="`Grade: ${classwork.title}`" />

    <TeacherLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <Link :href="route('teacher.courses.show', course.id)" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Course
                </Link>
                
                <div :class="['rounded-lg p-6 mt-4', getTypeBorderClass(classwork.type)]">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-bold text-gray-900">{{ classwork.title }}</h1>
                                <span class="px-2 py-0.5 text-xs font-medium rounded uppercase" :style="{ backgroundColor: classwork.color_code + '20', color: classwork.color_code }">
                                    {{ classwork.type }}
                                </span>
                            </div>
                            <p v-if="classwork.description" class="text-gray-600 mt-2">{{ classwork.description }}</p>
                            <div class="flex gap-4 mt-4 text-sm text-gray-600">
                                <span v-if="classwork.due_date">Due: {{ new Date(classwork.due_date).toLocaleString() }}</span>
                                <span v-if="classwork.points">{{ classwork.points }} points</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 mt-6">
                    <div class="bg-blue-50 rounded-lg p-4 flex-1">
                        <div class="text-2xl font-bold text-blue-600">{{ submittedCount }}</div>
                        <div class="text-sm text-gray-600">Submitted</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 flex-1">
                        <div class="text-2xl font-bold text-green-600">{{ gradedCount }}</div>
                        <div class="text-sm text-gray-600">Graded</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 flex-1">
                        <div class="text-2xl font-bold text-gray-600">{{ students.length }}</div>
                        <div class="text-sm text-gray-600">Total Students</div>
                    </div>
                </div>
            </div>

            <!-- Submissions List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Student Submissions</h2>
                
                <div v-if="students.length === 0" class="text-center py-12 text-gray-500">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="text-lg">No students enrolled yet</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="student in students"
                        :key="student.id"
                        class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ student.name.charAt(0) }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ student.name }}</h3>
                                    <p class="text-sm text-gray-500">{{ student.email }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <template v-if="student.submission">
                                    <span :class="['px-3 py-1 text-xs rounded-lg font-medium', getStatusColor(student.submission.status)]">
                                        {{ student.submission.status }}
                                    </span>
                                    <span v-if="student.submission.grade !== null" class="text-lg font-bold text-gray-900">
                                        {{ student.submission.grade }} / {{ classwork.points }}
                                    </span>
                                    <button
                                        v-if="student.submission.status !== 'draft'"
                                        @click="openGradingModal(student.submission)"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                    >
                                        {{ student.submission.status === 'graded' ? 'Review Grade' : 'Grade' }}
                                    </button>
                                </template>
                                <template v-else>
                                    <span class="px-3 py-1 text-xs rounded-lg font-medium bg-gray-200 text-gray-700">
                                        Not submitted
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Modal -->
        <div v-if="showGradingModal" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeGradingModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <form @submit.prevent="submitGrade">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Grade Submission
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ selectedSubmission?.student?.name }}
                                    </p>
                                </div>
                                <button type="button" @click="closeGradingModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4">
                                <!-- Submission Content -->
                                <div v-if="selectedSubmission?.submission_content">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Student's Work
                                    </label>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <p class="text-gray-700 whitespace-pre-wrap">{{ selectedSubmission.submission_content }}</p>
                                    </div>
                                </div>

                                <!-- Submitted Link -->
                                <div v-if="selectedSubmission?.link">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Submitted Link
                                    </label>
                                    <a 
                                        :href="selectedSubmission.link" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-2 bg-blue-50 px-4 py-3 rounded-lg hover:bg-blue-100 transition text-blue-700 border border-blue-200"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        <span class="flex-1 text-sm font-medium break-all">{{ selectedSubmission.link }}</span>
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>

                                <!-- Attachments -->
                                <div v-if="selectedSubmission?.attachments && selectedSubmission.attachments.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Attachments
                                    </label>
                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            v-for="(attachment, index) in selectedSubmission.attachments" 
                                            :key="index" 
                                            @click="openFilePreview(attachment)"
                                            class="w-full flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg hover:bg-gray-100 transition text-left"
                                        >
                                            <span class="text-xl">{{ getFileIcon(attachment) }}</span>
                                            <span class="text-sm text-gray-700 flex-1">{{ typeof attachment === 'object' ? attachment.name : attachment }}</span>
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quiz Questions & Answers (if applicable) -->
                                <div v-if="classwork.type === 'quiz' && classwork.quiz_questions && classwork.quiz_questions.length > 0 && selectedSubmission?.quiz_answers">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Quiz Answers
                                    </label>
                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                        <div v-for="(question, index) in classwork.quiz_questions" :key="index" class="border rounded-lg p-3"
                                             :class="question.correct_answer && selectedSubmission.quiz_answers[index] ? 
                                                     (selectedSubmission.quiz_answers[index].toLowerCase().trim() === question.correct_answer.toLowerCase().trim() ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50') : 
                                                     'border-gray-200 bg-white'">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <span class="text-xs font-medium text-gray-500">Question {{ index + 1 }} ({{ question.type }})</span>
                                                    <p class="text-sm font-medium text-gray-900 mt-1">{{ question.question }}</p>
                                                </div>
                                                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ question.points }} pts</span>
                                            </div>
                                            
                                            <!-- Student's Answer -->
                                            <div class="mt-2 p-2 bg-white border border-gray-200 rounded">
                                                <p class="text-xs font-medium text-gray-600 mb-1">Student's Answer:</p>
                                                <p class="text-sm text-gray-900">{{ selectedSubmission.quiz_answers[index] || '(No answer)' }}</p>
                                            </div>
                                            
                                            <!-- Correct Answer (if exists) -->
                                            <div v-if="question.correct_answer" class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                                                <p class="text-xs font-medium text-green-800 mb-1">Correct Answer:</p>
                                                <p class="text-sm text-green-900 font-medium">{{ question.correct_answer }}</p>
                                            </div>
                                            
                                            <!-- Manual grading for essay/short answer types -->
                                            <div v-if="!question.correct_answer" class="mt-2">
                                                <p class="text-xs text-orange-600 font-medium mb-1">⚠️ Requires Manual Grading</p>
                                                <p class="text-xs text-gray-500">No auto-grading available for {{ question.type }} questions</p>
                                            </div>
                                            
                                            <!-- Auto-graded result indicator -->
                                            <div v-if="question.correct_answer && selectedSubmission.quiz_answers[index]" class="mt-2 flex items-center gap-2">
                                                <span v-if="selectedSubmission.quiz_answers[index].toLowerCase().trim() === question.correct_answer.toLowerCase().trim()" 
                                                      class="text-xs font-bold text-green-700 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Correct ({{ question.points }} pts)
                                                </span>
                                                <span v-else class="text-xs font-bold text-red-700 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Incorrect (0 pts)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="selectedSubmission.status === 'submitted'" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-sm text-blue-800">
                                            <span class="font-medium">Auto-graded:</span> {{ selectedSubmission.grade || 0 }}/{{ classwork.points }} points
                                        </p>
                                        <p class="text-xs text-blue-600 mt-1">Review the answers and adjust the total grade if needed (especially for manually graded questions)</p>
                                    </div>
                                </div>

                                <!-- Rubric Scoring (if applicable) -->
                                <div v-if="classwork.rubric_criteria && classwork.rubric_criteria.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Rubric Scoring
                                    </label>
                                    <div class="space-y-3">
                                        <div v-for="criteria in classwork.rubric_criteria" :key="criteria.id" class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex-1">
                                                    <p class="text-sm text-gray-700">{{ criteria.description }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">Max: {{ criteria.points }} points</p>
                                                </div>
                                                <input
                                                    v-model.number="gradingForm.rubric_scores[criteria.id]"
                                                    type="number"
                                                    :max="criteria.points"
                                                    min="0"
                                                    step="0.5"
                                                    @input="validateRubricScore(criteria.id, criteria.points)"
                                                    @blur="validateRubricScore(criteria.id, criteria.points)"
                                                    class="w-20 px-2 py-1 text-sm border border-gray-300 rounded-lg"
                                                    placeholder="0"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Grade -->
                                <div>
                                    <label for="grade" class="block text-sm font-medium text-gray-700 mb-1">
                                        Total Grade <span class="text-gray-500">(out of {{ classwork.points }})</span>
                                    </label>
                                    <input
                                        id="grade"
                                        v-model.number="gradingForm.grade"
                                        @input="validateGradeInput"
                                        @blur="validateGradeInput"
                                        type="number"
                                        :max="classwork.points"
                                        min="0"
                                        step="0.5"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <!-- Feedback -->
                                <div>
                                    <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">
                                        Feedback
                                    </label>
                                    <textarea
                                        id="feedback"
                                        v-model="gradingForm.feedback"
                                        rows="4"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Provide feedback for the student..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <button
                                type="submit"
                                :disabled="gradingForm.processing"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                {{ gradingForm.processing ? 'Saving...' : 'Save Grade' }}
                            </button>
                            <button
                                type="button"
                                @click="closeGradingModal"
                                :disabled="gradingForm.processing"
                                class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- File Preview Modal -->
        <div 
            v-if="showFilePreview" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="closeFilePreview"
        >
            <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold">{{ previewFile?.name }}</h3>
                    <button @click="closeFilePreview" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-auto max-h-[calc(90vh-120px)]">
                    <!-- Loading State -->
                    <div v-if="isLoadingFile" class="flex flex-col items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
                        <p class="text-gray-600">Loading file for offline viewing...</p>
                    </div>

                    <!-- Error State with Fallback -->
                    <div v-else-if="fileLoadError" class="py-8">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-yellow-800 mb-1">File Not Available</h4>
                                    <p class="text-sm text-yellow-700">{{ fileLoadError }}</p>
                                    <p class="text-xs text-yellow-600 mt-2">This is a demo attachment. In production, actual student files would be displayed here.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Show submission content as fallback -->
                        <div v-if="selectedSubmission?.submission_content" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">📝 Submission Content Preview:</h4>
                            <div class="bg-white rounded p-4 text-gray-700 whitespace-pre-wrap text-sm max-h-96 overflow-y-auto">
                                {{ selectedSubmission.submission_content }}
                            </div>
                        </div>
                    </div>

                    <!-- Image Preview -->
                    <div v-else-if="fileBlob && isImage(previewFile?.name)" class="flex justify-center">
                        <img :src="fileBlob" :alt="previewFile?.name" class="max-w-full h-auto rounded">
                    </div>
                    
                    <!-- PDF Preview -->
                    <div v-else-if="fileBlob && isPDF(previewFile?.name)" class="w-full h-[70vh]">
                        <iframe :src="fileBlob" class="w-full h-full border rounded"></iframe>
                    </div>
                    
                    <!-- Text Preview -->
                    <div v-else-if="fileBlob && isText(previewFile?.name)" class="bg-gray-50 p-4 rounded">
                        <iframe :src="fileBlob" class="w-full h-96 border-0"></iframe>
                    </div>
                    
                    <!-- Other Files -->
                    <div v-else-if="!isLoadingFile" class="text-center py-8">
                        <div class="text-6xl mb-4">{{ getFileIcon(previewFile?.name) }}</div>
                        <p class="text-gray-600 mb-2">{{ previewFile?.name }}</p>
                        <p class="text-sm text-gray-500 mb-4">File has been downloaded for offline viewing</p>
                        <a 
                            v-if="fileBlob"
                            :href="fileBlob" 
                            :download="previewFile?.name"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 inline-block"
                        >
                            Download {{ previewFile?.name }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
