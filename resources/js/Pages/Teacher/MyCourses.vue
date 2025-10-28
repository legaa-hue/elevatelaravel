<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import axios from 'axios';

const props = defineProps({
    courses: {
        type: Array,
        required: true
    },
    programs: {
        type: Array,
        default: () => []
    },
    academicYears: {
        type: Array,
        default: () => []
    }
});

const showCreateModal = ref(false);
const selectedProgram = ref('');
const courseTemplates = ref([]);
const loadingTemplates = ref(false);

const formData = ref({
    academic_year_id: '',
    program_id: '',
    course_template_id: '',
    title: '',
    section: '',
    description: '',
    units: 3
});

watch(selectedProgram, async (newProgramId) => {
    if (newProgramId) {
        loadingTemplates.value = true;
        try {
            const response = await axios.get(route('teacher.programs.course-templates', newProgramId));
            courseTemplates.value = response.data;
            formData.value.program_id = newProgramId;
            formData.value.course_template_id = '';
            formData.value.title = '';
            formData.value.units = 3;
        } catch (error) {
            console.error('Error loading course templates:', error);
            courseTemplates.value = [];
        } finally {
            loadingTemplates.value = false;
        }
    } else {
        courseTemplates.value = [];
        formData.value.program_id = '';
        formData.value.course_template_id = '';
    }
});

const selectedTemplate = computed(() => {
    if (!formData.value.course_template_id) return null;
    return courseTemplates.value.find(t => t.id === parseInt(formData.value.course_template_id));
});

watch(selectedTemplate, (template) => {
    if (template) {
        formData.value.title = template.course_name;
        formData.value.units = template.units;
    }
});

const openCreateModal = () => {
    formData.value = {
        academic_year_id: '',
        program_id: '',
        course_template_id: '',
        title: '',
        section: '',
        description: '',
        units: 3
    };
    selectedProgram.value = '';
    courseTemplates.value = [];
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
};

const createCourse = () => {
    router.post(route('teacher.courses.store'), {
        academic_year_id: formData.value.academic_year_id,
        program_id: formData.value.program_id,
        course_template_id: formData.value.course_template_id,
        section: formData.value.section,
        description: formData.value.description
    }, {
        onSuccess: () => {
            closeModal();
        }
    });
};

const deleteCourse = (courseId) => {
    if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        router.delete(route('teacher.courses.destroy', courseId));
    }
};
</script>

<template>
    <Head title="My Courses" />
    <TeacherLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your courses and track student progress</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition-colors flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Course</span>
                </button>
            </div>

            <!-- Courses Grid -->
            <div v-if="courses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="course in courses"
                    :key="course.id"
                    class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900">{{ course.title }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ course.section }}</p>
                                <p v-if="course.academic_year" class="text-xs text-gray-500 mt-1">
                                    {{ course.academic_year.year_name }}
                                </p>
                            </div>
                            <span 
                                class="px-2 py-1 text-xs font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': course.status === 'active',
                                    'bg-red-100 text-red-800': course.status === 'archived'
                                }"
                            >
                                {{ course.status }}
                            </span>
                        </div>
                        
                        <p v-if="course.description" class="text-sm text-gray-600 mb-4 line-clamp-2">
                            {{ course.description }}
                        </p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ course.students_count || 0 }} Students
                            </span>
                            <span class="font-mono text-xs">{{ course.join_code }}</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <a
                                :href="`/teacher/courses/${course.id}`"
                                class="flex-1 px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg text-sm font-medium text-center transition"
                            >
                                Open Course
                            </a>
                            <button
                                @click="deleteCourse(course.id)"
                                class="px-3 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition"
                                title="Delete Course"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No courses yet</h3>
                <p class="mt-2 text-sm text-gray-500">Get started by creating your first course.</p>
                <button 
                    @click="openCreateModal"
                    class="mt-6 px-6 py-3 bg-red-900 text-white rounded-lg hover:bg-red-800 transition-colors"
                >
                    Create Your First Course
                </button>
            </div>
        </div>

        <!-- Create Course Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Create New Course</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Academic Year *
                        </label>
                        <select 
                            v-model="formData.academic_year_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        >
                            <option value="">Select Academic Year</option>
                            <option 
                                v-for="year in academicYears.filter(y => y.status === 'Active')" 
                                :key="year.id" 
                                :value="year.id"
                            >
                                {{ year.year_name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Program *
                        </label>
                        <select 
                            v-model="selectedProgram"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        >
                            <option value="">Select Program</option>
                            <option 
                                v-for="program in programs.filter(p => p.status === 'Active')" 
                                :key="program.id" 
                                :value="program.id"
                            >
                                {{ program.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="selectedProgram">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Course Template *
                        </label>
                        <select 
                            v-model="formData.course_template_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            :disabled="loadingTemplates"
                        >
                            <option value="">{{ loadingTemplates ? 'Loading...' : 'Select Course Template' }}</option>
                            <option 
                                v-for="template in courseTemplates" 
                                :key="template.id" 
                                :value="template.id"
                            >
                                {{ template.course_code }} - {{ template.course_name }} ({{ template.units }} units) - {{ template.course_type }}
                            </option>
                        </select>
                    </div>

                    <div v-if="formData.course_template_id">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Course Title (Auto-filled)
                        </label>
                        <input 
                            v-model="formData.title"
                            type="text"
                            disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                        />
                    </div>

                    <div v-if="formData.course_template_id">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Units (Auto-filled)
                        </label>
                        <input 
                            v-model.number="formData.units"
                            type="number"
                            disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Section *
                        </label>
                        <input 
                            v-model="formData.section"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="e.g., Section A, BSIT-101"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea 
                            v-model="formData.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Brief description of the course"
                        ></textarea>
                    </div>
                </div>
                <div class="sticky bottom-0 bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button 
                        @click="closeModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="createCourse"
                        class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800"
                        :disabled="!formData.academic_year_id || !formData.program_id || !formData.course_template_id || !formData.section"
                    >
                        Create Course
                    </button>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
