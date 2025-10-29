<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
  myCourses: { type: Array, default: () => [] },
  joinedCourses: { type: Array, default: () => [] },
});

// Merge and de-dupe courses by id
const allCourses = computed(() => {
  const map = new Map();
  [...props.myCourses, ...props.joinedCourses].forEach(c => map.set(c.id, c));
  return Array.from(map.values());
});

// Modal state for PDF viewing
const showPdfModal = ref(false);
const selectedCourse = ref(null);
const pdfUrl = ref('');

const viewGradeSheet = (course) => {
  selectedCourse.value = course;
  pdfUrl.value = route('teacher.class-record.grade-sheet.pdf', course.id);
  showPdfModal.value = true;
};

const closePdfModal = () => {
  showPdfModal.value = false;
  pdfUrl.value = '';
  selectedCourse.value = null;
};

const downloadPdf = () => {
  if (selectedCourse.value) {
    window.location.href = route('teacher.class-record.grade-sheet.download', selectedCourse.value.id);
  }
};
</script>

<template>
  <Head title="Class Record" />
  <TeacherLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center gap-3">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <div>
            <h1 class="text-2xl font-bold">Class Records</h1>
            <p class="text-red-100 text-sm">View grade sheets for all your courses (owned and joined)</p>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="allCourses.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Courses Found</h3>
        <p class="text-gray-600">Create or join a course to view its class record.</p>
      </div>

      <!-- Courses grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="course in allCourses"
          :key="course.id"
          class="bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden group cursor-pointer"
          @click="viewGradeSheet(course)"
        >
          <!-- Header -->
          <div class="bg-gradient-to-r from-red-900 to-red-800 p-6 text-white">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-lg font-bold group-hover:underline line-clamp-2">{{ course.title }}</h3>
                <p class="text-red-100 text-sm">{{ course.section }}</p>
              </div>
              <svg class="w-6 h-6 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
              </svg>
            </div>
          </div>
          <!-- Body -->
          <div class="p-6">
            <div class="space-y-2 text-sm text-gray-700">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ course.teacher?.first_name }} {{ course.teacher?.last_name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>{{ course.students_count || 0 }} Students</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ course.status }}</span>
              </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between text-sm">
              <span class="text-gray-600">Open Class Record</span>
              <svg class="w-5 h-5 text-red-600 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- PDF Modal -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showPdfModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
          @click="closePdfModal"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="showPdfModal"
              class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl h-[90vh] flex flex-col"
              @click.stop
            >
              <!-- Modal Header -->
              <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-red-900 to-red-700 rounded-t-2xl">
                <div class="flex items-center gap-3 text-white">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div>
                    <h3 class="text-xl font-bold">Grade Sheet</h3>
                    <p class="text-sm text-red-100" v-if="selectedCourse">{{ selectedCourse.title }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    @click="downloadPdf"
                    class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all"
                    title="Download PDF"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden sm:inline">Download</span>
                  </button>
                  <button
                    @click="closePdfModal"
                    class="p-2 hover:bg-white/20 backdrop-blur-sm text-white rounded-lg transition-all"
                    title="Close"
                  >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- PDF Viewer -->
              <div class="flex-1 overflow-hidden bg-gray-100 rounded-b-2xl">
                <iframe
                  v-if="pdfUrl"
                  :src="pdfUrl"
                  class="w-full h-full border-0"
                  title="Grade Sheet PDF"
                />
                <div v-else class="flex items-center justify-center h-full">
                  <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <p class="text-gray-600">Loading PDF...</p>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </div>
  </TeacherLayout>
</template>
