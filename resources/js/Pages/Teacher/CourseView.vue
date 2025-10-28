<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import GradebookContent from '@/Components/GradebookContent.vue';

const props = defineProps({
    course: Object,
    classwork: Array,
    students: Array,
    classworks: Array,
});

// Restore active tab from sessionStorage or default to 'stream'
const activeTab = ref(sessionStorage.getItem(`courseTab_${props.course.id}`) || 'stream');
const showClassworkModal = ref(false);
const editingClasswork = ref(null);
const showDeleteConfirm = ref(false);
const classworkToDelete = ref(null);
const showStudentPerformance = ref(false);
const selectedStudent = ref(null);
const showFilePreview = ref(false);
const previewFile = ref(null);
const fileBlob = ref(null);
const isLoadingFile = ref(false);
const fileLoadError = ref(null);
const classworkForm = useForm({
    type: 'lesson',
    title: '',
    description: '',
    due_date: '',
    points: null,
    has_submission: false,
    status: 'active',
    rubric_criteria: [],
    quiz_questions: [],
    attachments: [],
});

// Rubric and Quiz management
const rubricCriteria = ref([]);
const quizQuestions = ref([]);
const fileAttachments = ref([]);

const tabs = [
    { id: 'stream', name: 'Stream' },
    { id: 'classwork', name: 'Classwork' },
    { id: 'people', name: 'People' },
    { id: 'gradebook', name: 'Gradebook' },
    { id: 'class-record', name: 'Class Record' },
];

// Watch activeTab and save to sessionStorage
watch(activeTab, (newTab) => {
    sessionStorage.setItem(`courseTab_${props.course.id}`, newTab);
});

const classworkTypes = [
    { value: 'lesson', label: 'Lesson', color: '#3b82f6', icon: '�' },
    { value: 'assignment', label: 'Assignment', color: '#eab308', icon: '📝' },
    { value: 'quiz', label: 'Quiz', color: '#ef4444', icon: '📋' },
    { value: 'activity', label: 'Activity', color: '#10b981', icon: '🎯' },
];

const questionTypes = [
    { value: 'multiple_choice', label: 'Multiple Choice' },
    { value: 'identification', label: 'Identification' },
    { value: 'enumeration', label: 'Enumeration' },
    { value: 'true_false', label: 'True/False' },
    { value: 'short_answer', label: 'Short Answer' },
    { value: 'essay', label: 'Essay' },
];

// Computed properties
const todoItems = computed(() => {
    return props.classwork?.filter(item => item.is_todo) || [];
});

const latestMaterials = computed(() => {
    return props.classwork?.slice(0, 5) || [];
});

const isQuizType = computed(() => classworkForm.type === 'quiz');

const totalRubricPoints = computed(() => {
    return rubricCriteria.value.reduce((sum, criteria) => sum + (parseInt(criteria.points) || 0), 0);
});

const totalQuizPoints = computed(() => {
    return quizQuestions.value.reduce((sum, question) => sum + (parseInt(question.points) || 0), 0);
});

// Watch for type changes to reset rubric/quiz data
watch(() => classworkForm.type, (newType) => {
    if (newType === 'quiz') {
        // Quizzes don't have rubric criteria or file attachments
        rubricCriteria.value = [];
        fileAttachments.value = []; // Clear files for quizzes
        if (quizQuestions.value.length === 0) {
            addQuizQuestion();
        }
    } else if (newType === 'lesson') {
        // Lessons don't have rubric criteria or due dates
        quizQuestions.value = [];
        rubricCriteria.value = [];
        classworkForm.due_date = null;
    } else {
        // Activities and assignments have rubric criteria but no quiz
        quizQuestions.value = [];
        if (rubricCriteria.value.length === 0) {
            addRubricCriteria();
        }
    }
});

// Watch for question type changes to initialize correct data structures
watch(() => quizQuestions.value.map(q => q.type), (newTypes, oldTypes) => {
    quizQuestions.value.forEach((question, index) => {
        if (question.type === 'enumeration' && (!question.correct_answers || question.correct_answers.length === 0)) {
            question.correct_answers = [''];
        }
        if (question.type === 'multiple_choice' && (!question.options || question.options.length === 0)) {
            question.options = ['', '', '', ''];
        }
    });
}, { deep: true });

