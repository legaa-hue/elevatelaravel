<script setup>
// Use Heroicons outline icons for a modern look
import { AcademicCapIcon, ChartBarIcon, UserGroupIcon, CheckCircleIcon, XCircleIcon, ArrowTrendingUpIcon, CalendarIcon, DocumentArrowDownIcon, InboxIcon, ExclamationTriangleIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { ref, watch, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  courses: Array,
});

const selectedCourseId = ref('');
const dateFrom = ref('');
const dateTo = ref('');

const loading = ref(false);
const errorMsg = ref('');

const overview = ref(null);
const distribution = ref([]);
const students = ref([]);
const insights = ref(null);

const selectedCourse = computed(() => props.courses?.find(c => c.id == selectedCourseId.value));

const fetchData = async () => {
  if (!selectedCourseId.value) return;
  loading.value = true; errorMsg.value = '';
  try {
    const res = await axios.get('/teacher/reports/data', {
      params: {
        course_id: selectedCourseId.value,
        from: dateFrom.value || undefined,
        to: dateTo.value || undefined,
      }
    });
    overview.value = res.data.overview;
    distribution.value = res.data.distribution || [];
    students.value = res.data.students || [];
    insights.value = res.data.insights || {};
  } catch (e) {
    console.error(e);
    errorMsg.value = e.response?.data?.message || e.message;
  } finally {
    loading.value = false;
  }
};

watch([selectedCourseId, dateFrom, dateTo], fetchData);

onMounted(() => {
  if (props.courses && props.courses.length > 0) {
    selectedCourseId.value = props.courses[0].id;
  }
});

const sendFeedback = async (student) => {
  const msg = prompt(`Message for ${student.name}:`, 'Great improvement in quizzes!');
  if (!msg) return;
  await axios.post('/teacher/reports/notify-feedback', {
    student_id: student.id,
    course_id: selectedCourseId.value,
    message: msg,
  });
  alert('Feedback sent');
};

const notifyFailing = async (student) => {
  const msg = prompt(`Alert message for ${student.name}:`, 'You are below passing grade.');
  await axios.post('/teacher/reports/notify-failing', {
    student_id: student.id,
    course_id: selectedCourseId.value,
    message: msg,
  });
  alert('Failing alert sent');
};

const exportReport = async (format) => {
  if (!selectedCourseId.value) return;
  const params = new URLSearchParams({ course_id: selectedCourseId.value, format });
  if (dateFrom.value) params.append('from', dateFrom.value);
  if (dateTo.value) params.append('to', dateTo.value);

  const url = `/teacher/reports/export?${params.toString()}`;
  if (format === 'csv') {
    window.location.href = url;
  } else {
    // pdf
    window.location.href = url;
  }
};
</script>

