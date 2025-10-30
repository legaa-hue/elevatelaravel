<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InfoTooltip from '@/Components/InfoTooltip.vue';
import axios from 'axios';

const props = defineProps({
    academicYears: Array,
    activeYear: Object,
    programs: {
        type: Array,
        default: () => []
    },
    courseTemplates: {
        type: Array,
        default: () => []
    }
});

// Tab management
const activeTab = ref('academic-years');

// Forms
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showVersionHistoryModal = ref(false);
const showYearSelectorModal = ref(false);
const selectedYear = ref(null);
const versionHistory = ref([]);
const visibleYearsCount = ref(5); // Initially show 5 years

// Program forms
const showProgramModal = ref(false);
const isEditingProgram = ref(false);
const programFormData = ref({
    id: null,
    name: '',
    description: '',
    status: 'Active'
});
const showDeleteProgramConfirm = ref(false);
const programToDelete = ref(null);

// Course Template forms
const showTemplateModal = ref(false);
const isEditingTemplate = ref(false);
const selectedProgramForTemplate = ref('');
const loadingTemplates = ref(false);
const templateFormData = ref({
    id: null,
    program_id: '',
    course_code: '',
    course_name: '',
    course_type: 'Major Course',
    units: 3,
    status: 'Active'
});
const showDeleteTemplateConfirm = ref(false);
const templateToDelete = ref(null);

const createForm = useForm({
    year_name: '',
    file: null,
    status: 'Inactive',
    notes: '',
});

const editForm = useForm({
    notes: '',
});

// Generate year options starting from current year
const generateYearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    
    // Generate years from current year onwards
    for (let i = 0; i < 20; i++) { // Generate 20 years total
        const startYear = currentYear + i;
        const endYear = startYear + 1;
        years.push(`${startYear}-${endYear}`);
    }
    
    return years;
});

// Get visible years based on visibleYearsCount
const visibleYears = computed(() => {
    return generateYearOptions.value.slice(0, visibleYearsCount.value);
});

// Check if there are more years to show
const hasMoreYears = computed(() => {
    return visibleYearsCount.value < generateYearOptions.value.length;
});

// Load more years
const loadMoreYears = () => {
    visibleYearsCount.value += 3;
};

// Select a year
const selectYear = (year) => {
    createForm.year_name = year;
    showYearSelectorModal.value = false;
    visibleYearsCount.value = 5; // Reset for next time
};

// Group years by year_name
const groupedYears = computed(() => {
    const groups = {};
    props.academicYears.forEach(year => {
        if (!groups[year.year_name]) {
            groups[year.year_name] = [];
        }
        groups[year.year_name].push(year);
    });
    return groups;
});

// Get latest version for each year
const latestYears = computed(() => {
    return Object.entries(groupedYears.value).map(([yearName, versions]) => {
        // Sort by created_at descending and get the first (latest)
        const sorted = [...versions].sort((a, b) => 
            new Date(b.created_at) - new Date(a.created_at)
        );
        return sorted[0];
    }).sort((a, b) => b.year_name.localeCompare(a.year_name));
});


const handleFileUpload = (event) => {
    createForm.file = event.target.files[0];
};

const submitCreate = () => {
    createForm.post(route('admin.academic-year.store'), {
        onSuccess: () => {
            createForm.reset();
            showCreateModal.value = false;
        },
        forceFormData: true,
    });
};

const toggleStatus = (year) => {
    router.patch(route('admin.academic-year.updateStatus', year.id), {
        status: year.status === 'Active' ? 'Inactive' : 'Active',
    });
};

const openEditModal = (year) => {
    selectedYear.value = year;
    editForm.notes = year.notes || '';
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.academic-year.update', selectedYear.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            selectedYear.value = null;
        },
    });
};

const showVersionHistory = (yearName) => {
    versionHistory.value = groupedYears.value[yearName] || [];
    showVersionHistoryModal.value = true;
};

