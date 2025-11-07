                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <script setup>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import GradebookContent from '@/Components/GradebookContent.vue';
import { useOfflineSync } from '@/composables/useOfflineSync';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    import { useOfflineFiles } from '@/composables/useOfflineFiles';

const props = defineProps({
    course: Object,
    classwork: Array,
    students: Array,
    classworks: Array,
    announcements: Array,
});

// Debug: Check students prop and program data
console.log('CourseView - Students prop:', props.students);
console.log('CourseView - Students count:', props.students?.length);
console.log('CourseView - Course students:', props.course.students);
console.log('CourseView - First course student:', props.course.students?.[0]);
console.log('CourseView - Course program:', props.course.program);
console.log('CourseView - Course object:', props.course);

// Create a reactive ref for gradebook that can be updated
const gradebook = ref(props.course.gradebook || {
    midtermPercentage: 50,
    finalsPercentage: 50,
    midterm: { tables: [], grades: {}, periodGrades: {} },
    finals: { tables: [], grades: {}, periodGrades: {} }
});

// Sync local gradebook with server-provided course.gradebook when it changes (e.g., after creating gradebook subcolumns)
watch(
    () => props.course?.gradebook,
    (newVal) => {
        if (newVal && typeof newVal === 'object') {
            gradebook.value = newVal;
        }
    },
    { deep: true }
);

// Restore active tab from sessionStorage or default to 'classwork'
const activeTab = ref(sessionStorage.getItem(`courseTab_${props.course.id}`) || 'classwork');
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
    show_correct_answers: false,
    grading_period: '',
    grade_table_name: '',
    grade_main_column: '',
    grade_sub_column: '',
});

// Rubric and Quiz management
const rubricCriteria = ref([]);
const quizQuestions = ref([]);
const fileAttachments = ref([]); // New file uploads (File objects)
const existingAttachments = ref([]); // Existing attachments from edit (metadata only)

// Material filter
const materialFilter = ref('all');

const tabs = [
    { id: 'classwork', name: 'Classroom' },
    { id: 'people', name: 'People' },
    { id: 'gradebook', name: 'Gradebook' },
    { id: 'class-record', name: 'Class Record' },
];

// Offline sync utilities
const { saveOfflineAction, updatePendingCount, savePendingFiles } = useOfflineSync();
const { downloadFiles, downloadClassworkAttachments, isFileCached } = useOfflineFiles();

// Watch activeTab and save to sessionStorage
watch(activeTab, (newTab, oldTab) => {
    sessionStorage.setItem(`courseTab_${props.course.id}`, newTab);
});

const classworkTypes = [
    { value: 'lesson', label: 'Lesson', color: '#3b82f6', icon: '📚' },
    { value: 'assignment', label: 'Assignment', color: '#eab308', icon: '📝' },
    { value: 'quiz', label: 'Quiz', color: '#ef4444', icon: '📋' },
    { value: 'activity', label: 'Activity', color: '#10b981', icon: '🎯' },
    { value: 'essay', label: 'Essay', color: '#8b5cf6', icon: '✍️' },
    { value: 'project', label: 'Project', color: '#f97316', icon: '🚀' },
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
const gradeAccessRequests = computed(() => {
    return props.course.students?.filter(student => 
        student.grade_access_requested && !student.grade_access_granted
    ) || [];
});

const todoItems = computed(() => {
    // Use full classwork list for Classroom (includes lessons/materials)
    return props.classwork?.filter(item => item.is_todo) || [];
});

const latestMaterials = computed(() => {
    return props.classwork?.slice(0, 5) || [];
});

// Filtered materials based on materialFilter
const filteredMaterials = computed(() => {
    if (!props.classwork) return [];
    
    if (materialFilter.value === 'all') {
        return props.classwork;
    }
    
    // Filter by type
    return props.classwork.filter(item => {
        const type = item.type.toLowerCase();
        const filter = materialFilter.value.toLowerCase();
        
        if (filter === 'materials') {
            return type === 'lesson';
        } else if (filter === 'tasks') {
            return type === 'assignment';
        } else if (filter === 'quizzes') {
            return type === 'quiz';
        } else if (filter === 'activities') {
            return type === 'activity';
        } else if (filter === 'essays') {
            return type === 'essay';
        } else if (filter === 'projects') {
            return type === 'project';
        }
        
        return true;
    });
});

// Filtered announcements based on materialFilter
const filteredAnnouncements = computed(() => {
    if (!props.announcements) return [];
    
    // Only show announcements when filter is 'all' or 'materials'
    if (materialFilter.value === 'all' || materialFilter.value === 'materials') {
        return props.announcements;
    }
    
    return [];
});

const isQuizType = computed(() => classworkForm.type === 'quiz');

const totalRubricPoints = computed(() => {
    return rubricCriteria.value.reduce((sum, criteria) => sum + (parseInt(criteria.points) || 0), 0);
});

const totalQuizPoints = computed(() => {
    return quizQuestions.value.reduce((sum, question) => sum + (parseInt(question.points) || 0), 0);
});

// Get available grading periods from gradebook
const availableGradingPeriods = computed(() => {
    // Always show Midterm and Finals options, even for new courses without a gradebook yet
    return [
        { value: 'midterm', label: 'Midterm' },
        { value: 'finals', label: 'Finals' }
    ];
});

// Get available grade tables for selected grading period
const availableGradeTables = computed(() => {
    if (!classworkForm.grading_period) return [];
    
    // Always start with default tables
    const examName = classworkForm.grading_period === 'midterm' ? 'Midterm Examination' : 'Final Examination';
    const defaultTables = [
        { value: 'Asynchronous', label: 'Asynchronous' },
        { value: 'Synchronous', label: 'Synchronous' },
        { value: examName, label: examName }
    ];
    
    // Check both the reactive gradebook ref and the props
    const gradebookData = gradebook.value || props.course?.gradebook;
    
    // If gradebook doesn't exist yet, return default tables only
    if (!gradebookData) {
        return defaultTables;
    }
    
    const periodData = gradebookData[classworkForm.grading_period];
    if (!periodData || !periodData.tables) {
        return defaultTables;
    }
    
    // Get custom tables (exclude Summary table)
    const customTables = periodData.tables
        .filter(table => !table.isSummary && !['Asynchronous', 'Synchronous', examName].includes(table.name))
        .map(table => ({
            value: table.name,
            label: table.name
        }));
    
    // Combine default tables with custom tables
    return [...defaultTables, ...customTables];
});

// Get available main columns for selected grade table
const availableMainColumns = computed(() => {
    if (!classworkForm.grading_period || !classworkForm.grade_table_name) return [];
    
    // Check both the reactive gradebook ref and the props
    const gradebookData = gradebook.value || props.course?.gradebook;
    
    // If no gradebook data exists, return empty (user needs to create columns in gradebook first)
    if (!gradebookData) {
        console.log('No gradebook data available');
        return [];
    }
    
    const periodData = gradebookData[classworkForm.grading_period];
    if (!periodData || !periodData.tables) {
        console.log('No period data or tables');
        return [];
    }
    
    // Find the selected table in the gradebook
    const table = periodData.tables.find(t => t.name === classworkForm.grade_table_name);
    
    if (!table) {
        console.log('Table not found:', classworkForm.grade_table_name);
        console.log('Available tables:', periodData.tables.map(t => t.name));
        return [];
    }
    
    if (!table.columns || table.columns.length === 0) {
        console.log('No columns in table:', classworkForm.grade_table_name);
        return [];
    }
    
    console.log('Found columns for table:', classworkForm.grade_table_name, table.columns);
    return table.columns.map(col => ({
        value: col.name,
        label: col.name
    }));
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
        // Clear gradebook fields for lessons
        classworkForm.grading_period = '';
        classworkForm.grade_table_name = '';
        classworkForm.grade_main_column = '';
        classworkForm.grade_sub_column = '';
    } else {
        // Activities and assignments have rubric criteria but no quiz
        quizQuestions.value = [];
        if (rubricCriteria.value.length === 0) {
            addRubricCriteria();
        }
    }
});

