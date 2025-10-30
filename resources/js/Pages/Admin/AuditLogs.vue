<template>
    <Head title="Audit Logs" />

    <AdminLayout>
        <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-2">
                    <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
                    <InfoTooltip 
                        title="Audit Logs"
                        content="Complete audit trail of all system activities including user actions, logins, data modifications, and deletions. Use this to monitor security and track changes."
                        position="right"
                    />
                </div>
                <p class="mt-2 text-sm text-gray-600">Track and monitor all system activities</p>
            </div>

            <!-- Summary Cards -->
            <div class="flex items-center gap-2 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Activity Statistics</h2>
                <InfoTooltip 
                    title="Activity Statistics"
                    content="Real-time statistics showing today's system activity including total actions, logins, deletions, and the most active user."
                    position="right"
                />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Actions Today -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Actions Today</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.today_actions }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Number of Logins -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Logins Today</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.today_logins }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Number of Deletions -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Deletions Today</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.today_deletions }}</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-full">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Most Active User -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Most Active User</p>
                            <p class="text-lg font-bold text-gray-900 truncate">{{ stats.most_active_user || 'N/A' }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Log Filters</h2>
                    <InfoTooltip 
                        title="Log Filters"
                        content="Filter audit logs by keywords, action type, user role, module, or date range to find specific activities quickly."
                        position="right"
                    />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search Bar -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-medium text-gray-700">Search</label>
                            <InfoTooltip 
                                title="Search Logs"
                                content="Search by username, user ID, action description, or any text in the log entries. Results update in real-time as you type."
                                position="top"
                            />
                        </div>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search by username, user ID, action..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Action Type Filter -->
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-sm font-medium text-gray-700">Action Type</label>
                            <InfoTooltip 
                                title="Action Type Filter"
                                content="Filter by type of action: Create (new records), Update (modifications), Delete (removals), or Login (user sessions)."
                                position="top"
                            />
                        </div>
                        <select
                            v-model="filters.action"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            @change="applyFilters"
                        >
                            <option value="">All Actions</option>
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="approve">Approve</option>
                        </select>
                    </div>

                    <!-- User Role Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User Role</label>
                        <select
                            v-model="filters.role"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            @change="applyFilters"
                        >
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                </div>

                <!-- Date Range and Quick Filters -->
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <!-- Date Range -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="filters.start_date"
                            type="date"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm"
                            @change="applyFilters"
                        />
                        <span class="text-gray-500">to</span>
                        <input
                            v-model="filters.end_date"
                            type="date"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent text-sm"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Quick Filter Chips -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="setQuickFilter('today')"
                            :class="quickFilter === 'today' ? 'bg-red-900 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition"
                        >
                            Today
                        </button>
                        <button
                            @click="setQuickFilter('week')"
                            :class="quickFilter === 'week' ? 'bg-red-900 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition"
                        >
                            This Week
                        </button>
                        <button
                            @click="setQuickFilter('month')"
                            :class="quickFilter === 'month' ? 'bg-red-900 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition"
                        >
                            This Month
                        </button>
                    </div>

                    <!-- Reset and Export Buttons -->
                    <div class="ml-auto flex gap-2">
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm font-medium"
                        >
                            Reset Filters
                        </button>
                        <button
                            @click="exportLogs"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
                        >
                            Export CSV
                        </button>
                    </div>
                </div>

                <!-- Module View Toggle -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">View by Module</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="module in modules"
                            :key="module"
                            @click="toggleModule(module)"
                            :class="filters.module === module ? 'bg-red-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            {{ module }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ log.id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ log.user_name }}</div>
                                            <div class="text-sm text-gray-500">{{ log.user_role }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getActionBadgeClass(log.action)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                        <span class="mr-1">{{ getActionIcon(log.action) }}</span>
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getModuleBadgeClass(log.module)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                        {{ log.module }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatTimestamp(log.created_at) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button
                                        @click="viewDetails(log)"
                                        class="text-red-900 hover:text-red-700 font-medium"
                                    >
                                        View More
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No audit logs found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-gray-200">
                    <div
                        v-for="log in logs.data"
                        :key="log.id"
                        class="p-4 hover:bg-gray-50 transition"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-medium text-gray-900">#{{ log.id }}</span>
                            <span :class="getActionBadgeClass(log.action)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                                <span class="mr-1">{{ getActionIcon(log.action) }}</span>
                                {{ log.action }}
                            </span>
                        </div>
                        <div class="mb-2">
                            <p class="text-sm font-medium text-gray-900">{{ log.user_name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="text-xs text-gray-500">{{ log.user_role }}</p>
                                <span :class="getModuleBadgeClass(log.module)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                                    {{ log.module }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">{{ formatTimestamp(log.created_at) }}</span>
                            <button
                                @click="viewDetails(log)"
                                class="text-xs text-red-900 hover:text-red-700 font-medium"
                            >
                                View Details
                            </button>
                        </div>
                    </div>
                    <div v-if="logs.data.length === 0" class="p-8 text-center text-gray-500">
                        No audit logs found
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="logs.data.length > 0" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ logs.from }}</span> to <span class="font-medium">{{ logs.to }}</span> of <span class="font-medium">{{ logs.total }}</span> results
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-for="link in logs.links"
                                :key="link.label"
                                @click="goToPage(link.url)"
                                :disabled="!link.url"
                                :class="[
                                    link.active ? 'bg-red-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium transition"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div
            v-if="showDetailsModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            @click.self="closeDetailsModal"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDetailsModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Audit Log Details</h3>
                            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="selectedLog" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Log ID</label>
                                    <p class="mt-1 text-sm text-gray-900">#{{ selectedLog.id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Action</label>
                                    <p class="mt-1">
                                        <span :class="getActionBadgeClass(selectedLog.action)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                            <span class="mr-1">{{ getActionIcon(selectedLog.action) }}</span>
                                            {{ selectedLog.action }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">User</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ selectedLog.user_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">User Role</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ selectedLog.user_role }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Affected Module</label>
                                <p class="mt-1">
                                    <span :class="getModuleBadgeClass(selectedLog.module)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                        {{ selectedLog.module }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <p class="mt-1 text-sm text-gray-900">{{ selectedLog.description }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Timestamp</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatFullTimestamp(selectedLog.created_at) }}</p>
                            </div>

                            <div v-if="selectedLog.changes && hasRelevantChanges(selectedLog.changes)">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Changes Made</label>
                                <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
                                    <div class="space-y-3">
                                        <div v-for="(value, field) in getRelevantChanges(selectedLog.changes)" :key="field" class="border-b border-gray-200 pb-2 last:border-0">
                                            <p class="text-xs font-semibold text-gray-600 uppercase mb-1">{{ formatFieldName(field) }}</p>
                                            <div class="flex items-start gap-2 text-sm">
                                                <div class="flex-1">
                                                    <span class="text-red-600 line-through">{{ value.old }}</span>
                                                </div>
                                                <span class="text-gray-400">→</span>
                                                <div class="flex-1">
                                                    <span class="text-green-600 font-medium">{{ value.new }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="closeDetailsModal"
                            class="w-full sm:w-auto px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition font-medium"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InfoTooltip from '@/Components/InfoTooltip.vue';

const props = defineProps({
    logs: Object,
    stats: Object,
    currentFilters: Object,
});

// State
const showDetailsModal = ref(false);
const selectedLog = ref(null);
const quickFilter = ref('');

// Filters
const filters = ref({
    search: props.currentFilters?.search || '',
    action: props.currentFilters?.action || '',
    role: props.currentFilters?.role || '',
    module: props.currentFilters?.module || '',
    start_date: props.currentFilters?.start_date || '',
    end_date: props.currentFilters?.end_date || '',
});

// Modules
const modules = ['All', 'Users', 'Calendar', 'Courses', 'Academic Year'];

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

// Apply filters
const applyFilters = () => {
    router.get(route('admin.audit-logs'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        search: '',
        action: '',
        role: '',
        module: '',
        start_date: '',
        end_date: '',
    };
    quickFilter.value = '';
    applyFilters();
};

// Quick filter
const setQuickFilter = (period) => {
    quickFilter.value = period;
    const today = new Date();
    filters.value.end_date = today.toISOString().split('T')[0];

    if (period === 'today') {
        filters.value.start_date = today.toISOString().split('T')[0];
    } else if (period === 'week') {
        const weekAgo = new Date(today);
        weekAgo.setDate(today.getDate() - 7);
        filters.value.start_date = weekAgo.toISOString().split('T')[0];
    } else if (period === 'month') {
        const monthAgo = new Date(today);
        monthAgo.setMonth(today.getMonth() - 1);
        filters.value.start_date = monthAgo.toISOString().split('T')[0];
    }

    applyFilters();
};

// Toggle module
const toggleModule = (module) => {
    if (module === 'All') {
        filters.value.module = '';
    } else {
        filters.value.module = filters.value.module === module ? '' : module;
    }
    applyFilters();
};

// View details
const viewDetails = (log) => {
    selectedLog.value = log;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedLog.value = null;
};

// Pagination
const goToPage = (url) => {
    if (!url) return;
    router.get(url, filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Export logs
const exportLogs = () => {
    const csvContent = convertToCSV(props.logs.data);
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
};

const convertToCSV = (data) => {
    const headers = ['Log ID', 'User', 'Role', 'Action', 'Module', 'Description', 'Timestamp'];
    const rows = data.map(log => [
        log.id,
        log.user_name,
        log.user_role,
        log.action,
        log.model_type,
        log.description,
        log.created_at
    ]);

    const csvRows = [headers, ...rows];
    return csvRows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
};

// Helper functions for changes
const hasRelevantChanges = (changes) => {
    if (!changes) return false;
    const relevant = getRelevantChanges(changes);
    return Object.keys(relevant).length > 0;
};

const getRelevantChanges = (changes) => {
    if (!changes || !changes.old || !changes.new) return {};
    
    const excludedFields = [
        'id', 'created_at', 'updated_at', 'email_verified_at', 
        'password', 'remember_token', 'google_id'
    ];
    
    const relevantChanges = {};
    const oldData = changes.old;
    const newData = changes.new;
    
    for (const key in newData) {
        if (excludedFields.includes(key)) continue;
        if (oldData[key] !== newData[key]) {
            relevantChanges[key] = {
                old: oldData[key] || '(empty)',
                new: newData[key] || '(empty)'
            };
        }
    }
    
    return relevantChanges;
};

const formatFieldName = (field) => {
    return field
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

// Helper functions
const getActionIcon = (action) => {
    const icons = {
        create: '🟢',
        update: '🟡',
        delete: '🔴',
        login: '🔵',
        logout: '🔵',
        approve: '✅',
    };
    return icons[action.toLowerCase()] || '⚪';
};

const getActionBadgeClass = (action) => {
    const classes = {
        create: 'bg-green-100 text-green-800',
        update: 'bg-yellow-100 text-yellow-800',
        delete: 'bg-red-100 text-red-800',
        login: 'bg-blue-100 text-blue-800',
        logout: 'bg-blue-100 text-blue-800',
        approve: 'bg-green-100 text-green-800',
    };
    return classes[action.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const getModuleBadgeClass = (module) => {
    const classes = {
        'Users': 'bg-purple-100 text-purple-800',
        'Calendar': 'bg-blue-100 text-blue-800',
        'Courses': 'bg-indigo-100 text-indigo-800',
        'Academic Year': 'bg-pink-100 text-pink-800',
    };
    return classes[module] || 'bg-gray-100 text-gray-800';
};

const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatFullTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZoneName: 'short'
    });
};
</script>
