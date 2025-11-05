<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    programs: {
        type: Array,
        required: true
    }
});

const showModal = ref(false);
const isEditing = ref(false);
const formData = ref({
    id: null,
    name: '',
    description: '',
    status: 'active'
});

const showDeleteConfirm = ref(false);
const programToDelete = ref(null);

const openCreateModal = () => {
    formData.value = {
        id: null,
        name: '',
        description: '',
        status: 'Active'
    };
    isEditing.value = false;
    showModal.value = true;
};

const openEditModal = (program) => {
    formData.value = {
        id: program.id,
        name: program.name,
        description: program.description || '',
        status: program.status
    };
    isEditing.value = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    formData.value = {
        id: null,
        name: '',
        description: '',
        status: 'Active'
    };
};

const saveProgram = () => {
    if (isEditing.value) {
        router.put(route('admin.programs.update', formData.value.id), {
            name: formData.value.name,
            description: formData.value.description,
            status: formData.value.status
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                // Reload the page data to show updated program
                router.reload({ only: ['programs'] });
            }
        });
    } else {
        router.post(route('admin.programs.store'), {
            name: formData.value.name,
            description: formData.value.description,
            status: formData.value.status
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                // Reload the page data to show new program
                router.reload({ only: ['programs'] });
            }
        });
    }
};

const confirmDelete = (program) => {
    programToDelete.value = program;
    showDeleteConfirm.value = true;
};

const deleteProgram = () => {
    if (programToDelete.value) {
        router.delete(route('admin.programs.destroy', programToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirm.value = false;
                programToDelete.value = null;
                // Reload the page data to remove deleted program
                router.reload({ only: ['programs'] });
            }
        });
    }
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    programToDelete.value = null;
};
</script>

<template>
    <Head title="Programs" />
    <AdminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Programs Management</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage academic programs and their course templates</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Program</span>
                </button>
            </div>

            <!-- Programs Table -->
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
                                    @click="openEditModal(program)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Edit
                                </button>
                                <button 
                                    @click="confirmDelete(program)"
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

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ isEditing ? 'Edit Program' : 'Create New Program' }}
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Program Name *
                        </label>
                        <input 
                            v-model="formData.name"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., MASTER OF ARTS IN EDUCATION"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea 
                            v-model="formData.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Brief description of the program"
                        ></textarea>
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
                        @click="saveProgram"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        :disabled="!formData.name"
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
                        Are you sure you want to delete the program "{{ programToDelete?.name }}"? 
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
                        @click="deleteProgram"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