const openClassworkModal = () => {
    editingClasswork.value = null;
    classworkForm.reset();
    rubricCriteria.value = [];
    quizQuestions.value = [];
    fileAttachments.value = [];
    addRubricCriteria(); // Start with one criteria by default
    showClassworkModal.value = true;
};

const openEditModal = (classwork) => {
    editingClasswork.value = classwork;
    classworkForm.type = classwork.type;
    classworkForm.title = classwork.title;
    classworkForm.description = classwork.description || '';
    classworkForm.due_date = classwork.due_date || '';
    classworkForm.points = classwork.points;
    classworkForm.has_submission = classwork.has_submission;
    classworkForm.status = classwork.status;
    
    // Load rubric criteria or quiz questions
    if (classwork.type === 'quiz') {
        quizQuestions.value = classwork.quiz_questions?.map(q => ({...q})) || [];
        rubricCriteria.value = [];
    } else {
        rubricCriteria.value = classwork.rubric_criteria?.map(r => ({...r})) || [];
        quizQuestions.value = [];
    }
    
    fileAttachments.value = [];
    showClassworkModal.value = true;
};

const confirmDelete = (classwork) => {
    classworkToDelete.value = classwork;
    showDeleteConfirm.value = true;
};

const deleteClasswork = () => {
    if (!classworkToDelete.value) return;
    
    router.delete(route('teacher.courses.classwork.destroy', {
        course: props.course.id,
        classwork: classworkToDelete.value.id
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            classworkToDelete.value = null;
        },
    });
};

const viewClasswork = (classwork) => {
    // Navigate to grading view
    router.visit(route('teacher.courses.classwork.show', {
        course: props.course.id,
        classwork: classwork.id
    }));
};

const closeClassworkModal = () => {
    showClassworkModal.value = false;
    editingClasswork.value = null;
    classworkForm.reset();
    rubricCriteria.value = [];
    quizQuestions.value = [];
    fileAttachments.value = [];
};

const addRubricCriteria = () => {
    rubricCriteria.value.push({
        description: '',
        points: 0,
    });
};

const removeRubricCriteria = (index) => {
    rubricCriteria.value.splice(index, 1);
};

const addQuizQuestion = () => {
    quizQuestions.value.push({
        type: 'multiple_choice',
        question: '',
        options: ['', '', '', ''],
        correct_answer: '',
        correct_answers: [],
        points: 1,
    });
};

const removeQuizQuestion = (index) => {
    quizQuestions.value.splice(index, 1);
};

const addOption = (questionIndex) => {
    quizQuestions.value[questionIndex].options.push('');
};

const removeOption = (questionIndex, optionIndex) => {
    quizQuestions.value[questionIndex].options.splice(optionIndex, 1);
};

const addEnumerationAnswer = (questionIndex) => {
    if (!quizQuestions.value[questionIndex].correct_answers) {
        quizQuestions.value[questionIndex].correct_answers = [];
    }
    quizQuestions.value[questionIndex].correct_answers.push('');
};

const removeEnumerationAnswer = (questionIndex, answerIndex) => {
    quizQuestions.value[questionIndex].correct_answers.splice(answerIndex, 1);
};

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    fileAttachments.value.push(...files);
};

const removeFile = (index) => {
    fileAttachments.value.splice(index, 1);
};

