<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    courseTemplates: {
        type: Array,
        required: true
    },
    programs: {
        type: Array,
        required: true
    }
});

const showModal = ref(false);
const isEditing = ref(false);
const formData = ref({
    id: null,
    program_id: '',
    course_code: '',
    course_name: '',
    units: 3,
    status: 'active'
});

const showDeleteConfirm = ref(false);
const templateToDelete = ref(null);

const openCreateModal = () => {
    formData.value = {
        id: null,
        program_id: '',
        course_code: '',
        course_name: '',
        units: 3,
        status: 'Active'
    };
    isEditing.value = false;
    showModal.value = true;
};

const openEditModal = (template) => {
    formData.value = {
        id: template.id,
        program_id: template.program_id,
        course_code: template.course_code,
        course_name: template.course_name,
        units: template.units,
        status: template.status
    };
    isEditing.value = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    formData.value = {
        id: null,
        program_id: '',
        course_code: '',
        course_name: '',
        units: 3,
        status: 'Active'
    };
};

const saveTemplate = () => {
    if (isEditing.value) {
        router.put(route('admin.course-templates.update', formData.value.id), {
            program_id: formData.value.program_id,
            course_code: formData.value.course_code,
            course_name: formData.value.course_name,
            units: formData.value.units,
            status: formData.value.status
        }, {
            onSuccess: () => {
                closeModal();
            }
        });
    } else {
        router.post(route('admin.course-templates.store'), {
            program_id: formData.value.program_id,
            course_code: formData.value.course_code,
            course_name: formData.value.course_name,
            units: formData.value.units,
            status: formData.value.status
        }, {
            onSuccess: () => {
                closeModal();
            }
        });
    }
};

const confirmDelete = (template) => {
    templateToDelete.value = template;
    showDeleteConfirm.value = true;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        router.delete(route('admin.course-templates.destroy', templateToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                templateToDelete.value = null;
            }
        });
    }
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    templateToDelete.value = null;
};
</script>

<template>
    <Head title="Course Templates" />
    <AdminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Course Templates</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage course templates for each program</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Course Template</span>
                </button>
            </div>

            <!-- Course Templates Table -->
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
                                    @click="openEditModal(template)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Edit
                                </button>
                                <button 
                                    @click="confirmDelete(template)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="courseTemplates.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No course templates found. Click "Add Course Template" to create one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ isEditing ? 'Edit Course Template' : 'Create New Course Template' }}
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Program *
                        </label>
                        <select 
                            v-model="formData.program_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Course Code *
                        </label>
                        <input 
                            v-model="formData.course_code"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., MATH101"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Course Name *
                        </label>
                        <input 
                            v-model="formData.course_name"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Calculus I"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Units *
                        </label>
                        <input 
                            v-model.number="formData.units"
                            type="number"
                            min="1"
                            max="12"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">Enter a value between 1 and 12</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select 
                            v-model="formData.status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button 
                        @click="closeModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveTemplate"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        :disabled="!formData.program_id || !formData.course_code || !formData.course_name || formData.units < 1 || formData.units > 12"
                    >
                        {{ isEditing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
                        @click="cancelDelete"
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
    </AdminLayout>
</template>