// Watch for title changes to auto-generate sub-column name
watch(() => classworkForm.title, (newTitle) => {
    if (newTitle && classworkForm.type !== 'lesson' && classworkForm.grade_main_column) {
        classworkForm.grade_sub_column = newTitle.substring(0, 30); // Limit to 30 chars
    }
});

// Watch gradebook fields to debug selection
watch(() => classworkForm.grading_period, (val) => {
    console.log('🎯 Grading Period changed:', val);
});
watch(() => classworkForm.grade_table_name, (val) => {
    console.log('📊 Grade Table changed:', val);
});
watch(() => classworkForm.grade_main_column, (val) => {
    console.log('📋 Main Column changed:', val);
});
watch(() => classworkForm.grade_sub_column, (val) => {
    console.log('📝 Sub Column changed:', val);
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
    existingAttachments.value = [];
    addRubricCriteria(); // Start with one criteria by default
    showClassworkModal.value = true;
};

const grantAccess = (studentId) => {
    router.post(route('teacher.courses.grant-grade-access', { course: props.course.id, student: studentId }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Reload the page data to show updated student data
            router.reload({ only: ['students'] });
        }
    });
};

const revokeAccess = (studentId) => {
    if (confirm('Are you sure you want to hide grades from this student?')) {
        router.post(route('teacher.courses.revoke-grade-access', { course: props.course.id, student: studentId }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page data to show updated student data
                router.reload({ only: ['students'] });
            }
        });
    }
};

const denyRequest = (studentId) => {
    if (confirm('Are you sure you want to deny this grade access request?')) {
        router.post(route('teacher.courses.revoke-grade-access', { course: props.course.id, student: studentId }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page data to show updated student data
                router.reload({ only: ['students'] });
            }
        });
    }
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
    classworkForm.show_correct_answers = classwork.show_correct_answers || false;
    classworkForm.grading_period = classwork.grading_period || '';
    classworkForm.grade_table_name = classwork.grade_table_name || '';
    classworkForm.grade_main_column = classwork.grade_main_column || '';
    classworkForm.grade_sub_column = classwork.grade_sub_column || '';
    
    // Load rubric criteria or quiz questions
    if (classwork.type === 'quiz') {
        quizQuestions.value = classwork.quiz_questions?.map(q => ({...q})) || [];
        rubricCriteria.value = [];
    } else {
        rubricCriteria.value = classwork.rubric_criteria?.map(r => ({...r})) || [];
        quizQuestions.value = [];
    }
    
    // Load existing attachments (for display only, not for submission)
    existingAttachments.value = classwork.attachments || [];
    fileAttachments.value = []; // Clear new file uploads
    showClassworkModal.value = true;

    // Prefetch attachments for this classwork so teacher can view offline immediately
    if (classwork.attachments && classwork.attachments.length > 0 && navigator.onLine) {
        try { downloadClassworkAttachments(classwork); } catch (e) { console.warn('Teacher prefetch failed:', e); }
    }
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
            // Reload the page data to remove deleted classwork and update progress
            router.reload({ only: ['course', 'classwork', 'classworks', 'students'] });
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
    existingAttachments.value = [];
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
        option_labels: ['A', 'B', 'C', 'D'],
        correct_answer: '',
        correct_answers: [],
        points: 1,
    });
};

const removeQuizQuestion = (index) => {
    quizQuestions.value.splice(index, 1);
};

const addOption = (questionIndex) => {
    const question = quizQuestions.value[questionIndex];
    if (!question.option_labels) {
        question.option_labels = [];
    }
    
    // Generate next letter (E, F, G, etc.)
    const nextLetter = String.fromCharCode(65 + question.options.length); // 65 is 'A'
    question.options.push('');
    question.option_labels.push(nextLetter);
};

const removeOption = (questionIndex, optionIndex) => {
    const question = quizQuestions.value[questionIndex];
    question.options.splice(optionIndex, 1);
    if (question.option_labels) {
        question.option_labels.splice(optionIndex, 1);
    }
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
    const maxSize = 10 * 1024 * 1024; // 10MB in bytes
    const validFiles = [];
    const invalidFiles = [];
    
    files.forEach(file => {
        if (file.size <= maxSize) {
            validFiles.push(file);
        } else {
            invalidFiles.push(file.name);
        }
    });
    
    if (validFiles.length > 0) {
        fileAttachments.value.push(...validFiles);
    }
    
    if (invalidFiles.length > 0) {
        alert(`The following files exceed the 10MB limit and were not added:\n${invalidFiles.join('\n')}`);
    }
    
    // Clear the input so the same file can be selected again if needed
    event.target.value = '';
};

const removeFile = (index) => {
    fileAttachments.value.splice(index, 1);
};

