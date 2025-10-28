<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    reportType: {
        type: String,
        default: 'users'
    },
    data: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const selectedType = ref(props.reportType);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const loading = ref(false);
const reportData = ref(props.data);

const reportTypes = [
    { value: 'users', label: 'Users Report', icon: '👥' },
    { value: 'courses', label: 'Courses Report', icon: '📚' },
    { value: 'academic_year', label: 'Academic Year Report', icon: '🎓' },
    { value: 'audit_logs', label: 'Audit Logs Report', icon: '📋' },
    { value: 'performance', label: 'Performance Report', icon: '📊' },
];

const currentReportType = computed(() => {
    return reportTypes.find(t => t.value === selectedType.value);
});

const summaryCards = computed(() => {
    const summary = reportData.value.summary || {};
    switch (selectedType.value) {
        case 'users':
            return [
                { label: 'Total Users', value: summary.total || 0, icon: '👥', color: 'blue' },
                { label: 'Admins', value: summary.admins || 0, icon: '🔐', color: 'purple' },
                { label: 'Teachers', value: summary.teachers || 0, icon: '👨‍🏫', color: 'green' },
                { label: 'Students', value: summary.students || 0, icon: '👩‍🎓', color: 'yellow' },
            ];
        case 'courses':
            return [
                { label: 'Total Courses', value: summary.total || 0, icon: '📚', color: 'blue' },
                { label: 'Active', value: summary.active || 0, icon: '✅', color: 'green' },
                { label: 'Pending', value: summary.pending || 0, icon: '⏳', color: 'yellow' },
                { label: 'Archived', value: summary.archived || 0, icon: '📦', color: 'gray' },
            ];
        case 'academic_year':
            return [
                { label: 'Total Years', value: summary.total || 0, icon: '🎓', color: 'blue' },
                { label: 'Active', value: summary.active || 0, icon: '🟢', color: 'green' },
                { label: 'Inactive', value: summary.inactive || 0, icon: '⚪', color: 'gray' },
                { label: 'Versions', value: summary.versions || 0, icon: '📑', color: 'purple' },
            ];
        case 'audit_logs':
            return [
                { label: 'Total Logs', value: summary.total || 0, icon: '📋', color: 'blue' },
                { label: 'Creates', value: summary.creates || 0, icon: '➕', color: 'green' },
                { label: 'Updates', value: summary.updates || 0, icon: '✏️', color: 'yellow' },
                { label: 'Deletes', value: summary.deletes || 0, icon: '🗑️', color: 'red' },
            ];
        case 'performance':
            return [
                { label: 'Total Users', value: summary.total_users || 0, icon: '👥', color: 'blue' },
                { label: 'Total Events', value: summary.total_events || 0, icon: '📅', color: 'green' },
                { label: 'Academic Years', value: summary.total_academic_years || 0, icon: '🎓', color: 'purple' },
                { label: 'Audit Logs', value: summary.total_audit_logs || 0, icon: '📋', color: 'yellow' },
            ];
        default:
            return [];
    }
});

const tableColumns = computed(() => {
    switch (selectedType.value) {
        case 'users':
            return ['Name', 'Email', 'Role', 'Status', 'Created'];
        case 'courses':
            return ['Course Name', 'Instructor', 'Students', 'Status', 'Created'];
        case 'academic_year':
            return ['Year', 'Version', 'Status', 'Uploaded By', 'Created'];
        case 'audit_logs':
            return ['User', 'Action', 'Module', 'Description', 'Date/Time'];
        case 'performance':
            return ['Metric', 'Value', 'Percentage'];
        default:
            return [];
    }
});

const fetchReportData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.reports.fetch'), {
            params: {
                type: selectedType.value,
                start_date: startDate.value,
                end_date: endDate.value,
            }
        });
        reportData.value = response.data;
    } catch (error) {
        console.error('Error fetching report data:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedType, () => {
    fetchReportData();
});

const applyDateFilter = () => {
    fetchReportData();
};

const resetFilters = () => {
    startDate.value = '';
    endDate.value = '';
    fetchReportData();
};

const exportReport = (format) => {
    // Prepare data
    const data = {
        type: selectedType.value,
        format: format,
        records: reportData.value.records,
        summary: reportData.value.summary,
        start_date: startDate.value,
        end_date: endDate.value,
    };

    // Convert to CSV
    if (format === 'csv') {
        exportToCSV(data);
    } else if (format === 'excel') {
        // Export to Excel (same as CSV for now)
        exportToCSV(data);
    } else if (format === 'pdf') {
        alert('PDF export will be implemented with a PDF library');
    }
};