<template>
  <TeacherLayout>
    <Head title="Reports" />
    <div class="space-y-6">
    <!-- Header / Filters -->
    <div class="bg-white rounded-lg shadow p-6 flex flex-col md:flex-row gap-6 items-center justify-between border border-gray-100">
      <div class="flex items-center gap-3 w-full md:w-auto">
        <InboxIcon class="w-6 h-6 text-blue-600" />
        <label class="text-sm text-gray-700 font-medium">Course</label>
        <select v-model="selectedCourseId" class="px-3 py-2 border rounded w-56 focus:ring-2 focus:ring-blue-500">
          <option value="" disabled>Select course</option>
          <option v-for="c in props.courses" :key="c.id" :value="c.id">{{ c.title }}<span v-if="c.section"> — {{ c.section }}</span></option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <CalendarIcon class="w-6 h-6 text-gray-500" />
        <input type="date" v-model="dateFrom" class="px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" />
        <span class="text-gray-400">-</span>
        <input type="date" v-model="dateTo" class="px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="flex items-center gap-2">
        <button @click="exportReport('pdf')" class="flex items-center gap-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded shadow-sm transition">
          <DocumentArrowDownIcon class="w-5 h-5" /> PDF
        </button>
        <button @click="exportReport('csv')" class="flex items-center gap-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded shadow-sm transition">
          <DocumentArrowDownIcon class="w-5 h-5" /> Excel
        </button>
      </div>
    </div>

    <div v-if="!selectedCourseId" class="p-8 text-center bg-white rounded shadow">
      📄 No report available. Select a course (🏷️) or date range (📅) to generate a report.
    </div>

    <div v-else>
      <!-- Overview Cards -->
      <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold flex items-center gap-2"><AcademicCapIcon class="w-6 h-6 text-blue-700" /> CLASS OVERVIEW</h2>
        </div>
        <div v-if="loading">Loading…</div>
        <div v-else-if="errorMsg" class="text-red-600">{{ errorMsg }}</div>
        <div v-else-if="overview" class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="p-4 rounded border flex flex-col items-center bg-blue-50">
            <UserGroupIcon class="w-7 h-7 text-blue-600 mb-1" />
            <div class="text-gray-700 text-sm">Total Students</div>
            <div class="text-2xl font-bold">{{ overview.totalStudents }}</div>
          </div>
          <div class="p-4 rounded border flex flex-col items-center bg-green-50">
            <ChartBarIcon class="w-7 h-7 text-green-600 mb-1" />
            <div class="text-gray-700 text-sm">Average Grade</div>
            <div class="text-2xl font-bold">{{ overview.averageGrade }}%</div>
          </div>
          <div class="p-4 rounded border flex flex-col items-center bg-emerald-50">
            <CheckCircleIcon class="w-7 h-7 text-emerald-600 mb-1" />
            <div class="text-gray-700 text-sm">Passed Students</div>
            <div class="text-2xl font-bold">{{ overview.passed.count }} ({{ overview.passed.percent }}%)</div>
          </div>
          <div class="p-4 rounded border flex flex-col items-center bg-rose-50">
            <XCircleIcon class="w-7 h-7 text-rose-600 mb-1" />
            <div class="text-gray-700 text-sm">Failed Students</div>
            <div class="text-2xl font-bold">{{ overview.failed.count }} ({{ overview.failed.percent }}%)</div>
          </div>
          <div class="p-4 rounded border flex flex-col items-center bg-yellow-50">
            <ArrowTrendingUpIcon class="w-7 h-7 text-yellow-600 mb-1" />
            <div class="text-gray-700 text-sm">Trend</div>
            <div class="text-2xl font-bold">{{ overview.trend }}</div>
          </div>
        </div>
      </div>

      <!-- Grade Distribution -->
      <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold flex items-center gap-2"><ChartBarIcon class="w-6 h-6 text-indigo-700" /> GRADE DISTRIBUTION</h2>
        </div>
        <div class="space-y-2">
          <div v-for="row in distribution" :key="row.range" class="flex items-center gap-3">
            <div class="w-24 text-sm text-gray-600">{{ row.range }}</div>
            <div class="flex-1 h-4 bg-gray-100 rounded">
              <div class="h-4 bg-blue-500 rounded" :style="{ width: Math.min(row.percent, 100) + '%' }"></div>
            </div>
            <div class="w-16 text-right text-sm">{{ row.percent }}%</div>
          </div>
        </div>
      </div>

      <!-- Student Performance Table -->
      <div class="bg-white rounded-lg shadow p-6 overflow-x-auto border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold flex items-center gap-2"><UserGroupIcon class="w-6 h-6 text-blue-700" /> STUDENT PERFORMANCE</h2>
        </div>
        <table class="min-w-full">
          <thead>
            <tr class="text-left text-sm text-gray-600 border-b">
              <th class="py-2 px-3">Student Name</th>
              <th class="py-2 px-3">Course</th>
              <th class="py-2 px-3" title="Weighted average calculated from all categories">Weighted Avg</th>
              <th class="py-2 px-3" title="Equivalent grade based on your grading scale">Grade</th>
              <th class="py-2 px-3" title="Automatically generated or teacher added">Remarks</th>
              <th class="py-2 px-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in students" :key="s.id" class="border-b hover:bg-gray-50">
              <td class="py-2 px-3">{{ s.name }}</td>
              <td class="py-2 px-3">{{ selectedCourse?.title }}</td>
              <td class="py-2 px-3">{{ s.weightedAvg }}%</td>
              <td class="py-2 px-3">{{ s.grade }}</td>
              <td class="py-2 px-3">{{ s.remarks }}</td>
              <td class="py-2 px-3 flex gap-2">
                <button @click="sendFeedback(s)" class="px-2 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded flex items-center gap-1" title="Write a message to student">
                  <EnvelopeIcon class="w-4 h-4" />
                </button>
                <button @click="notifyFailing(s)" class="px-2 py-1 text-sm bg-yellow-600 hover:bg-yellow-700 text-white rounded flex items-center gap-1" title="Send automated failing alert">
                  <ExclamationTriangleIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="students.length === 0">
              <td colspan="6" class="py-4 text-center text-gray-500">No data</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Remarks / Insights -->
      <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold flex items-center gap-2"><InboxIcon class="w-6 h-6 text-indigo-700" /> REMARKS & INSIGHTS</h2>
        </div>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
          <li><CheckCircleIcon class="inline w-4 h-4 text-emerald-600 mr-1" /> {{ overview?.passed?.percent || 0 }}% of students passed.</li>
          <li><XCircleIcon class="inline w-4 h-4 text-rose-600 mr-1" /> {{ students.filter(s=>s.remarks==='Failed').length }} students need improvement.</li>
          <li><ChartBarIcon class="inline w-4 h-4 text-green-600 mr-1" /> Average weighted grade: {{ overview?.averageGrade || 0 }}%</li>
          <li v-if="insights?.summary?.weakCategory"><ExclamationTriangleIcon class="inline w-4 h-4 text-yellow-600 mr-1" /> Most common weak category: {{ insights.summary.weakCategory }}</li>
        </ul>
      </div>

      <!-- Export Section -->
      <div class="bg-white rounded-lg shadow p-6 flex items-center justify-between border border-gray-100">
        <div>
          <h2 class="text-lg font-semibold flex items-center gap-2"><DocumentArrowDownIcon class="w-6 h-6 text-gray-700" /> EXPORT REPORT</h2>
          <p class="text-sm text-gray-500">Download this report for record-keeping</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="exportReport('pdf')" class="flex items-center gap-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded shadow-sm transition">
            <DocumentArrowDownIcon class="w-5 h-5" /> PDF
          </button>
          <button @click="exportReport('csv')" class="flex items-center gap-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded shadow-sm transition">
            <DocumentArrowDownIcon class="w-5 h-5" /> Excel
          </button>
        </div>
      </div>
    </div>
    </div>
  </TeacherLayout>
</template>