const submitClasswork = async () => {
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

    console.log('Submitting classwork:', {
        type: classworkForm.type,
        title: classworkForm.title,
        due_date: classworkForm.due_date,
        has_submission: classworkForm.has_submission,
        new_attachments_count: fileAttachments.value.length,
        existing_attachments_count: existingAttachments.value.length,
        rubric_criteria_count: classworkForm.rubric_criteria.length,
        quiz_questions_count: classworkForm.quiz_questions.length,
        grading_period: classworkForm.grading_period,
        grade_table_name: classworkForm.grade_table_name,
        grade_main_column: classworkForm.grade_main_column,
        grade_sub_column: classworkForm.grade_sub_column,
    });

    if (editingClasswork.value) {
        // When editing: only send attachments if there are NEW files to upload
        // If no new files, don't send attachments field at all to preserve existing ones
        const updateData = {
            ...classworkForm.data(),
            _method: 'PUT'
        };
        
        // Only include attachments if there are new files
        if (fileAttachments.value.length > 0) {
            updateData.attachments = fileAttachments.value;
        } else {
            // Don't send attachments field - backend will keep existing files
            delete updateData.attachments;
        }
        
        // Update existing classwork
        // Validate we have a classwork id for the update route
        const classworkId = editingClasswork.value?.id;
        if (!classworkId) {
            console.error('Edit attempted without a valid classwork id:', editingClasswork.value);
            alert('Could not update: missing classwork ID. Please close the modal and try editing again.');
            return;
        }

        // Use POST with _method=PUT for file uploads (Laravel requirement)
        classworkForm.transform(() => updateData).post(route('teacher.courses.classwork.update', {
            course: props.course.id,
            classwork: classworkId
        }), {
            preserveScroll: true,
            forceFormData: fileAttachments.value.length > 0, // Only force FormData if uploading files
            onSuccess: () => {
                closeClassworkModal();
                alert('Classwork updated successfully!');
                // Reload the page data to show updated classwork and progress
                router.reload({ only: ['course', 'classwork', 'classworks', 'students'] });
            },
            onError: (errors) => {
                console.error('Failed to update classwork:', errors);
                let errorMessage = 'Failed to update classwork:\n';
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
    } else {
        // When creating: if offline, queue for later sync; if online, post immediately
        const isOnline = navigator.onLine;
        if (!isOnline) {
            // Build a plain JSON payload (no File objects) for offline queue
            const payload = {
                course_id: props.course.id,
                type: classworkForm.type,
                title: classworkForm.title,
                description: classworkForm.description,
                due_date: classworkForm.due_date,
                points: classworkForm.points,
                has_submission: classworkForm.has_submission,
                status: classworkForm.status,
                rubric_criteria: classworkForm.rubric_criteria,
                quiz_questions: classworkForm.quiz_questions,
                show_correct_answers: classworkForm.show_correct_answers,
                grading_period: classworkForm.grading_period,
                grade_table_name: classworkForm.grade_table_name,
                grade_main_column: classworkForm.grade_main_column,
                grade_sub_column: classworkForm.grade_sub_column,
                // attachments will be saved separately in PendingUploadsDB
            };

            try {
                const res = await saveOfflineAction('create_classwork', payload);
                // Persist file blobs for this pending action so we can upload later
                if (fileAttachments.value && fileAttachments.value.length > 0 && res?.id) {
                    await savePendingFiles(res.id, fileAttachments.value);
                }
                closeClassworkModal();
                alert('Material saved offline. It will sync automatically when you are online.');
                await updatePendingCount();
                return;
            } catch (err) {
                console.error('Failed to save classwork offline:', err);
                alert('Failed to save offline. Please try again.');
                return;
            }
        }

        // Online: send all new file uploads now
        // Add a one-time idempotency token to prevent double-create on double-click
        classworkForm.attachments = fileAttachments.value;

        classworkForm.post(route('teacher.courses.classwork.store', props.course.id), {
            preserveScroll: true,
            forceFormData: true, // Force FormData for file uploads
            onSuccess: () => {
                const typeName = classworkForm.type.charAt(0).toUpperCase() + classworkForm.type.slice(1);
                closeClassworkModal();
                alert(`${typeName} created successfully!`);
                // If this material is linked to the gradebook, switch to the Gradebook tab for immediate visibility
                if (classworkForm.grading_period && classworkForm.grade_table_name && classworkForm.grade_main_column) {
                    activeTab.value = 'gradebook';
                }
                router.reload({ only: ['course', 'classwork', 'classworks', 'students'] });
                // After reload completes, a global event may be fired; also proactively prefetch current props soon
                setTimeout(() => prefetchAllCourseAttachments(), 500);
            },
            onError: (errors) => {
                console.error('Failed to create classwork:', errors);
                console.error('Full error object:', JSON.stringify(errors, null, 2));
                let errorMessage = 'Failed to create classwork:\n\n';
                if (typeof errors === 'object') {
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMessage += `${key}: ${errors[key].join(', ')}\n`;
                        } else {
                            errorMessage += `${key}: ${errors[key]}\n`;
                        }
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
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'];
    return imageExts.includes(getFileExtension(filename));
};

const isPDF = (filename) => {
    return getFileExtension(filename) === 'pdf';
};

const isText = (filename) => {
    const textExts = ['txt', 'md', 'json', 'xml', 'html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'h'];
    return textExts.includes(getFileExtension(filename));
};

const isWord = (filename) => {
    const wordExts = ['doc', 'docx'];
    return wordExts.includes(getFileExtension(filename));
};

const isExcel = (filename) => {
    const excelExts = ['xls', 'xlsx', 'csv'];
    return excelExts.includes(getFileExtension(filename));
};

const isPowerPoint = (filename) => {
    const pptExts = ['ppt', 'pptx'];
    return pptExts.includes(getFileExtension(filename));
};

const isOfficeDocument = (filename) => {
    return isWord(filename) || isExcel(filename) || isPowerPoint(filename);
};

const getOfficeViewerUrl = (fileUrl) => {
    // Use Google Docs Viewer as it works better with local files
    // Need to convert relative URL to absolute URL
    const absoluteUrl = fileUrl.startsWith('http') ? fileUrl : window.location.origin + fileUrl;
    return `https://docs.google.com/gview?url=${encodeURIComponent(absoluteUrl)}&embedded=true`;
};

// Class Record Helper Functions
const getClassworksByType = (period, types) => {
    return props.classworks.filter(cw => 
        cw.grading_period === period && types.includes(cw.type)
    );
};

const calculateComponentGrade = (studentId, period, types) => {
    const classworkItems = getClassworksByType(period, types);
    if (classworkItems.length === 0) return '-';
    
    const student = props.students.find(s => s.id === studentId);
    if (!student || !student.submissions) return '-';
    
    let totalScore = 0;
    let totalMaxPoints = 0;
    let hasGrades = false;
    
    classworkItems.forEach(cw => {
        const submission = student.submissions.find(sub => 
            sub.classwork_id === cw.id && 
            ['graded', 'returned'].includes(sub.status) &&
            sub.grade !== null
        );
        
        if (submission) {
            totalScore += parseFloat(submission.grade || 0);
            totalMaxPoints += parseFloat(cw.points || 0);
            hasGrades = true;
        }
    });
    
    if (!hasGrades || totalMaxPoints === 0) return '-';
    
    // Calculate percentage based on component weight
    const percentage = (totalScore / totalMaxPoints) * 100;
    
    // Apply component weight (30% for written works & exam, 40% for performance tasks)
    let weight = 0.30; // Default for written works and exam
    if (types.includes('activity')) weight = 0.40; // Performance tasks
    
    return (percentage * weight / 100 * 100).toFixed(2);
};

const calculatePeriodGrade = (studentId, period) => {
    const writtenWorks = calculateComponentGrade(studentId, period, ['assignment', 'quiz']);
    const performanceTasks = calculateComponentGrade(studentId, period, ['activity']);
    const assessment = calculateComponentGrade(studentId, period, ['exam']);
    
    if (writtenWorks === '-' && performanceTasks === '-' && assessment === '-') return '-';
    
    const ww = writtenWorks === '-' ? 0 : parseFloat(writtenWorks);
    const pt = performanceTasks === '-' ? 0 : parseFloat(performanceTasks);
    const qa = assessment === '-' ? 0 : parseFloat(assessment);
    
    return (ww + pt + qa).toFixed(2);
};

const calculateFinalGrade = (studentId) => {
    const midtermGrade = calculatePeriodGrade(studentId, 'midterm');
    const finalsGrade = calculatePeriodGrade(studentId, 'finals');
    
    if (midtermGrade === '-' && finalsGrade === '-') return '-';
    
    const midterm = midtermGrade === '-' ? 0 : parseFloat(midtermGrade);
    const finals = finalsGrade === '-' ? 0 : parseFloat(finalsGrade);
    
    // 50% midterm + 50% finals
    return ((midterm * 0.5) + (finals * 0.5)).toFixed(2);
};

const getRemarks = (finalGrade) => {
    if (finalGrade === '-') return 'No Grade';
    
    const grade = parseFloat(finalGrade);
    
    if (grade >= 90) return 'EXCELLENT';
    if (grade >= 85) return 'VERY GOOD';
    if (grade >= 80) return 'GOOD';
    if (grade >= 75) return 'FAIR';
    return 'FAILED';
};

const getRemarkClass = (finalGrade) => {
    if (finalGrade === '-') return 'text-gray-500';
    
    const grade = parseFloat(finalGrade);
    
    if (grade >= 90) return 'text-green-600';
    if (grade >= 85) return 'text-blue-600';
    if (grade >= 80) return 'text-yellow-600';
    if (grade >= 75) return 'text-orange-600';
    return 'text-red-600';
};

// Class Record Program Type Toggle
// Persist the selected program type per-course in localStorage so the teacher's choice
// is remembered when navigating away and back.
const programType = ref(localStorage.getItem(`courseProgramType_${props.course.id}`) || 'masteral'); // 'masteral' or 'doctorate'
const passingGrade = computed(() => programType.value === 'masteral' ? 1.75 : 1.45);

// Persist changes to localStorage
watch(programType, (newVal) => {
    try {
        localStorage.setItem(`courseProgramType_${props.course.id}`, newVal);
    } catch (e) {
        // ignore storage errors (e.g., private mode)
        console.warn('Could not persist programType', e);
    }
});

// Boolean helper to drive the toggle switch (true = doctorate)
const isDoctorate = computed({
    get: () => programType.value === 'doctorate',
    set: (val) => { programType.value = val ? 'doctorate' : 'masteral'; }
});

// Class Record Grade Calculation Functions
// Use the reactive gradebook ref so changes are reflected immediately
const getMidtermGrade = (studentId) => {
    console.log('[ClassRecord] getMidtermGrade for student', studentId, 'gradebook:', gradebook.value);
    
    if (!gradebook.value || !gradebook.value.midterm) {
        console.log('[ClassRecord] No gradebook or midterm data');
        return 0;
    }
    
    // Use periodGrades (saved from GradebookContent)
    if (gradebook.value.midterm.periodGrades && gradebook.value.midterm.periodGrades[studentId] !== undefined && gradebook.value.midterm.periodGrades[studentId] !== null) {
        console.log('[ClassRecord] Found midterm periodGrades for student', studentId, ':', gradebook.value.midterm.periodGrades[studentId]);
        return parseFloat(gradebook.value.midterm.periodGrades[studentId]);
    }
    
    console.log('[ClassRecord] No periodGrades found for student', studentId);
    return 0;
};

const getFinalsGrade = (studentId) => {
    console.log('[ClassRecord] getFinalsGrade for student', studentId, 'gradebook:', gradebook.value);
    
    if (!gradebook.value || !gradebook.value.finals) {
        console.log('[ClassRecord] No gradebook or finals data');
        return 0;
    }
    
    // Use periodGrades (saved from GradebookContent)
    if (gradebook.value.finals.periodGrades && gradebook.value.finals.periodGrades[studentId] !== undefined && gradebook.value.finals.periodGrades[studentId] !== null) {
        console.log('[ClassRecord] Found finals periodGrades for student', studentId, ':', gradebook.value.finals.periodGrades[studentId]);
        return parseFloat(gradebook.value.finals.periodGrades[studentId]);
    }
    
    console.log('[ClassRecord] No periodGrades found for student', studentId);
    return 0;
};

const getFinalGrade = (studentId) => {
    const midterm = getMidtermGrade(studentId);
    const finals = getFinalsGrade(studentId);
    // Get percentages from gradebook or use default 50/50
    const gradebook = props.course.gradebook;
    const midtermPercentage = gradebook?.midtermPercentage || 50;
    const finalsPercentage = gradebook?.finalsPercentage || 50;
    return (midterm * (midtermPercentage / 100)) + (finals * (finalsPercentage / 100));
};

// Convert percentage grade to Philippine grading scale (1.0 - 5.0)
const convertToGradingScale = (percentGrade) => {
    if (!percentGrade || percentGrade === 0 || percentGrade === '-') return '-';
    
    const grade = parseFloat(percentGrade);
    
    // Philippine Grading Scale based on the provided table
    if (grade >= 100) return 1.0;
    if (grade >= 99) return 1.15;
    if (grade >= 98) return 1.2;
    if (grade >= 97) return 1.25;
    if (grade >= 96) return 1.3;
    if (grade >= 95) return 1.35;
    if (grade >= 94) return 1.4;
    if (grade >= 93) return 1.45;
    if (grade >= 92) return 1.5;
    if (grade >= 91) return 1.55;
    if (grade >= 90) return 1.6;
    if (grade >= 89) return 1.65;
    if (grade >= 88) return 1.7;
    if (grade >= 87) return 1.75;
    if (grade >= 86) return 1.8;
    if (grade >= 85) return 1.85;
    if (grade >= 84) return 1.9;
    if (grade >= 83) return 1.95;
    if (grade >= 82) return 2.0;
    if (grade >= 81) return 2.05;
    if (grade >= 80) return 2.1;
    if (grade >= 79) return 2.15;
    if (grade >= 78) return 2.2;
    if (grade >= 77) return 2.25;
    if (grade >= 76) return 2.3;
    if (grade >= 75) return 2.35;
    if (grade >= 74) return 2.4;
    if (grade >= 73) return 2.45;
    if (grade >= 72) return 2.5;
    if (grade >= 71) return 2.55;
    if (grade >= 70) return 2.6;
    if (grade >= 69) return 2.65;
    if (grade >= 68) return 2.7;
    if (grade >= 67) return 2.75;
    if (grade >= 66) return 2.8;
    if (grade >= 65) return 2.85;
    if (grade >= 64) return 2.9;
    if (grade >= 63) return 2.95;
    if (grade >= 62) return 3.0;
    
    // Below 62% = 5.0 (Failed)
    return 5.0;
};

// Get the final converted grade (average of midterm and finals in 1.0-5.0 scale)
const getFinalConvertedGrade = (studentId) => {
    const midtermConverted = convertToGradingScale(getMidtermGrade(studentId));
    const finalsConverted = convertToGradingScale(getFinalsGrade(studentId));
    
    // If either grade is missing, return '-'
    if (midtermConverted === '-' || finalsConverted === '-') {
        if (midtermConverted !== '-') return midtermConverted;
        if (finalsConverted !== '-') return finalsConverted;
        return '-';
    }
    
    // Get percentages from gradebook or use default 50/50
    const gradebook = props.course.gradebook;
    const midtermPercentage = gradebook?.midtermPercentage || 50;
    const finalsPercentage = gradebook?.finalsPercentage || 50;
    
    return (midtermConverted * (midtermPercentage / 100)) + (finalsConverted * (finalsPercentage / 100));
};

const getClassRecordRemark = (finalGrade) => {
    if (finalGrade === '-' || finalGrade === undefined || finalGrade === null) return 'No Grade';
    const grade = parseFloat(finalGrade);
    if (grade <= passingGrade.value) return 'PASSED';
    return 'FAILED/RETAKE';
};

const getClassRecordRemarkClass = (finalGrade) => {
    if (finalGrade === '-' || finalGrade === undefined || finalGrade === null) return 'text-gray-500';
    const grade = parseFloat(finalGrade);
    if (grade <= passingGrade.value) return 'text-green-600';
    return 'text-red-600';
};

const printClassRecord = () => {
    window.print();
};

// Export Functions for Class Record
const exportFinalGrades = () => {
    window.open(route('teacher.courses.export-final-grades', props.course.id), '_blank');
};

const exportCoursePerformance = () => {
    window.open(route('teacher.courses.export-course-performance', props.course.id), '_blank');
};

const exportClassStandings = () => {
    window.open(route('teacher.courses.export-class-standings', props.course.id), '_blank');
};

// ===== Offline prefetch of attachments for the whole course =====
const prefetchAllCourseAttachments = async () => {
    try {
        if (!navigator.onLine) return;
        const urls = [];
    (props.classwork || []).forEach(cw => {
            (cw.attachments || []).forEach(att => {
                if (att?.path) urls.push(`/storage/${att.path}`);
                else if (att?.url) urls.push(att.url);
            });
        });
        const unique = Array.from(new Set(urls));
        if (unique.length === 0) return;
        const checks = await Promise.all(unique.map(u => isFileCached(u)));
        const toDownload = unique.filter((u, i) => !checks[i]);
        if (toDownload.length > 0) {
            await downloadFiles(toDownload, props.course?.id || null);
        }
    } catch (e) {
        console.warn('Prefetch course attachments (teacher) failed:', e);
    }
};

const onClassworksUpdated = () => prefetchAllCourseAttachments();

onMounted(() => {
    window.addEventListener('app:classworks-updated', onClassworksUpdated);
    prefetchAllCourseAttachments();
});

onBeforeUnmount(() => {
    window.removeEventListener('app:classworks-updated', onClassworksUpdated);
});

watch(() => props.classwork, () => {
    prefetchAllCourseAttachments();
});
</script>

<template>
    <Head :title="`${course.title} - ${course.section}`" />

    <TeacherLayout>
        <div class="space-y-6 overflow-x-hidden">
            <!-- Course Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold break-words">Course: {{ course.title }} - {{ course.section }}</h1>
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
                            @click="activeTab = tab.id"
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
                    <!-- Classwork Tab -->
                    <div v-if="activeTab === 'classwork'">
                        <!-- Latest Materials Section (Full width) -->
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <!-- Header with Create Button and Filter -->
                            <div class="bg-gradient-to-r from-red-900 to-red-800 px-4 sm:px-6 py-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h2 class="text-2xl font-bold text-white">Course Materials</h2>
                                        <p class="text-red-100 text-sm mt-1">Manage assignments, quizzes, and course content</p>
                                    </div>
                                    <button 
                                        @click="openClassworkModal"
                                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-3 bg-white text-red-900 rounded-lg hover:bg-gray-50 transition font-semibold shadow-lg transform hover:scale-105"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Material
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Bar -->
                            <div class="bg-gray-50 px-4 sm:px-6 py-3 border-b border-gray-200">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700">Filter by:</span>
                                    <select 
                                        v-model="materialFilter"
                                        class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-red-900 text-sm bg-white"
                                    >
                                        <option value="all">All Materials</option>
                                        <option value="materials">📚 Materials</option>
                                        <option value="tasks">📝 Tasks</option>
                                        <option value="quizzes">❓ Quizzes</option>
                                        <option value="activities">🎯 Activities</option>
                                        <option value="essays">📄 Essays</option>
                                        <option value="projects">🚀 Projects</option>
                                    </select>
                                    <div class="sm:ml-auto text-sm text-gray-600">
                                        <span class="font-semibold">{{ filteredMaterials.length + filteredAnnouncements.length }}</span> items
                                    </div>
                                </div>
                            </div>

                            <!-- Materials Content -->
                            <div class="p-6">

                                    <!-- Materials List -->
                                    <div v-if="filteredMaterials.length === 0 && filteredAnnouncements.length === 0" class="text-center py-12 text-gray-500">
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-lg">No materials found</p>
                                        <p class="text-sm mt-2">Try changing the filter or add materials using the "To Do" section</p>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <!-- Announcements -->
                                        <div
                                            v-for="announcement in filteredAnnouncements"
                                            :key="'announcement-' + announcement.id"
                                            class="bg-white rounded-lg p-4 border-l-4 shadow hover:shadow-md transition"
                                            :style="{ borderLeftColor: announcement.color || '#800000' }"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                                        </svg>
                                                        <h3 class="font-semibold text-gray-900 break-words">{{ announcement.title }}</h3>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded uppercase bg-blue-100 text-blue-800">
                                                            Announcement
                                                        </span>
                                                        <span v-if="announcement.target_audience === 'all_courses'" class="px-2 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-800">
                                                            All Courses
                                                        </span>
                                                    </div>
                                                    <p v-if="announcement.description" class="text-sm text-gray-600 mt-2 break-words">{{ announcement.description }}</p>
                                                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            {{ announcement.author }}
                                                        </span>
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ announcement.date }}
                                                        </span>
                                                        <span v-if="announcement.time" class="flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            {{ announcement.time }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Regular Materials -->
                                        <div
                                            v-for="item in filteredMaterials"
                                            :key="item.id"
                                            class="bg-white rounded-lg p-4 border-l-4 shadow hover:shadow-md transition"
                                            :style="{ borderLeftColor: item.color_code || getTypeColor(item.type) }"
                                        >
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="font-semibold text-gray-900 break-words">{{ item.title }}</h3>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded uppercase" :style="{ backgroundColor: item.color_code + '20', color: item.color_code }">
                                                            {{ item.type }}
                                                        </span>
                                                    </div>
                                                    <p v-if="item.description" class="text-sm text-gray-600 mt-1 break-words">{{ item.description }}</p>
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
                                                <div class="w-full sm:w-auto flex gap-2 mt-3 sm:mt-0 sm:self-start justify-end sm:justify-normal">
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
                        </div>
                        
                        <!-- Grade Access Requests Section -->
                        <div v-if="gradeAccessRequests.length > 0" class="mb-8">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <h3 class="text-lg font-bold text-yellow-900">
                                        Grade Access Requests
                                        <span class="ml-2 px-2 py-1 bg-yellow-600 text-white text-sm rounded-full">{{ gradeAccessRequests.length }}</span>
                                    </h3>
                                </div>
                                
                                <div class="space-y-3">
                                    <div 
                                        v-for="student in gradeAccessRequests" 
                                        :key="student.id"
                                        class="bg-white rounded-lg p-4 border border-yellow-300 flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-yellow-600 rounded-full flex items-center justify-center text-white font-bold">
                                                {{ student.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ student.name }}</p>
                                                <p class="text-sm text-gray-600">{{ student.email }}</p>
                                                <p class="text-xs text-gray-500">Requested: {{ student.grade_access_requested_at }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                @click="grantAccess(student.id)"
                                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition"
                                            >
                                                Grant Access
                                            </button>
                                            <button
                                                @click="denyRequest(student.id)"
                                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg text-sm font-medium transition"
                                            >
                                                Deny
                                            </button>
                                        </div>
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
                                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold overflow-hidden flex-shrink-0 relative">
                                            <img v-if="teacher.profile_picture" 
                                                 :src="teacher.profile_picture" 
                                                 :alt="teacher.name"
                                                 class="w-full h-full object-cover absolute inset-0"
                                            />
                                            <span v-if="!teacher.profile_picture" class="z-10">
                                                {{ teacher.name.charAt(0) }}
                                            </span>
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
                                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-lg overflow-hidden flex-shrink-0 relative">
                                                <img v-if="student.profile_picture" 
                                                     :src="student.profile_picture" 
                                                     :alt="student.name"
                                                     class="w-full h-full object-cover absolute inset-0"
                                                />
                                                <span v-if="!student.profile_picture" class="z-10">
                                                    {{ student.name.charAt(0) }}
                                                </span>
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
                                            <span class="font-semibold text-gray-900">{{ Math.min(student.progress.completion_rate, 100) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div 
                                                class="bg-blue-600 h-2 rounded-full transition-all" 
                                                :style="{ width: Math.min(student.progress.completion_rate, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-blue-50 rounded px-2 py-1 text-center">
                                                <div class="text-gray-600 text-[10px] uppercase">Submitted</div>
                                                <div class="font-bold text-blue-700 text-base">{{ student.progress.submitted }}<span class="text-gray-400 text-xs">/{{ student.progress.total_classwork }}</span></div>
                                            </div>
                                            <div class="bg-green-50 rounded px-2 py-1 text-center">
                                                <div class="text-gray-600 text-[10px] uppercase">Graded</div>
                                                <div class="font-bold text-green-700 text-base">{{ student.progress.graded }}<span class="text-gray-400 text-xs">/{{ student.progress.total_classwork }}</span></div>
                                            </div>
                                            <div class="bg-yellow-50 rounded px-2 py-1 text-center">
                                                <div class="text-gray-600 text-[10px] uppercase">Pending</div>
                                                <div class="font-bold text-yellow-700 text-base">{{ student.progress.pending || 0 }}</div>
                                            </div>
                                            <div class="bg-red-50 rounded px-2 py-1 text-center">
                                                <div class="text-gray-600 text-[10px] uppercase">Missing</div>
                                                <div class="font-bold text-red-700 text-base">{{ student.progress.not_submitted || 0 }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- View Performance Button -->
                                    <button 
                                        @click="viewStudentPerformance(student)"
                                        class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2 mb-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        View Performance
                                    </button>
                                    
                                    <!-- Grade Access Control -->
                                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span class="text-xs font-medium text-gray-700">Grade Visibility</span>
                                        </div>
                                        <button
                                            @click="student.grade_access_granted ? revokeAccess(student.id) : grantAccess(student.id)"
                                            :class="[
                                                'px-3 py-1 rounded text-xs font-semibold transition',
                                                student.grade_access_granted
                                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                                    : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                            ]"
                                        >
                                            {{ student.grade_access_granted ? 'Visible' : 'Hidden' }}
                                        </button>
                                    </div>
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
                            :gradebook="gradebook.value"
                            @gradebook-updated="(updatedGradebook) => gradebook.value = updatedGradebook"
                        />
                    </div>

                    <!-- Class Record Tab -->
                    <div v-if="activeTab === 'class-record'" class="space-y-6">
                        <!-- Header Section -->
                        <div :class="['bg-white rounded-lg shadow-md p-6', isDoctorate ? 'border-t-4 border-red-600' : 'border-t-4 border-blue-600']">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">Class Record</h2>
                                    <p class="text-gray-600 mt-1">{{ course.title }} - {{ course.section }}</p>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-700">Program Type:</span>

                                        <!-- Accessible toggle switch: Masteral <-> Doctorate -->
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm text-gray-600">Masteral</span>

                                            <button
                                                type="button"
                                                role="switch"
                                                :aria-checked="isDoctorate"
                                                @click="isDoctorate = !isDoctorate"
                                                class="relative inline-flex items-center h-6 w-12 rounded-full transition-colors focus:outline-none"
                                                :class="isDoctorate ? 'bg-red-600' : 'bg-blue-600'"
                                                :title="isDoctorate ? 'Doctorate' : 'Masteral'"
                                            >
                                                <span
                                                    class="inline-block bg-white w-5 h-5 rounded-full shadow transform transition-transform"
                                                    :class="isDoctorate ? 'translate-x-6' : 'translate-x-1'"
                                                />
                                            </button>

                                            <span class="text-sm text-gray-600">Doctorate</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Simple 5-Column Class Record Table -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr :class="isDoctorate ? 'bg-gradient-to-r from-red-700 to-red-600 text-white' : 'bg-gradient-to-r from-blue-600 to-blue-700 text-white'">
                                            <th :class="isDoctorate ? 'px-6 py-4 text-left border border-red-500 font-bold' : 'px-6 py-4 text-left border border-blue-500 font-bold'">
                                                Student Name
                                            </th>
                                            <th :class="isDoctorate ? 'px-6 py-4 text-center border border-red-500 font-bold' : 'px-6 py-4 text-center border border-blue-500 font-bold'">
                                                Program
                                            </th>
                                            <th :class="isDoctorate ? 'px-6 py-4 text-center border border-red-500 font-bold' : 'px-6 py-4 text-center border border-blue-500 font-bold'">
                                                Midterm Grade
                                            </th>
                                            <th :class="isDoctorate ? 'px-6 py-4 text-center border border-red-500 font-bold' : 'px-6 py-4 text-center border border-blue-500 font-bold'">
                                                Final Grade
                                            </th>
                                            <th :class="isDoctorate ? 'px-6 py-4 text-center border border-red-500 font-bold' : 'px-6 py-4 text-center border border-blue-500 font-bold'">
                                                Remarks
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="student in course.students" 
                                            :key="student.id"
                                            class="hover:bg-gray-50 border-b"
                                        >
                                            <!-- Student Name -->
                                            <td class="px-6 py-4 text-left font-medium text-gray-900 border border-gray-300">
                                                {{ student.name }}
                                            </td>
                                            
                                            <!-- Program -->
                                            <td class="px-6 py-4 text-center text-gray-700 border border-gray-300">
                                                {{ student.program || course.program?.name || 'N/A' }}
                                            </td>
                                            
                                            <!-- Midterm Grade -->
                                            <td class="px-6 py-4 text-center font-semibold border border-gray-300"
                                                :class="convertToGradingScale(getMidtermGrade(student.id)) !== '-' && convertToGradingScale(getMidtermGrade(student.id)) <= passingGrade ? 'text-green-600' : convertToGradingScale(getMidtermGrade(student.id)) === '-' ? 'text-gray-500' : 'text-red-600'">
                                                {{ convertToGradingScale(getMidtermGrade(student.id)) !== '-' ? convertToGradingScale(getMidtermGrade(student.id)).toFixed(2) : '-' }}
                                            </td>
                                            
                                            <!-- Final Grade -->
                                            <td class="px-6 py-4 text-center font-semibold border border-gray-300"
                                                :class="convertToGradingScale(getFinalsGrade(student.id)) !== '-' && convertToGradingScale(getFinalsGrade(student.id)) <= passingGrade ? 'text-green-600' : convertToGradingScale(getFinalsGrade(student.id)) === '-' ? 'text-gray-500' : 'text-red-600'">
                                                {{ convertToGradingScale(getFinalsGrade(student.id)) !== '-' ? convertToGradingScale(getFinalsGrade(student.id)).toFixed(2) : '-' }}
                                            </td>
                                            
                                            <!-- Remarks -->
                                            <td class="px-6 py-4 text-center font-bold border border-gray-300"
                                                :class="getClassRecordRemarkClass(getFinalConvertedGrade(student.id))">
                                                {{ getClassRecordRemark(getFinalConvertedGrade(student.id)) }}
                                            </td>
                                        </tr>
                                        
                                        <!-- Empty State -->
                                        <tr v-if="course.students.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                <p class="text-lg font-medium">No students enrolled</p>
                                                <p class="text-sm text-gray-400 mt-1">Students will appear here once they join the course</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Export Options -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Export Class Record
                                    </h3>
                                    <p class="text-sm text-gray-600">Download reports in PDF format</p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        @click="exportFinalGrades"
                                        class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-sm transition font-medium"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Final Grades PDF
                                    </button>
                                    <button
                                        type="button"
                                        disabled
                                        aria-disabled="true"
                                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm transition font-medium opacity-50 cursor-not-allowed pointer-events-none"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Course Performance PDF
                                    </button>
                                    <button
                                        type="button"
                                        disabled
                                        aria-disabled="true"
                                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg shadow-sm transition font-medium opacity-50 cursor-not-allowed pointer-events-none"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                        </svg>
                                        Class Standings PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Grading Scale</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <span class="text-sm">{{ passingGrade }} and below: Passed</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span class="text-sm">Above {{ passingGrade }}: Failed/Retake</span>
                                </div>
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
                                                        <label class="text-sm font-medium text-gray-700">Options:</label>
                                                        <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex gap-2 items-center">
                                                            <span class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-700 font-semibold rounded-full text-sm flex-shrink-0">
                                                                {{ question.option_labels?.[oIndex] || String.fromCharCode(65 + oIndex) }}
                                                            </span>
                                                            <input 
                                                                v-model="question.options[oIndex]" 
                                                                type="text" 
                                                                :placeholder="`Enter option ${question.option_labels?.[oIndex] || String.fromCharCode(65 + oIndex)}`" 
                                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" 
                                                            />
                                                            <button 
                                                                v-if="question.options.length > 2" 
                                                                type="button" 
                                                                @click="removeOption(qIndex, oIndex)" 
                                                                class="text-red-600 hover:text-red-800 p-1"
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <button 
                                                            type="button" 
                                                            @click="addOption(qIndex)" 
                                                            class="text-sm text-red-600 hover:text-red-800 font-medium flex items-center gap-1"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            Add Option
                                                        </button>
                                                        
                                                        <div class="pt-2">
                                                            <label class="text-sm font-medium text-green-700 mb-2 block">Correct Answer:</label>
                                                            <select 
                                                                v-model="question.correct_answer" 
                                                                class="w-full px-3 py-2 text-sm border-2 border-green-300 rounded-lg bg-green-50 focus:ring-2 focus:ring-green-500 font-medium"
                                                            >
                                                                <option value="">Select correct answer</option>
                                                                <option 
                                                                    v-for="(option, oIndex) in question.options" 
                                                                    :key="oIndex" 
                                                                    :value="question.option_labels?.[oIndex] || String.fromCharCode(65 + oIndex)"
                                                                >
                                                                    {{ question.option_labels?.[oIndex] || String.fromCharCode(65 + oIndex) }} - {{ option || '(empty)' }}
                                                                </option>
                                                            </select>
                                                        </div>
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

                                            <!-- Show Correct Answers Toggle -->
                                            <div class="mt-4 p-3 bg-white border border-green-300 rounded-lg">
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        v-model="classworkForm.show_correct_answers"
                                                        class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                                    />
                                                    <div class="flex-1">
                                                        <span class="text-sm font-medium text-gray-900">Show Correct Answers to Students</span>
                                                        <p class="text-xs text-gray-500">Students can view correct answers after the quiz is graded</p>
                                                    </div>
                                                </label>
                                            </div>
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

                                        <!-- Gradebook Integration (not for lessons) -->
                                        <div v-if="classworkForm.type !== 'lesson'" class="border-2 border-blue-200 rounded-lg p-4 bg-blue-50 space-y-3">
                                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                </svg>
                                                Grade Book Integration (Optional)
                                            </h4>
                                            <p class="text-xs text-gray-600">Link this task to your gradebook to automatically record student scores.</p>

                                            <div class="grid grid-cols-2 gap-3">
                                                <!-- Grading Period -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Grading Period
                                                    </label>
                                                    <select
                                                        v-model="classworkForm.grading_period"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm"
                                                    >
                                                        <option value="">Select Period</option>
                                                        <option v-for="period in availableGradingPeriods" :key="period.value" :value="period.value">
                                                            {{ period.label }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Grade Table -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Grade Table
                                                    </label>
                                                    <select
                                                        v-model="classworkForm.grade_table_name"
                                                        :disabled="!classworkForm.grading_period"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm disabled:bg-gray-100"
                                                    >
                                                        <option value="">Select Table</option>
                                                        <option v-for="table in availableGradeTables" :key="table.value" :value="table.value">
                                                            {{ table.label }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Main Column -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Main Column
                                                    </label>
                                                    <select
                                                        v-model="classworkForm.grade_main_column"
                                                        :disabled="!classworkForm.grade_table_name"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm disabled:bg-gray-100"
                                                    >
                                                        <option value="">Select Column</option>
                                                        <option v-for="column in availableMainColumns" :key="column.value" :value="column.value">
                                                            {{ column.label }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Sub Column (Auto-generated) -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Sub Column Name
                                                    </label>
                                                    <input
                                                        v-model="classworkForm.grade_sub_column"
                                                        type="text"
                                                        :disabled="!classworkForm.grade_main_column"
                                                        placeholder="Auto-generated from title"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm disabled:bg-gray-100"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- File Attachments (not for quizzes) -->
                                        <div v-if="classworkForm.type !== 'quiz'">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Attachments
                                                <span class="text-xs text-gray-500 font-normal ml-1">(Max 10MB per file)</span>
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
                                                            <span v-if="file.path" class="text-xs text-green-600">(Existing)</span>
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

                                        <!-- Has Submission (Hidden for lessons and quizzes) -->
                                        <div v-if="classworkForm.type !== 'lesson' && classworkForm.type !== 'quiz'" class="flex items-center">
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
                                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-2xl overflow-hidden flex-shrink-0 relative">
                                    <img v-if="selectedStudent.profile_picture" 
                                         :src="selectedStudent.profile_picture" 
                                         :alt="selectedStudent.name"
                                         class="w-full h-full object-cover absolute inset-0"
                                    />
                                    <span v-if="!selectedStudent.profile_picture" class="z-10">
                                        {{ selectedStudent.name.charAt(0) }}
                                    </span>
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
                                <div class="text-3xl font-bold text-blue-600">{{ Math.min(selectedStudent.progress.completion_rate, 100) }}%</div>
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
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div 
                                            class="h-2 rounded-full transition-all"
                                            :class="submission.percentage >= 90 ? 'bg-green-500' : submission.percentage >= 75 ? 'bg-blue-500' : submission.percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                                            :style="{ width: Math.min(submission.percentage, 100) + '%' }"
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
                                    <span class="font-semibold text-yellow-600">{{ Math.max(0, selectedStudent.progress.submitted - selectedStudent.progress.graded) }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                    <span class="text-sm font-semibold text-gray-900">Not Submitted</span>
                                    <span class="font-bold text-red-600">{{ Math.max(0, selectedStudent.progress.total_classwork - selectedStudent.progress.submitted) }}</span>
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
                    <div v-else-if="fileBlob && isImage(previewFile?.name)" class="flex justify-center bg-gray-50 p-4 rounded">
                        <img :src="fileBlob" :alt="previewFile?.name" class="max-w-full max-h-[70vh] h-auto rounded shadow-lg">
                    </div>
                    
                    <!-- PDF Preview -->
                    <div v-else-if="fileBlob && isPDF(previewFile?.name)" class="w-full h-[70vh]">
                        <iframe :src="fileBlob" class="w-full h-full border rounded"></iframe>
                    </div>
                    
                    <!-- Office Documents Preview (Word, Excel, PowerPoint) -->
                    <div v-else-if="fileBlob && isOfficeDocument(previewFile?.name)" class="w-full h-[70vh]">
                        <iframe 
                            :src="getOfficeViewerUrl(previewFile?.path)" 
                            class="w-full h-full border rounded"
                            frameborder="0"
                        >
                            Your browser does not support iframe preview.
                        </iframe>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            If preview doesn't load, <a :href="fileBlob" :download="previewFile?.name" class="text-blue-600 hover:underline">download the file</a>
                        </p>
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
