<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    course: Object,
    students: Array,
    gradebook: Object,
});

// Print function
const printGradeSheet = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Grade Sheet - ${course.title}`" />

    <AdminLayout>
        <div class="max-w-[1400px] mx-auto p-6 space-y-6">
            <!-- Action Buttons -->
            <div class="flex items-center justify-between print:hidden">
                <Link
                    :href="route('admin.class-record')"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Class Record
                </Link>

                <div class="flex items-center gap-3">
                    <a
                        :href="route('admin.class-record.grade-sheet.pdf', course.id)"
                        target="_blank"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View PDF
                    </a>
                    
                    <a
                        :href="route('admin.class-record.grade-sheet.download', course.id)"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download PDF
                    </a>

                    <button
                        @click="printGradeSheet"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>

            <!-- Grade Sheet Document -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden print:shadow-none">
                <!-- Header -->
                <div class="text-center p-8 border-b-4 border-red-900">
                    <div class="mb-4">
                        <div class="text-red-900 font-bold text-2xl mb-2">PHILIPPINE CHRISTIAN UNIVERSITY</div>
                        <div class="text-gray-700 text-base">Taft Avenue, Manila</div>
                        <div class="text-gray-700 text-base font-semibold mt-1">GRADUATE SCHOOL</div>
                    </div>
                    <h1 class="text-3xl font-bold text-red-900 mt-6 mb-2">GRADING SHEET</h1>
                    <p class="text-gray-700 text-lg font-medium">{{ course.academic_year?.name || 'Academic Year' }}</p>
                </div>

                <!-- Course Information -->
                <div class="p-8 bg-gray-50 border-b-2 border-gray-300">
                    <div class="grid grid-cols-2 gap-x-16 gap-y-4 text-base">
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Course Title:</span>
                            <span class="text-gray-900 font-semibold">{{ course.title }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Course Code:</span>
                            <span class="text-gray-900 font-medium">{{ course.section }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Instructor:</span>
                            <span class="text-gray-900 font-medium">{{ course.teacher?.first_name }} {{ course.teacher?.last_name }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Program:</span>
                            <span class="text-gray-900 font-medium">{{ course.program?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Academic Year:</span>
                            <span class="text-gray-900 font-medium">{{ course.academic_year?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="font-bold text-gray-800 min-w-[160px]">Total Students:</span>
                            <span class="text-gray-900 font-semibold">{{ students.length }}</span>
                        </div>
                    </div>
                </div>

                <!-- Grade Table -->
                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="w-full border-2 border-gray-900">
                            <thead>
                                <tr class="bg-red-900 text-white">
                                    <th class="border-2 border-gray-900 px-4 py-4 text-center font-bold text-base w-16">No.</th>
                                    <th class="border-2 border-gray-900 px-6 py-4 text-left font-bold text-base">Student Name</th>
                                    <th class="border-2 border-gray-900 px-4 py-4 text-center font-bold text-base w-32">Midterm<br>Grade</th>
                                    <th class="border-2 border-gray-900 px-4 py-4 text-center font-bold text-base w-32">Finals<br>Grade</th>
                                    <th class="border-2 border-gray-900 px-4 py-4 text-center font-bold text-base w-32">Final Grade</th>
                                    <th class="border-2 border-gray-900 px-4 py-4 text-center font-bold text-base w-32">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="(student, index) in students" 
                                    :key="student.id"
                                    class="hover:bg-gray-50"
                                    :class="{ 'bg-gray-50': index % 2 === 0 }"
                                >
                                    <td class="border-2 border-gray-400 px-4 py-3 text-center font-semibold text-gray-900">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="border-2 border-gray-400 px-6 py-3 font-semibold text-gray-900">
                                        {{ student.last_name }}, {{ student.first_name }}
                                    </td>
                                    <td class="border-2 border-gray-400 px-4 py-3 text-center font-bold text-lg text-gray-900">
                                        {{ student.midterm_grade }}
                                    </td>
                                    <td class="border-2 border-gray-400 px-4 py-3 text-center font-bold text-lg text-gray-900">
                                        {{ student.finals_grade }}
                                    </td>
                                    <td class="border-2 border-gray-400 px-4 py-3 text-center font-bold text-xl text-green-700">
                                        {{ student.letter_grade }}
                                    </td>
                                    <td class="border-2 border-gray-400 px-4 py-3 text-center">
                                        <span 
                                            class="px-3 py-1 rounded-full text-sm font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-800': student.remarks === 'Passed',
                                                'bg-yellow-100 text-yellow-800': student.remarks === 'Conditional',
                                                'bg-red-100 text-red-800': student.remarks === 'Failed',
                                                'bg-gray-100 text-gray-800': student.remarks === 'Incomplete'
                                            }"
                                        >
                                            {{ student.remarks }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0">
                                    <td colspan="6" class="border-2 border-gray-400 px-4 py-12 text-center text-gray-500 text-lg">
                                        No students enrolled in this course
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Grading System Legend -->
                    <div class="mt-8 p-6 bg-gray-50 rounded-lg border-2 border-gray-300">
                        <h3 class="font-bold text-gray-900 mb-4 text-lg">Grading System</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-sm"><span class="font-bold text-base">1.0</span> - 97-100 (Excellent)</div>
                            <div class="text-sm"><span class="font-bold text-base">1.25</span> - 94-96 (Very Good)</div>
                            <div class="text-sm"><span class="font-bold text-base">1.5</span> - 91-93 (Very Good)</div>
                            <div class="text-sm"><span class="font-bold text-base">1.75</span> - 88-90 (Good)</div>
                            <div class="text-sm"><span class="font-bold text-base">2.0</span> - 85-87 (Good)</div>
                            <div class="text-sm"><span class="font-bold text-base">2.25</span> - 82-84 (Satisfactory)</div>
                            <div class="text-sm"><span class="font-bold text-base">2.5</span> - 79-81 (Satisfactory)</div>
                            <div class="text-sm"><span class="font-bold text-base">2.75</span> - 76-78 (Fair)</div>
                            <div class="text-sm"><span class="font-bold text-base">3.0</span> - 75 (Passing)</div>
                            <div class="text-sm"><span class="font-bold text-base">4.0</span> - 70-74 (Conditional)</div>
                            <div class="text-sm"><span class="font-bold text-base">5.0</span> - 65-69 (Failing)</div>
                            <div class="text-sm"><span class="font-bold text-base">F</span> - Below 65 (Failing)</div>
                        </div>
                    </div>

                    <!-- Signatures -->
                    <div class="mt-16 grid grid-cols-3 gap-12">
                        <div class="text-center">
                            <div class="mb-16"></div>
                            <div class="border-t-2 border-gray-900 pt-2">
                                <div class="font-bold text-gray-900 text-base">{{ course.teacher?.first_name }} {{ course.teacher?.last_name }}</div>
                                <div class="text-sm text-gray-700 mt-1">Instructor</div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="mb-16"></div>
                            <div class="border-t-2 border-gray-900 pt-2">
                                <div class="font-bold text-gray-900 text-base">&nbsp;</div>
                                <div class="text-sm text-gray-700 mt-1">Program Director</div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="mb-16"></div>
                            <div class="border-t-2 border-gray-900 pt-2">
                                <div class="font-bold text-gray-900 text-base">&nbsp;</div>
                                <div class="text-sm text-gray-700 mt-1">Dean, Graduate School</div>
                            </div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="mt-8 text-center text-sm text-gray-600">
                        Date Generated: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    .print\:hidden {
        display: none !important;
    }
    
    .print\:shadow-none {
        box-shadow: none !important;
    }
    
    @page {
        size: A4 landscape;
        margin: 1.5cm;
    }
    
    /* Ensure table borders print correctly */
    table, th, td {
        border-color: #000 !important;
    }
    
    /* Ensure header colors print */
    .bg-red-900 {
        background-color: #7f1d1d !important;
        color: white !important;
    }
    
    .bg-gray-50 {
        background-color: #f9fafb !important;
    }
    
    /* Remove rounded corners for print */
    * {
        border-radius: 0 !important;
    }
    
    /* Ensure page breaks properly */
    table {
        page-break-inside: auto;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    thead {
        display: table-header-group;
    }
    
    tfoot {
        display: table-footer-group;
    }
}

/* Screen view enhancements */
@media screen {
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