const deleteYear = (year) => {
    if (confirm(`Are you sure you want to delete ${year.year_name} (${year.version})?`)) {
        router.delete(route('admin.academic-year.destroy', year.id));
    }
};

const downloadFile = (year) => {
    window.location.href = route('admin.academic-year.download', year.id);
};

const getStatusIcon = (status) => {
    return status === 'Active' ? '🟢' : '⚪';
};

const getStatusClass = (status) => {
    return status === 'Active' 
        ? 'bg-green-100 text-green-800' 
        : 'bg-gray-100 text-gray-600';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Program Management Functions
const openCreateProgramModal = () => {
    programFormData.value = {
        id: null,
        name: '',
        description: '',
        status: 'Active'
    };
    isEditingProgram.value = false;
    showProgramModal.value = true;
};

const openEditProgramModal = (program) => {
    programFormData.value = {
        id: program.id,
        name: program.name,
        description: program.description || '',
        status: program.status
    };
    isEditingProgram.value = true;
    showProgramModal.value = true;
};

const closeProgramModal = () => {
    showProgramModal.value = false;
};

const saveProgram = () => {
    if (isEditingProgram.value) {
        router.put(route('admin.programs.update', programFormData.value.id), {
            name: programFormData.value.name,
            description: programFormData.value.description,
            status: programFormData.value.status
        }, {
            onSuccess: () => {
                closeProgramModal();
            }
        });
    } else {
        router.post(route('admin.programs.store'), {
            name: programFormData.value.name,
            description: programFormData.value.description,
            status: programFormData.value.status
        }, {
            onSuccess: () => {
                closeProgramModal();
            }
        });
    }
};

const confirmDeleteProgram = (program) => {
    programToDelete.value = program;
    showDeleteProgramConfirm.value = true;
};

const deleteProgram = () => {
    if (programToDelete.value) {
        router.delete(route('admin.programs.destroy', programToDelete.value.id), {
            onSuccess: () => {
                showDeleteProgramConfirm.value = false;
                programToDelete.value = null;
            }
        });
    }
};

const cancelDeleteProgram = () => {
    showDeleteProgramConfirm.value = false;
    programToDelete.value = null;
};

// Course Template Management Functions
const openCreateTemplateModal = () => {
    templateFormData.value = {
        id: null,
        program_id: '',
        course_code: '',
        course_name: '',
        course_type: 'Major Course',
        units: 3,
        status: 'Active'
    };
    isEditingTemplate.value = false;
    showTemplateModal.value = true;
};

const openEditTemplateModal = (template) => {
    templateFormData.value = {
        id: template.id,
        program_id: template.program_id,
        course_code: template.course_code,
        course_name: template.course_name,
        course_type: template.course_type,
        units: template.units,
        status: template.status
    };
    isEditingTemplate.value = true;
    showTemplateModal.value = true;
};

const closeTemplateModal = () => {
    showTemplateModal.value = false;
};

const saveTemplate = () => {
    if (isEditingTemplate.value) {
        router.put(route('admin.course-templates.update', templateFormData.value.id), {
            program_id: templateFormData.value.program_id,
            course_code: templateFormData.value.course_code,
            course_name: templateFormData.value.course_name,
            course_type: templateFormData.value.course_type,
            units: templateFormData.value.units,
            status: templateFormData.value.status
        }, {
            onSuccess: () => {
                closeTemplateModal();
            }
        });
    } else {
        router.post(route('admin.course-templates.store'), {
            program_id: templateFormData.value.program_id,
            course_code: templateFormData.value.course_code,
            course_name: templateFormData.value.course_name,
            course_type: templateFormData.value.course_type,
            units: templateFormData.value.units,
            status: templateFormData.value.status
        }, {
            onSuccess: () => {
                closeTemplateModal();
            }
        });
    }
};

const confirmDeleteTemplate = (template) => {
    templateToDelete.value = template;
    showDeleteTemplateConfirm.value = true;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        router.delete(route('admin.course-templates.destroy', templateToDelete.value.id), {
            onSuccess: () => {
                showDeleteTemplateConfirm.value = false;
                templateToDelete.value = null;
            }
        });
    }
};

