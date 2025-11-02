<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InfoTooltip from '@/Components/InfoTooltip.vue';

const props = defineProps({
    users: Array,
    stats: Object,
    filters: Object,
});

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const currentUser = ref(null);
const processing = ref(false);

// Form data
const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    username: '',
    role: 'student',
    password: '',
    password_confirmation: ''
});

// Filters
const roleFilter = ref(props.filters.role);
const statusFilter = ref(props.filters.status);
const searchQuery = ref(props.filters.search);

// Filter and search users
const applyFilters = () => {
    router.get(route('admin.users'), {
        role: roleFilter.value,
        status: statusFilter.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Open create modal
const openCreateModal = () => {
    form.value = {
        first_name: '',
        last_name: '',
        email: '',
        username: '',
        role: 'student',
        password: '',
        password_confirmation: ''
    };
    isEditing.value = false;
    currentUser.value = null;
    showModal.value = true;
};

// Open edit modal
const editUser = (user) => {
    form.value = {
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
        username: user.username || '',
        role: user.role,
        password: '',
        password_confirmation: ''
    };
    currentUser.value = user;
    isEditing.value = true;
    showModal.value = true;
};

// Save user
const saveUser = () => {
    if (!form.value.first_name || !form.value.last_name || !form.value.email || !form.value.role) {
        alert('Please fill in all required fields');
        return;
    }

    if (!isEditing.value && !form.value.password) {
        alert('Password is required for new users');
        return;
    }

    processing.value = true;

    if (isEditing.value) {
        router.put(route('admin.users.update', currentUser.value.id), form.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeModal();
                processing.value = false;
                alert('User updated successfully!');
            },
            onError: (errors) => {
                processing.value = false;
                // Handle 419 CSRF token mismatch
                if (errors.message && errors.message.includes('CSRF')) {
                    alert('Session expired. Please refresh the page and try again.');
                    window.location.reload();
                    return;
                }
                const errorMessage = errors.error || Object.values(errors).flat().join(', ') || 'Failed to update user';
                alert(errorMessage);
            },
            onFinish: () => {
                processing.value = false;
            }
        });
    } else {
        router.post(route('admin.users.store'), form.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeModal();
                processing.value = false;
                alert('User created successfully!');
            },
            onError: (errors) => {
                processing.value = false;
                console.error('Create user errors:', errors);
                
                // Handle 419 CSRF token mismatch
                if (errors.message && errors.message.includes('CSRF')) {
                    alert('Session expired. Please refresh the page and try again.');
                    window.location.reload();
                    return;
                }
                
                const errorMessage = errors.error || Object.values(errors).flat().join(', ') || 'Failed to create user';
                alert(errorMessage);
            },
            onFinish: () => {
                processing.value = false;
            }
        });
    }
};

// Delete user
const deleteUser = (userId, userName) => {
    if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
        processing.value = true;
        router.delete(route('admin.users.destroy', userId), {
            preserveScroll: true,
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                alert('Failed to delete user');
            }
        });
    }
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    isEditing.value = false;
    currentUser.value = null;
};

// Get role badge color
const getRoleBadgeColor = (role) => {
    const colors = {
        admin: '#800000',
        teacher: '#10B981',
        student: '#F59E0B',
    };
    return colors[role] || '#6B7280';
};

// Get role icon
const getRoleIcon = (role) => {
    const icons = {
        admin: '👑',
        teacher: '👨‍🏫',
        student: '🎓',
    };
    return icons[role] || '👤';
};

// Get status badge color
const getStatusColor = (status) => {
    const colors = {
        active: '#10B981',
        inactive: '#6B7280',
        pending: '#F59E0B',
    };
    return colors[status] || '#6B7280';
};

// Generate password
const generatePassword = () => {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    form.value.password = password;
    form.value.password_confirmation = password;
};
</script>