const submitClasswork = () => {
    // Set has_submission to false for lessons
    if (classworkForm.type === 'lesson') {
        classworkForm.has_submission = false;
        // Lessons don't have rubric criteria or quiz questions
        classworkForm.rubric_criteria = [];
        classworkForm.quiz_questions = [];
        classworkForm.points = 0;
    } else if (classworkForm.type === 'quiz') {
        // Quizzes have quiz questions but no rubric criteria or file attachments
        classworkForm.quiz_questions = quizQuestions.value;
        classworkForm.rubric_criteria = [];
        classworkForm.points = totalQuizPoints.value;
        // Clear any attachments for quizzes
        fileAttachments.value = [];
    } else {
        // Activities and assignments have rubric criteria but no quiz questions
        classworkForm.rubric_criteria = rubricCriteria.value;
        classworkForm.quiz_questions = [];
        classworkForm.points = totalRubricPoints.value;
    }

    // Add actual file objects to attachments for upload
    // IMPORTANT: Inertia needs the actual File objects
    classworkForm.attachments = fileAttachments.value;

    console.log('Submitting classwork:', {
        type: classworkForm.type,
        title: classworkForm.title,
        due_date: classworkForm.due_date,
        has_submission: classworkForm.has_submission,
        attachments_count: fileAttachments.value.length,
        rubric_criteria_count: classworkForm.rubric_criteria.length,
        quiz_questions_count: classworkForm.quiz_questions.length,
    });

    if (editingClasswork.value) {
        // Update existing classwork
        classworkForm.put(route('teacher.courses.classwork.update', {
            course: props.course.id,
            classwork: editingClasswork.value.id
        }), {
            preserveScroll: true,
            onSuccess: () => {
                closeClassworkModal();
            },
        });
    } else {
        // Create new classwork
        // When posting with files, Inertia automatically converts to FormData
        classworkForm.post(route('teacher.courses.classwork.store', props.course.id), {
            preserveScroll: true,
            forceFormData: true, // Force FormData for file uploads
            onSuccess: () => {
                const typeName = classworkForm.type.charAt(0).toUpperCase() + classworkForm.type.slice(1);
                closeClassworkModal();
                alert(`${typeName} created successfully!`);
            },
            onError: (errors) => {
                console.error('Failed to create classwork:', errors);
                // Show error message to user
                let errorMessage = 'Failed to create classwork:\n';
                if (typeof errors === 'object') {
                    Object.keys(errors).forEach(key => {
                        errorMessage += `${key}: ${errors[key]}\n`;
                    });
                } else {
                    errorMessage += errors;
                }
                alert(errorMessage);
            }
        });
    }
};

const getTypeColor = (type) => {
    const typeConfig = classworkTypes.find(t => t.value === type);
    return typeConfig?.color || '#3b82f6';
};

const getTypeBorderClass = (type) => {
    const colors = {
        'lesson': 'border-t-4 border-blue-500 bg-blue-50',
        'assignment': 'border-t-4 border-yellow-500 bg-yellow-50',
        'quiz': 'border-t-4 border-red-500 bg-red-50',
        'activity': 'border-t-4 border-green-500 bg-green-50',
    };
    return colors[type] || 'border-t-4 border-blue-500 bg-blue-50';
};

const viewStudentPerformance = (student) => {
    selectedStudent.value = student;
    showStudentPerformance.value = true;
};

const closeStudentPerformance = () => {
    showStudentPerformance.value = false;
    selectedStudent.value = null;
};