const cancelDeleteTemplate = () => {
    showDeleteTemplateConfirm.value = false;
    templateToDelete.value = null;
};
</script>

<template>
    <Head title="Academic Year Management" />
    
    <AdminLayout>
        <div class="p-4 md:p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Academic Year Management</h1>
                    <InfoTooltip 
                        title="Academic Year Management"
                        content="Manage academic years, programs, and course templates. Control which academic year is active, add/edit programs, and create course templates that can be used when setting up courses."
                        position="right"
                    />
                    <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600 ml-2">Manage academic years, programs, and course templates</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="activeTab === 'academic-years'"
                        @click="showCreateModal = true"
                        class="w-full sm:w-auto bg-red-900 hover:bg-red-800 hover:scale-105 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-medium transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-sm md:text-base">Create Academic Year</span>
                    </button>
                    <InfoTooltip 
                        v-if="activeTab === 'academic-years'"
                        title="Create Academic Year"
                        content="Add a new academic year to the system. You can specify the year name (e.g., 2024-2025) and upload configuration files if needed."
                        position="left"
                    />
                    <button
                        v-if="activeTab === 'programs'"
                        @click="openCreateProgramModal"
                        class="w-full sm:w-auto bg-red-900 hover:bg-red-800 hover:scale-105 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-medium transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-sm md:text-base">Add Program</span>
                    </button>
                    <InfoTooltip 
                        v-if="activeTab === 'programs'"
                        title="Add Program"
                        content="Create a new academic program (e.g., BS Computer Science, BS Information Technology). Programs group related courses together."
                        position="left"
                    />
                    <button
                        v-if="activeTab === 'course-templates'"
                        @click="openCreateTemplateModal"
                        class="w-full sm:w-auto bg-red-900 hover:bg-red-800 hover:scale-105 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-medium transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-sm md:text-base">Add Course Template</span>
                    </button>
                    <InfoTooltip 
                        v-if="activeTab === 'course-templates'"
                        title="Add Course Template"
                        content="Create a reusable course template with code, name, type, and units. Templates make it easier to create new course sections."
                        position="left"
                    />
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <div class="flex items-center gap-1">
                        <button
                            @click="activeTab = 'academic-years'"
                            :class="[
                                activeTab === 'academic-years'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Academic Years
                        </button>
                        <InfoTooltip 
                            title="Academic Years Tab"
                            content="View and manage all academic years. Set which year is currently active and control year transitions."
                            position="top"
                        />
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="activeTab = 'programs'"
                            :class="[
                                activeTab === 'programs'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Programs
                        </button>
                        <InfoTooltip 
                            title="Programs Tab"
                            content="Manage academic programs (e.g., BS Computer Science, BS IT). Add, edit, or deactivate programs."
                            position="top"
                        />
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="activeTab = 'course-templates'"
                            :class="[
                                activeTab === 'course-templates'
                                    ? 'border-red-900 text-red-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Course Templates
                        </button>
                        <InfoTooltip 
                            title="Course Templates Tab"
                            content="Manage reusable course templates with predefined course codes, names, types, and units for easier course creation."
                            position="top"
                        />
                    </div>
                </nav>
            </div>

            <!-- Academic Years Tab Content -->
            <div v-show="activeTab === 'academic-years'">
            <div v-if="activeYear" class="bg-green-50 border-l-4 border-green-500 p-3 md:p-4 rounded-lg">
                <div class="flex items-center">
                    <span class="text-xl md:text-2xl mr-2 md:mr-3">🟢</span>
                    <div>
                        <h3 class="text-base md:text-lg font-semibold text-green-900">Current Active Academic Year</h3>
                        <p class="text-sm md:text-base text-green-700">
                            <span class="font-bold">{{ activeYear.year_name }}</span> 
                            ({{ activeYear.version }})
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="bg-yellow-50 border-l-4 border-yellow-500 p-3 md:p-4 rounded-lg">
                <div class="flex items-center">
                    <span class="text-xl md:text-2xl mr-2 md:mr-3">⚠️</span>
                    <div>
                        <h3 class="text-base md:text-lg font-semibold text-yellow-900">No Active Academic Year</h3>
                        <p class="text-sm md:text-base text-yellow-700">Please activate an academic year to enable course creation</p>
                    </div>
                </div>
            </div>

            <!-- Academic Years List - Mobile Cards (visible on mobile only) -->
            <div class="md:hidden space-y-4">
                <div v-for="year in latestYears" :key="year.id" class="bg-white rounded-lg shadow-md p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ year.year_name }}</h3>
                            <button
                                v-if="groupedYears[year.year_name].length > 1"
                                @click="showVersionHistory(year.year_name)"
                                class="text-xs text-blue-600 hover:text-blue-800 mt-1"
                            >
                                View {{ groupedYears[year.year_name].length }} versions
                            </button>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                            {{ year.version }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 font-medium">Status:</span>
                        <button
                            @click="toggleStatus(year)"
                            :class="getStatusClass(year.status)"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium cursor-pointer hover:opacity-80 transition"
                        >
                            <span class="mr-1">{{ getStatusIcon(year.status) }}</span>
                            {{ year.status }}
                        </button>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 font-medium">Uploaded By:</span>
                            <span class="text-gray-900">{{ year.uploader ? year.uploader.first_name + ' ' + year.uploader.last_name : 'System' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 font-medium">Date:</span>
                            <span class="text-gray-500">{{ formatDate(year.created_at) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-2 border-t border-gray-200">
                        <button
                            v-if="year.file_path"
                            @click="downloadFile(year)"
                            class="flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download File
                        </button>
                        <div class="flex gap-2">
                            <div class="flex-1 flex items-center gap-1">
                                <button
                                    @click="openEditModal(year)"
                                    class="flex-1 px-4 py-2 bg-yellow-100 hover:bg-yellow-200 hover:scale-105 text-yellow-800 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-1"
                                >
                                    ✏️ Edit Notes
                                </button>
                                <InfoTooltip 
                                    title="Edit Notes"
                                    content="Add or modify notes for this academic year to keep track of important information or changes."
                                    position="left"
                                />
                            </div>
                            <div class="flex-1 flex items-center gap-1">
                                <button
                                    @click="deleteYear(year)"
                                    class="flex-1 px-4 py-2 bg-red-100 hover:bg-red-200 hover:scale-105 text-red-800 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-1"
                                >
                                    🗑️ Delete
                                </button>
                                <InfoTooltip 
                                    title="⚠️ Delete Academic Year"
                                    content="CAUTION: This will permanently remove this academic year and ALL associated data including courses, grades, and submissions. This action cannot be undone!"
                                    position="left"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="latestYears.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                    No academic years found. Create one to get started.
                </div>
            </div>

            <!-- Academic Years List - Desktop Table (hidden on mobile) -->
            <div class="hidden md:block bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="year in latestYears" :key="year.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ year.year_name }}</div>
                                    <button
                                        v-if="groupedYears[year.year_name].length > 1"
                                        @click="showVersionHistory(year.year_name)"
                                        class="text-xs text-blue-600 hover:text-blue-800"
                                    >
                                        View {{ groupedYears[year.year_name].length }} versions
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        {{ year.version }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="toggleStatus(year)"
                                        :class="getStatusClass(year.status)"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium cursor-pointer hover:opacity-80 transition"
                                    >
                                        <span class="mr-1">{{ getStatusIcon(year.status) }}</span>
                                        {{ year.status }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button
                                        v-if="year.file_path"
                                        @click="downloadFile(year)"
                                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Download
                                    </button>
                                    <span v-else class="text-gray-400">No file</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ year.uploader ? year.uploader.first_name + ' ' + year.uploader.last_name : 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(year.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button
                                        @click="openEditModal(year)"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Edit Notes"
                                    >
                                        ✏️
                                    </button>
                                    <button
                                        @click="deleteYear(year)"
                                        class="text-red-600 hover:text-red-900"
                                        title="Delete"
                                    >
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="latestYears.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No academic years found. Create one to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Create Academic Year</h2>
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Year Name</label>
                                <div
                                    @click="showYearSelectorModal = true"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent cursor-pointer hover:bg-gray-50 flex justify-between items-center"
                                >
                                    <span :class="createForm.year_name ? 'text-gray-900' : 'text-gray-400'">
                                        {{ createForm.year_name || 'Select academic year...' }}
                                    </span>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">If this year exists, a new version will be created</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload File (Optional)</label>
                                <input
                                    @change="handleFileUpload"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.xlsx,.xls"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900"
                                />
                                <p class="mt-1 text-xs text-gray-500">Supported: PDF, DOC, DOCX, XLSX (Max: 10MB)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    v-model="createForm.status"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900"
                                >
                                    <option value="Inactive">Inactive</option>
                                    <option value="Active">Active</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Only one year can be active at a time</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                <textarea
                                    v-model="createForm.notes"
                                    rows="3"
                                    placeholder="Add any notes or comments..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900"
                                ></textarea>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="flex-1 bg-red-900 hover:bg-red-800 text-white py-2 px-4 rounded-lg font-medium transition disabled:opacity-50"
                                >
                                    {{ createForm.processing ? 'Creating...' : 'Create' }}
                                </button>
                                <button
                                    @click="showCreateModal = false"
                                    type="button"
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg font-medium transition"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Notes Modal -->
            <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Edit Notes</h2>
                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    v-model="editForm.notes"
                                    rows="5"
                                    placeholder="Add any notes or comments..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900"
                                ></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="flex-1 bg-red-900 hover:bg-red-800 text-white py-2 px-4 rounded-lg font-medium transition disabled:opacity-50"
                                >
                                    {{ editForm.processing ? 'Saving...' : 'Save' }}
                                </button>
                                <button
                                    @click="showEditModal = false"
                                    type="button"
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg font-medium transition"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </div>

            <!-- Programs Tab Content -->
            <div v-show="activeTab === 'programs'" class="space-y-4">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course Templates
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="program in programs" :key="program.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ program.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">{{ program.description || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ program.course_templates_count }} Templates
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': program.status === 'Active',
                                            'bg-red-100 text-red-800': program.status === 'Inactive'
                                        }"
                                    >
                                        {{ program.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button 
                                        @click="openEditProgramModal(program)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        @click="confirmDeleteProgram(program)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="programs.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No programs found. Click "Add Program" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Course Templates Tab Content -->
            <div v-show="activeTab === 'course-templates'" class="space-y-4">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Units
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="template in courseTemplates" :key="template.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ template.course_code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ template.course_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-blue-100 text-blue-800': template.course_type === 'Major Course',
                                            'bg-green-100 text-green-800': template.course_type === 'Basic Course',
                                            'bg-purple-100 text-purple-800': template.course_type === 'Thesis'
                                        }"
                                    >
                                        {{ template.course_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">{{ template.program?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {{ template.units }} Units
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': template.status === 'Active',
                                            'bg-red-100 text-red-800': template.status === 'Inactive'
                                        }"
                                    >
                                        {{ template.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button 
                                        @click="openEditTemplateModal(template)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        @click="confirmDeleteTemplate(template)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="courseTemplates.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No course templates found. Click "Add Course Template" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Program Modal -->
            <div v-if="showVersionHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Version History</h2>
                        <div class="space-y-4">
                            <div
                                v-for="version in versionHistory"
                                :key="version.id"
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                {{ version.version }}
                                            </span>
                                            <span :class="getStatusClass(version.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getStatusIcon(version.status) }} {{ version.status }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <strong>Uploaded by:</strong> 
                                            {{ version.uploader ? version.uploader.first_name + ' ' + version.uploader.last_name : 'System' }}
                                        </p>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <strong>Date:</strong> {{ formatDate(version.created_at) }}
                                        </p>
                                        <p v-if="version.notes" class="text-sm text-gray-700 mt-2">
                                            <strong>Notes:</strong> {{ version.notes }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            v-if="version.file_path"
                                            @click="downloadFile(version)"
                                            class="text-blue-600 hover:text-blue-800 p-2"
                                            title="Download"
                                        >
                                            📥
                                        </button>
                                        <button
                                            @click="deleteYear(version)"
                                            class="text-red-600 hover:text-red-900 p-2"
                                            title="Delete"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button
                                @click="showVersionHistoryModal = false"
                                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg font-medium transition"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year Selector Modal -->
            <div v-if="showYearSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Select Academic Year</h2>
                            <button
                                @click="showYearSelectorModal = false; visibleYearsCount = 5"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-2">
                            <button
                                v-for="year in visibleYears"
                                :key="year"
                                @click="selectYear(year)"
                                class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-900 transition flex justify-between items-center group"
                            >
                                <span class="font-medium text-gray-900 group-hover:text-red-900">{{ year }}</span>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            
                            <!-- Load More Button -->
                            <button
                                v-if="hasMoreYears"
                                @click="loadMoreYears"
                                class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-red-900 hover:bg-red-50 transition text-gray-600 hover:text-red-900 font-medium flex items-center justify-center gap-2"
                            >
                                <span>Load More Years</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program Create/Edit Modal -->
            <div v-if="showProgramModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ isEditingProgram ? 'Edit Program' : 'Create New Program' }}
                        </h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Program Name *</label>
                            <input 
                                v-model="programFormData.name"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="e.g., MASTER OF ARTS IN EDUCATION"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea 
                                v-model="programFormData.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Brief description of the program"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select 
                                v-model="programFormData.status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            >
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            @click="closeProgramModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="saveProgram"
                            class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800"
                            :disabled="!programFormData.name"
                        >
                            {{ isEditingProgram ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Program Delete Confirmation Modal -->
            <div v-if="showDeleteProgramConfirm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Delete</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600">
                            Are you sure you want to delete the program "{{ programToDelete?.name }}"? 
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            @click="cancelDeleteProgram"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteProgram"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Course Template Create/Edit Modal -->
            <div v-if="showTemplateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ isEditingTemplate ? 'Edit Course Template' : 'Create New Course Template' }}
                        </h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Program *</label>
                            <select 
                                v-model="templateFormData.program_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            >
                                <option value="">Select a program</option>
                                <option 
                                    v-for="program in programs.filter(p => p.status === 'Active')" 
                                    :key="program.id" 
                                    :value="program.id"
                                >
                                    {{ program.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Code *</label>
                            <input 
                                v-model="templateFormData.course_code"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="e.g., MATH101"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Name *</label>
                            <input 
                                v-model="templateFormData.course_name"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="e.g., Calculus I"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Type *</label>
                            <select 
                                v-model="templateFormData.course_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            >
                                <option value="Major Course">Major Course</option>
                                <option value="Basic Course">Basic Course</option>
                                <option value="Thesis">Thesis</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Units *</label>
                            <input 
                                v-model.number="templateFormData.units"
                                type="number"
                                min="1"
                                max="12"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">Enter a value between 1 and 12</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select 
                                v-model="templateFormData.status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            >
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            @click="closeTemplateModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="saveTemplate"
                            class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800"
                            :disabled="!templateFormData.program_id || !templateFormData.course_code || !templateFormData.course_name || templateFormData.units < 1 || templateFormData.units > 12"
                        >
                            {{ isEditingTemplate ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Course Template Delete Confirmation Modal -->
            <div v-if="showDeleteTemplateConfirm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Delete</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600">
                            Are you sure you want to delete the course template "{{ templateToDelete?.course_name }}"? 
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            @click="cancelDeleteTemplate"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteTemplate"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
