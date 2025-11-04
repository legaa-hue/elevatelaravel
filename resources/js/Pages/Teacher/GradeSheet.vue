<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
    course: Object,
});

// Print function
const printGradeSheet = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Grade Sheet - ${course.title}`" />

    <TeacherLayout>
        <div class="max-w-[1400px] mx-auto p-6 space-y-6">
            <!-- Action Buttons -->
            <div class="flex items-center justify-between print:hidden">
                <Link
                    :href="route('teacher.courses.gradebook', course.id)"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Gradebook
                </Link>

                <div class="flex items-center gap-3">
                    <a
                        :href="route('teacher.class-record.grade-sheet.pdf', course.id)"
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
                        :href="route('teacher.class-record.grade-sheet.download', course.id)"
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

            <!-- Grade Sheet Preview -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden print:shadow-none">
                <!-- Embedded PDF Preview -->
                <div class="w-full h-[800px] border-2 border-gray-200">
                    <iframe 
                        :src="route('teacher.class-record.grade-sheet.pdf', course.id)"
                        class="w-full h-full"
                        frameborder="0"
                    ></iframe>
                </div>
            </div>

            <!-- Help Text -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 print:hidden">
                <div class="flex gap-3">
                    <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">Grade Sheet Options:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>View PDF:</strong> Opens the grade sheet in a new tab for viewing</li>
                            <li><strong>Download PDF:</strong> Downloads the grade sheet as a PDF file</li>
                            <li><strong>Print:</strong> Prints the current view directly from your browser</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    
    .print\:shadow-none {
        box-shadow: none !important;
    }
}
</style>