const exportToCSV = (data) => {
    const records = data.records;
    if (!records || records.length === 0) {
        alert('No data to export');
        return;
    }

    // Get headers from first record
    const headers = Object.keys(records[0]);
    const csvContent = [
        headers.join(','),
        ...records.map(record => 
            headers.map(header => `"${record[header] || ''}"`).join(',')
        )
    ].join('\n');

    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${data.type}_report_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);
};

const getCardColorClass = (color) => {
    const colors = {
        blue: 'border-blue-500 bg-blue-50',
        green: 'border-green-500 bg-green-50',
        yellow: 'border-yellow-500 bg-yellow-50',
        red: 'border-red-500 bg-red-50',
        purple: 'border-purple-500 bg-purple-50',
        gray: 'border-gray-500 bg-gray-50',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head title="Reports Overview" />

    <AdminLayout>
        <div class="space-y-6 p-4 md:p-0">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Reports Overview</h1>
                    <p class="mt-2 text-sm md:text-base text-gray-600">Generate and export system reports</p>
                </div>
                
                <!-- Export Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="exportReport('csv')"
                        class="flex-1 sm:flex-none px-3 md:px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm md:text-base font-medium transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        CSV
                    </button>
                    <button
                        @click="exportReport('excel')"
                        class="flex-1 sm:flex-none px-3 md:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm md:text-base font-medium transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Excel
                    </button>
                    <button
                        @click="exportReport('pdf')"
                        class="flex-1 sm:flex-none px-3 md:px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm md:text-base font-medium transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Report Type -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                        <select
                            v-model="selectedType"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        >
                            <option v-for="type in reportTypes" :key="type.value" :value="type.value">
                                {{ type.icon }} {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input
                            type="date"
                            v-model="startDate"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input
                            type="date"
                            v-model="endDate"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex flex-col sm:flex-row gap-2 mt-4">
                    <button
                        @click="applyDateFilter"
                        class="flex-1 sm:flex-none px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg font-medium transition text-sm md:text-base"
                    >
                        Apply Filters
                    </button>
                    <button
                        @click="resetFilters"
                        class="flex-1 sm:flex-none px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition text-sm md:text-base"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-red-900"></div>
                <p class="mt-4 text-gray-600">Loading report data...</p>
            </div>

            <template v-else>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        :class="['bg-white rounded-lg shadow-md border-l-4 p-6 hover:shadow-lg transition-shadow', getCardColorClass(card.color)]"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ card.label }}</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                            </div>
                            <div class="text-4xl">{{ card.icon }}</div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg md:text-xl font-semibold text-gray-900">
                            {{ currentReportType?.icon }} {{ currentReportType?.label }} - Detailed View
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        v-for="column in tableColumns"
                                        :key="column"
                                        class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ column }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Users -->
                                <template v-if="selectedType === 'users'">
                                    <tr v-for="record in reportData.records" :key="record.id" class="hover:bg-gray-50">
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">{{ record.name }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.email }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">{{ record.role }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">{{ record.status }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.created_at }}</td>
                                    </tr>
                                </template>

                                <!-- Academic Year -->
                                <template v-else-if="selectedType === 'academic_year'">
                                    <tr v-for="record in reportData.records" :key="record.id" class="hover:bg-gray-50">
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">{{ record.year_name }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.version }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">{{ record.status }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.uploaded_by }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.created_at }}</td>
                                    </tr>
                                </template>

                                <!-- Audit Logs -->
                                <template v-else-if="selectedType === 'audit_logs'">
                                    <tr v-for="record in reportData.records" :key="record.id" class="hover:bg-gray-50">
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">{{ record.user }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.action }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">{{ record.module }}</td>
                                        <td class="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-600 max-w-md truncate">{{ record.description }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ record.created_at }}</td>
                                    </tr>
                                </template>

                                <!-- Performance -->
                                <template v-else-if="selectedType === 'performance'">
                                    <tr v-for="(record, index) in reportData.records" :key="index" class="hover:bg-gray-50">
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">{{ record.metric }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-600 font-bold">{{ record.value }}</td>
                                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">{{ record.percentage }}%</td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-if="!reportData.records || reportData.records.length === 0">
                                    <td :colspan="tableColumns.length" class="px-3 md:px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="mt-2">No data available for this report</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chart Section -->
                <div v-if="reportData.chartData" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Visual Analytics</h3>
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                        <div class="text-center">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="mt-4 text-gray-600">Chart visualization (Chart.js integration)</p>
                            <p class="text-sm text-gray-500 mt-2">Data: {{ reportData.chartData.labels?.length || 0 }} points</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