<template>
    <Head title="User Management" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">👥 User Management</h1>
                    <InfoTooltip 
                        title="User Management"
                        content="Create, edit, and manage all system users including admins, teachers, and students. You can assign roles, manage permissions, and control user access to the system."
                        position="right"
                    />
                    <p class="text-sm text-gray-600 mt-1 ml-2">Manage all system users and their permissions</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateModal"
                        class="flex items-center justify-center space-x-2 px-6 py-3 bg-red-900 text-white rounded-lg hover:bg-red-800 hover:scale-105 transition-all shadow-md"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="font-medium">Add User</span>
                    </button>
                    <InfoTooltip 
                        title="Add New User"
                        content="Click to create a new user account. You'll need to provide basic information like name, email, role (admin/teacher/student), and set a password."
                        position="left"
                    />
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="flex items-center gap-2 mb-2">
                <h2 class="text-lg font-semibold text-gray-900">User Statistics</h2>
                <InfoTooltip 
                    title="User Statistics"
                    content="Real-time statistics showing the total count of users by role and status. This helps you monitor user distribution across the system."
                    position="right"
                />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-xl">👥</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Total Users</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-xl">👑</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Admins</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.admins }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-xl">👨‍🏫</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Teachers</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.teachers }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <span class="text-xl">🎓</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Students</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.students }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-xl">✅</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Active</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                            <span class="text-xl">⏸️</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Inactive</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.inactive }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                            <span class="text-xl">⏳</span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-600">Pending</p>
                            <p class="text-xl font-bold text-gray-900">{{ stats.pending }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                <div class="flex items-center gap-2 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
                    <InfoTooltip 
                        title="Filter Options"
                        content="Use these filters to quickly find specific users by searching names/emails, filtering by role, or filtering by account status."
                        position="right"
                    />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-1">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-semibold text-gray-900">Search</label>
                            <InfoTooltip 
                                title="Search Users"
                                content="Type any name, email, or username to instantly filter the user list. Search is case-insensitive."
                                position="top"
                            />
                        </div>
                        <input
                            v-model="searchQuery"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search by name, email, or username..."
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-red-900 focus:ring-red-900"
                        />
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-semibold text-gray-900">Filter by Role</label>
                            <InfoTooltip 
                                title="Role Filter"
                                content="Filter users by their role: Admin (full system access), Teacher (course management), or Student (course participation)."
                                position="top"
                            />
                        </div>
                        <select
                            v-model="roleFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-red-900 focus:ring-red-900"
                        >
                            <option value="all">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-semibold text-gray-900">Filter by Status</label>
                            <InfoTooltip 
                                title="Status Filter"
                                content="Filter users by account status: Active (can log in), Inactive (disabled), or Pending (awaiting approval)."
                                position="top"
                            />
                        </div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-red-900 focus:ring-red-900"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Users Table (desktop) and List (mobile) -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <!-- Mobile list: show on small screens -->
                <div class="md:hidden">
                    <div v-if="users.length > 0" class="space-y-3 p-4">
                        <div v-for="user in users" :key="user.id" class="bg-white border rounded-lg p-3 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-red-900 flex items-center justify-center text-white font-semibold flex-shrink-0 overflow-hidden relative">
                                        <img v-if="user.profile_picture" 
                                             :src="user.profile_picture" 
                                             :alt="user.name"
                                             class="w-full h-full object-cover absolute inset-0"
                                        />
                                        <span v-if="!user.profile_picture" class="z-10">
                                            {{ user.name.charAt(0) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ user.name }}</div>
                                        <div class="text-xs text-gray-600">{{ user.email }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-medium" :style="{ color: getRoleBadgeColor(user.role) }">{{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}</div>
                                    <div class="text-xs text-gray-500">{{ new Date(user.created_at).toLocaleDateString() }}</div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button @click="editUser(user)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Edit</button>
                                    <button @click="deleteUser(user.id, user.name)" class="text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :style="{ backgroundColor: `${getStatusColor(user.status)}20`, color: getStatusColor(user.status) }">{{ user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : 'Active' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-sm text-gray-600">No users found</div>
                </div>

                <!-- Desktop table: hidden on small screens -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-red-900 flex items-center justify-center text-white font-semibold flex-shrink-0 overflow-hidden relative">
                                            <img v-if="user.profile_picture" 
                                                 :src="user.profile_picture" 
                                                 :alt="user.name"
                                                 class="w-full h-full object-cover absolute inset-0"
                                            />
                                            <span v-if="!user.profile_picture" class="z-10">
                                                {{ user.name.charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ user.name }}</div>
                                            <div v-if="user.username" class="text-xs text-gray-600">@{{ user.username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                        :style="{
                                            backgroundColor: `${getRoleBadgeColor(user.role)}20`,
                                            color: getRoleBadgeColor(user.role)
                                        }"
                                    >
                                        <span class="mr-1">{{ getRoleIcon(user.role) }}</span>
                                        {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                        :style="{
                                            backgroundColor: `${getStatusColor(user.status)}20`,
                                            color: getStatusColor(user.status)
                                        }"
                                    >
                                        {{ user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : 'Active' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="editUser(user)"
                                                class="text-blue-600 hover:text-blue-900 hover:scale-105 transition-all font-medium"
                                            >
                                                Edit
                                            </button>
                                            <InfoTooltip 
                                                title="Edit User"
                                                content="Click to modify this user's information including name, email, role, and password."
                                                position="left"
                                            />
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="deleteUser(user.id, user.name)"
                                                class="text-red-600 hover:text-red-900 hover:scale-105 transition-all font-medium"
                                            >
                                                Delete
                                            </button>
                                            <InfoTooltip 
                                                title="⚠️ Delete User"
                                                content="CAUTION: This will permanently remove this user and all their associated data from the system. This action cannot be undone!"
                                                position="left"
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="users.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">No users found</h3>
                        <p class="mt-1 text-xs text-gray-600">Try adjusting your filters or search criteria.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full mx-4">
                    <div class="h-2 bg-red-900"></div>
                    <div class="bg-white px-4 md:px-6 pt-5 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base md:text-lg font-semibold text-gray-900">
                                {{ isEditing ? 'Edit User' : 'Add New User' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="saveUser" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs md:text-sm font-semibold text-gray-900 mb-1">First Name *</label>
                                    <input
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                        placeholder="John"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs md:text-sm font-semibold text-gray-900 mb-1">Last Name *</label>
                                    <input
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                        placeholder="Doe"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs md:text-sm font-semibold text-gray-900 mb-1">Email Address *</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    placeholder="john.doe@example.com"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs md:text-sm font-semibold text-gray-900 mb-1">Username (optional)</label>
                                    <input
                                        v-model="form.username"
                                        type="text"
                                        class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                        placeholder="johndoe"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs md:text-sm font-semibold text-gray-900 mb-1">Role *</label>
                                    <select
                                        v-model="form.role"
                                        required
                                        class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    >
                                        <option value="admin">Admin</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="student">Student</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs md:text-sm font-semibold text-gray-900">
                                        Password {{ isEditing ? '(leave blank to keep current)' : '*' }}
                                    </label>
                                    <button
                                        type="button"
                                        @click="generatePassword"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        Generate Password
                                    </button>
                                </div>
                                <input
                                    v-model="form.password"
                                    type="text"
                                    :required="!isEditing"
                                    class="w-full text-sm md:text-base rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    placeholder="Enter password"
                                />
                            </div>

                            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="w-full sm:flex-1 px-4 py-2.5 bg-red-900 text-white rounded-lg hover:bg-red-800 transition-colors font-medium text-sm md:text-base disabled:opacity-50"
                                >
                                    {{ processing ? 'Saving...' : (isEditing ? 'Update User' : 'Create User') }}
                                </button>
                                <button
                                    type="button"
                                    @click="closeModal"
                                    :disabled="processing"
                                    class="w-full sm:flex-1 px-4 py-2.5 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium text-sm md:text-base disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