const openFilePreview = async (file) => {
    // Set file info
    previewFile.value = {
        name: file.name || file,
        path: file.path || `/storage/submissions/${file}`
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
        downloadFileForOffline(previewFile.value.name, blob);
        
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

// Get the appropriate icon for file types
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

const getFileExtension = (filename) => {
    return filename.split('.').pop().toLowerCase();
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
</script>

<template>
    <Head :title="`${course.title} - ${course.section}`" />

    <TeacherLayout>
        <div class="space-y-6">
            <!-- Course Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold">Course: {{ course.title }} - {{ course.section }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-blue-100">
                            <span>Section: {{ course.section }}</span>
                            <span>•</span>
                            <span>Join Code: {{ course.join_code }}</span>
                            <span 
                                class="px-3 py-1 rounded-full text-xs font-semibold"
                                :class="course.status === 'active' ? 'bg-green-500 text-white' : 'bg-gray-400 text-white'"
                            >
                                {{ course.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="tab.id === 'gradebook' ? $inertia.visit(route('teacher.courses.gradebook', course.id)) : activeTab = tab.id"
                            :class="[
                                'py-4 px-6 text-sm font-medium whitespace-nowrap transition-colors',
                                activeTab === tab.id
                                    ? 'border-b-2 border-red-900 text-red-900'
                                    : 'text-gray-600 hover:text-gray-900 hover:border-gray-300'
                            ]"
                        >
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Stream Tab -->
                    <div v-if="activeTab === 'stream'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- To-do Section -->
                        <div class="lg:col-span-2 space-y-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-4">To-do</h2>
                                <div v-if="todoItems.length === 0" class="text-gray-500 text-sm">
                                    No pending tasks
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="item in todoItems"
                                        :key="item.id"
                                        :class="['rounded-lg p-4', getTypeBorderClass(item.type)]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded" :style="{ backgroundColor: item.color_code + '20', color: item.color_code }">
                                                        {{ item.type }}
                                                    </span>
                                                </div>
                                                <p v-if="item.description" class="text-sm text-gray-600 mt-1">{{ item.description }}</p>
                                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                                                    <span v-if="item.due_date_formatted">
                                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        Due: {{ item.due_date_formatted }}
                                                    </span>
                                                    <span v-if="item.points">{{ item.points }} points</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Materials & Activities -->
                        <div>
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-4">Latest Materials & Activities</h2>
                                <div v-if="latestMaterials.length === 0" class="text-gray-500 text-sm">
                                    No materials yet
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="item in latestMaterials"
                                        :key="item.id"
                                        class="border-l-4 pl-3 py-2 hover:bg-gray-50 cursor-pointer transition"
                                        :style="{ borderColor: item.color_code }"
                                    >
                                        <h4 class="font-medium text-gray-900 text-sm">{{ item.title }}</h4>
                                        <p class="text-xs text-gray-600 mt-1">{{ item.type }} • {{ item.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Classwork Tab -->
                    <div v-if="activeTab === 'classwork'">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Classwork</h2>
                            <div class="flex gap-2">
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900">
                                    <option>All</option>
                                    <option>Materials</option>
                                    <option>Tasks</option>
                                    <option>Quizzes</option>
                                    <option>Activities</option>
                                </select>
                                <button 
                                    @click="openClassworkModal"
                                    class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                        </div>

                        <!-- Classwork Items -->
                        <div v-if="classwork.length === 0" class="text-center py-12 text-gray-500">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-lg">No classwork yet</p>
                            <p class="text-sm mt-2">Click "Add" to create your first material, task, quiz, or activity</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="item in classwork"
                                :key="item.id"
                                :class="['rounded-lg p-4', getTypeBorderClass(item.type)]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded uppercase" :style="{ backgroundColor: item.color_code + '20', color: item.color_code }">
                                                {{ item.type }}
                                            </span>
                                        </div>
                                        <p v-if="item.description" class="text-sm text-gray-600 mt-1">{{ item.description }}</p>
                                        <p v-if="item.due_date_formatted" class="text-sm text-gray-600 mt-1">
                                            Due: {{ item.due_date_formatted }}
                                        </p>
                                        <div v-if="item.points" class="text-sm text-gray-600 mt-1">
                                            {{ item.points }} points
                                        </div>
                                        <div class="flex gap-3 mt-3">
                                            <span class="px-3 py-1 bg-blue-500 text-white text-xs rounded-lg">{{ item.submitted_count }} submitted</span>
                                            <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-lg">{{ item.graded_count }} graded</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            @click="viewClasswork(item)" 
                                            class="p-2 hover:bg-gray-100 rounded" 
                                            title="View Submissions & Grade"
                                        >
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="openEditModal(item)" 
                                            class="p-2 hover:bg-gray-100 rounded" 
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="confirmDelete(item)" 
                                            class="p-2 hover:bg-red-100 rounded text-red-600" 
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- People Tab -->
                    <div v-if="activeTab === 'people'">
                        <!-- Instructors -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Instructors 
                                    <span class="ml-2 px-2 py-1 bg-gray-200 text-gray-700 text-sm rounded-full">{{ course.teachers.length }}</span>
                                </h2>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="teacher in course.teachers"
                                    :key="teacher.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                            {{ teacher.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ teacher.name }}</p>
                                            <p class="text-sm text-gray-600">{{ teacher.email }}</p>
                                            <span
                                                v-if="teacher.is_owner"
                                                class="inline-block mt-1 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded"
                                            >
                                                OWNER
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Students -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Students 
                                    <span class="ml-2 px-2 py-1 bg-gray-200 text-gray-700 text-sm rounded-full">{{ course.students.length }}</span>
                                </h2>
                            </div>

                            <div v-if="course.students.length === 0" class="text-center py-12 text-gray-500">
                                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-lg">No students yet</p>
                                <p class="text-sm mt-2">Share the join code with students to add them to this course</p>
                            </div>
                            
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="student in course.students"
                                    :key="student.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                {{ student.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ student.name }}</p>
                                                <p class="text-xs text-gray-600">{{ student.email }}</p>
                                                <p class="text-xs text-gray-500 mt-1">Joined: {{ student.joined_at }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Stats -->
                                    <div class="space-y-2 mb-3">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600">Completion</span>
                                            <span class="font-semibold text-gray-900">{{ student.progress.completion_rate }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="bg-blue-600 h-2 rounded-full transition-all" 
                                                :style="{ width: student.progress.completion_rate + '%' }"
                                            ></div>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-gray-600">
                                            <span>{{ student.progress.submitted }}/{{ student.progress.total_classwork }} submitted</span>
                                            <span>{{ student.progress.graded }} graded</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs mt-1">
                                            <span class="text-yellow-600">{{ student.progress.pending || 0 }} pending</span>
                                            <span class="text-red-600">{{ student.progress.not_submitted || 0 }} not submitted</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Average Grade -->
                                    <div v-if="student.progress.average_grade !== null" class="bg-green-50 rounded-lg p-2 mb-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium text-gray-700">Average Grade</span>
                                            <span class="text-lg font-bold text-green-700">{{ student.progress.average_grade }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="bg-gray-50 rounded-lg p-2 mb-3">
                                        <div class="text-xs text-center text-gray-500">No grades yet</div>
                                    </div>
                                    
                                    <!-- View Performance Button -->
                                    <button 
                                        @click="viewStudentPerformance(student)"
                                        class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        View Performance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gradebook Tab -->
                    <div v-if="activeTab === 'gradebook'">
                        <GradebookContent 
                            :course="course"
                            :students="students"
                            :classworks="classworks"
                        />
                    </div>

                    <!-- Class Record Tab -->
                    <div v-if="activeTab === 'class-record'">
                        <div class="bg-white rounded-lg shadow-md p-8 text-center">
                            <div class="max-w-md mx-auto">
                                <svg class="w-20 h-20 mx-auto text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Class Record</h2>
                                <p class="text-gray-600 mb-6">View and print the official class record for this course.</p>
                                <Link
                                    :href="route('teacher.courses.class-record', course.id)"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Open Class Record
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classwork Modal -->
        <div v-if="showClassworkModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div 
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                    @click="closeClassworkModal"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full max-h-[90vh] overflow-y-auto">
                    <form @submit.prevent="submitClasswork">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Create Classwork
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <!-- Type Selection -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                            <div class="grid grid-cols-4 gap-2">
                                                <button
                                                    v-for="type in classworkTypes"
                                                    :key="type.value"
                                                    type="button"
                                                    @click="classworkForm.type = type.value"
                                                    :class="[
                                                        'px-4 py-3 rounded-lg border-2 text-sm font-medium transition flex flex-col items-center gap-1',
                                                        classworkForm.type === type.value
                                                            ? 'border-current shadow-md'
                                                            : 'border-gray-200 hover:border-gray-300'
                                                    ]"
                                                    :style="{ 
                                                        color: classworkForm.type === type.value ? type.color : '#6b7280',
                                                        backgroundColor: classworkForm.type === type.value ? type.color + '15' : 'transparent'
                                                    }"
                                                >
                                                    <span class="text-2xl">{{ type.icon }}</span>
                                                    <span>{{ type.label }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Title -->
                                        <div>
                                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                                Title <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="title"
                                                v-model="classworkForm.title"
                                                type="text"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                                placeholder="Enter title"
                                            />
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                                Description
                                            </label>
                                            <textarea
                                                id="description"
                                                v-model="classworkForm.description"
                                                rows="3"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                                placeholder="Enter description (optional)"
                                            ></textarea>
                                        </div>

                                        <!-- Quiz Questions Section -->
                                        <div v-if="isQuizType" class="border-2 border-red-200 rounded-lg p-4 bg-red-50">
                                            <div class="flex items-center justify-between mb-4">
                                                <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    Quiz Questions
                                                </h4>
                                                <span class="text-sm font-medium text-red-600">Total: {{ totalQuizPoints }} pts</span>
                                            </div>

                                            <div class="space-y-4 max-h-64 overflow-y-auto pr-2">
                                                <div v-for="(question, qIndex) in quizQuestions" :key="qIndex" class="bg-white border border-red-200 rounded-lg p-3 space-y-3">
                                                    <div class="flex items-start justify-between">
                                                        <span class="text-sm font-medium text-gray-700">Question {{ qIndex + 1 }}</span>
                                                        <button type="button" @click="removeQuizQuestion(qIndex)" class="text-red-600 hover:text-red-800">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <select v-model="question.type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900">
                                                        <option v-for="qType in questionTypes" :key="qType.value" :value="qType.value">
                                                            {{ qType.label }}
                                                        </option>
                                                    </select>

                                                    <input v-model="question.question" type="text" placeholder="Enter question" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900" />

                                                    <!-- Multiple Choice Options -->
                                                    <div v-if="question.type === 'multiple_choice'" class="space-y-2">
                                                        <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex gap-2">
                                                            <input v-model="question.options[oIndex]" type="text" :placeholder="`Option ${oIndex + 1}`" class="flex-1 px-3 py-1 text-sm border border-gray-300 rounded-lg" />
                                                            <button v-if="question.options.length > 2" type="button" @click="removeOption(qIndex, oIndex)" class="text-red-600 hover:text-red-800">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <button type="button" @click="addOption(qIndex)" class="text-sm text-red-600 hover:text-red-800">+ Add Option</button>
                                                        <input v-model="question.correct_answer" type="text" placeholder="Correct answer" class="w-full px-3 py-1 text-sm border border-green-300 rounded-lg bg-green-50" />
                                                    </div>

                                                    <!-- True/False -->
                                                    <div v-if="question.type === 'true_false'">
                                                        <select v-model="question.correct_answer" class="w-full px-3 py-2 text-sm border border-green-300 rounded-lg bg-green-50">
                                                            <option value="">Select correct answer</option>
                                                            <option value="True">True</option>
                                                            <option value="False">False</option>
                                                        </select>
                                                    </div>

                                                    <!-- Identification -->
                                                    <div v-if="question.type === 'identification'">
                                                        <input v-model="question.correct_answer" type="text" placeholder="Enter correct answer" class="w-full px-3 py-2 text-sm border border-green-300 rounded-lg bg-green-50" />
                                                    </div>

                                                    <!-- Enumeration -->
                                                    <div v-if="question.type === 'enumeration'" class="space-y-2">
                                                        <label class="text-sm font-medium text-gray-700">Correct Answers:</label>
                                                        <div v-for="(answer, aIndex) in question.correct_answers" :key="aIndex" class="flex gap-2">
                                                            <input v-model="question.correct_answers[aIndex]" type="text" :placeholder="`Answer ${aIndex + 1}`" class="flex-1 px-3 py-1 text-sm border border-green-300 rounded-lg bg-green-50" />
                                                            <button v-if="question.correct_answers.length > 1" type="button" @click="removeEnumerationAnswer(qIndex, aIndex)" class="text-red-600 hover:text-red-800">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <button type="button" @click="addEnumerationAnswer(qIndex)" class="text-sm text-green-600 hover:text-green-800">+ Add Answer</button>
                                                    </div>

                                                    <div class="flex items-center gap-2">
                                                        <label class="text-sm text-gray-600">Points:</label>
                                                        <input v-model.number="question.points" type="number" min="1" class="w-20 px-3 py-1 text-sm border border-gray-300 rounded-lg" />
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="button" @click="addQuizQuestion" class="mt-3 w-full px-4 py-2 border-2 border-dashed border-red-300 rounded-lg text-red-600 hover:bg-red-100 transition flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Add Question
                                            </button>
                                        </div>

                                        <!-- Rubric Criteria Section (for activities and assignments only, not lessons) -->
                                        <div v-if="!isQuizType && classworkForm.type !== 'lesson'" class="border-2 border-indigo-200 rounded-lg p-4 bg-indigo-50">
                                            <div class="flex items-center justify-between mb-4">
                                                <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Rubric Criteria
                                                </h4>
                                                <span class="text-sm font-medium text-indigo-600">Total: {{ totalRubricPoints }} pts</span>
                                            </div>

                                            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                                                <div v-for="(criteria, index) in rubricCriteria" :key="index" class="bg-white border border-indigo-200 rounded-lg p-3 flex gap-3">
                                                    <div class="flex-1 space-y-2">
                                                        <input 
                                                            v-model="criteria.description" 
                                                            type="text" 
                                                            placeholder="Criteria description" 
                                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600"
                                                            required
                                                        />
                                                        <div class="flex items-center gap-2">
                                                            <label class="text-sm text-gray-600">Points:</label>
                                                            <input 
                                                                v-model.number="criteria.points" 
                                                                type="number" 
                                                                min="0" 
                                                                placeholder="Points"
                                                                class="w-24 px-3 py-1 text-sm border border-gray-300 rounded-lg"
                                                                required
                                                            />
                                                        </div>
                                                    </div>
                                                    <button 
                                                        type="button" 
                                                        @click="removeRubricCriteria(index)" 
                                                        class="text-red-600 hover:text-red-800 self-start"
                                                        :disabled="rubricCriteria.length === 1"
                                                        :class="{ 'opacity-50 cursor-not-allowed': rubricCriteria.length === 1 }"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <button 
                                                type="button" 
                                                @click="addRubricCriteria" 
                                                class="mt-3 w-full px-4 py-2 border-2 border-dashed border-indigo-300 rounded-lg text-indigo-600 hover:bg-indigo-100 transition flex items-center justify-center gap-2"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Add Criteria
                                            </button>
                                        </div>

                                        <!-- Due Date (not for lessons) -->
                                        <div v-if="classworkForm.type !== 'lesson'">
                                            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                                                Due Date
                                            </label>
                                            <input
                                                id="due_date"
                                                v-model="classworkForm.due_date"
                                                type="datetime-local"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- File Attachments (not for quizzes) -->
                                        <div v-if="classworkForm.type !== 'quiz'">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Attachments
                                            </label>
                                            <div class="space-y-2">
                                                <input
                                                    type="file"
                                                    @change="handleFileUpload"
                                                    multiple
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-900 focus:border-transparent"
                                                />
                                                <div v-if="fileAttachments.length > 0" class="space-y-1">
                                                    <div v-for="(file, index) in fileAttachments" :key="index" class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded-lg text-sm">
                                                        <span class="text-gray-700 flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                            </svg>
                                                            {{ file.name }}
                                                        </span>
                                                        <button type="button" @click="removeFile(index)" class="text-red-600 hover:text-red-800">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Has Submission (Hidden for lessons) -->
                                        <div v-if="classworkForm.type !== 'lesson'" class="flex items-center">
                                            <input
                                                id="has_submission"
                                                v-model="classworkForm.has_submission"
                                                type="checkbox"
                                                class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded"
                                            />
                                            <label for="has_submission" class="ml-2 block text-sm text-gray-700">
                                                Requires submission
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <button
                                type="submit"
                                :disabled="classworkForm.processing"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-900 text-base font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-900 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                {{ classworkForm.processing ? (editingClasswork ? 'Updating...' : 'Creating...') : (editingClasswork ? 'Update' : 'Create') }}
                            </button>
                            <button
                                type="button"
                                @click="closeClassworkModal"
                                :disabled="classworkForm.processing"
                                class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-900 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showDeleteConfirm = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Delete Classwork
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete "{{ classworkToDelete?.title }}"? This action cannot be undone and will delete all associated submissions and grades.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            type="button"
                            @click="deleteClasswork"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Delete
                        </button>
                        <button
                            type="button"
                            @click="showDeleteConfirm = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Performance Modal -->
        <div v-if="showStudentPerformance && selectedStudent" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeStudentPerformance"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-2xl">
                                    {{ selectedStudent.name.charAt(0) }}
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900">{{ selectedStudent.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ selectedStudent.email }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Joined: {{ selectedStudent.joined_at }}</p>
                                </div>
                            </div>
                            <button type="button" @click="closeStudentPerformance" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Performance Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Completion Rate</div>
                                <div class="text-3xl font-bold text-blue-600">{{ selectedStudent.progress.completion_rate }}%</div>
                                <div class="text-xs text-gray-500 mt-1">{{ selectedStudent.progress.submitted }}/{{ selectedStudent.progress.total_classwork }}</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Average Grade</div>
                                <div class="text-3xl font-bold text-green-600">{{ selectedStudent.progress.average_grade || 'N/A' }}</div>
                                <div class="text-xs text-gray-500 mt-1">Overall performance</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Submitted</div>
                                <div class="text-3xl font-bold text-yellow-600">{{ selectedStudent.progress.submitted }}</div>
                                <div class="text-xs text-gray-500 mt-1">Total submissions</div>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Graded</div>
                                <div class="text-3xl font-bold text-purple-600">{{ selectedStudent.progress.graded }}</div>
                                <div class="text-xs text-gray-500 mt-1">Work evaluated</div>
                            </div>
                        </div>

                        <!-- Performance Graph -->
                        <div v-if="selectedStudent.recent_submissions && selectedStudent.recent_submissions.length > 0" class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Grade Trend</h4>
                            <div class="space-y-3">
                                <div v-for="(submission, index) in selectedStudent.recent_submissions" :key="index" class="bg-white rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ submission.title }}</div>
                                            <div class="text-xs text-gray-500">{{ submission.graded_at }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-900">{{ submission.grade }}/{{ submission.max_points }}</div>
                                            <div class="text-xs" :class="submission.percentage >= 90 ? 'text-green-600' : submission.percentage >= 75 ? 'text-blue-600' : submission.percentage >= 60 ? 'text-yellow-600' : 'text-red-600'">
                                                {{ submission.percentage }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all"
                                            :class="submission.percentage >= 90 ? 'bg-green-500' : submission.percentage >= 75 ? 'bg-blue-500' : submission.percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                                            :style="{ width: submission.percentage + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="bg-gray-50 rounded-lg p-12 text-center mb-6">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p class="text-gray-500">No graded submissions yet</p>
                        </div>

                        <!-- Progress Breakdown -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Progress Breakdown</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Total Classwork</span>
                                    <span class="font-semibold text-gray-900">{{ selectedStudent.progress.total_classwork }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Submitted</span>
                                    <span class="font-semibold text-blue-600">{{ selectedStudent.progress.submitted }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Graded</span>
                                    <span class="font-semibold text-green-600">{{ selectedStudent.progress.graded }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Pending</span>
                                    <span class="font-semibold text-yellow-600">{{ selectedStudent.progress.submitted - selectedStudent.progress.graded }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                    <span class="text-sm font-semibold text-gray-900">Not Submitted</span>
                                    <span class="font-bold text-red-600">{{ selectedStudent.progress.total_classwork - selectedStudent.progress.submitted }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="closeStudentPerformance"
                            class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
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

                    <!-- Error State -->
                    <div v-else-if="fileLoadError" class="py-8">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                            <div class="text-6xl mb-4">{{ getFileIcon(previewFile?.name) }}</div>
                            <h4 class="font-semibold text-yellow-800 mb-2">File Not Available</h4>
                            <p class="text-sm text-yellow-700 mb-1">{{ fileLoadError }}</p>
                            <p class="text-xs text-yellow-600 mt-3">
                                This is a demo attachment. In production, actual files would be uploaded and displayed here.
                            </p>
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
