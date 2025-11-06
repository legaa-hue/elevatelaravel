<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue'
import InfoTooltip from '@/Components/InfoTooltip.vue'
import { computed, reactive, onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({
      period: 'All',
      category: 'All',
      column: 'All',
      status: 'All',
    })
  },
  filterOptions: {
    type: Object,
    default: () => ({
      periods: ['All', 'Midterm', 'Finals'],
      categories: ['All'],
      columns: ['All'],
      statuses: ['All', 'Completed', 'In Progress', 'Pending'],
    })
  },
  courses: {
    type: Array,
    default: () => []
  }
})

const state = reactive({
  period: props.filters?.period || 'All',
  category: props.filters?.category || 'All',
  column: props.filters?.column || 'All',
  status: props.filters?.status || 'All',
})

const courses = computed(() => props.courses || [])

const statusIcon = (status) => {
  switch (status) {
    case 'completed': return '✅'
    case 'in_progress': return '⏳'
    case 'pending': return '⏸️'
    default: return '⏸️'
  }
}

const statusText = (status) => {
  switch (status) {
    case 'completed': return 'Completed'
    case 'in_progress': return 'In Progress'
    case 'pending': return 'Pending'
    default: return status
  }
}

const filteredCourses = computed(() => {
  return courses.value.filter(c => {
    if (state.status !== 'All') {
      const s = state.status.toLowerCase().replace(' ', '_')
      if (c.status !== (s === 'in_progress' ? 'in_progress' : s)) return false
    }
    return true
  })
})

function filterRow(r) {
  if (state.period !== 'All' && r.period !== state.period) return false
  if (state.category !== 'All' && r.category !== state.category) return false
  if (state.column !== 'All' && r.column !== state.column) return false
  return true
}

const chartData = computed(() => {
  const list = filteredCourses.value
  const statusCounts = { passed: 0, retake: 0, in_progress: 0 }
  const weighted = []
  const midFinal = []
  const categoryCounts = {}

  for (const c of list) {
    if (c.status === 'completed') {
      if (c.remark === 'passed') statusCounts.passed++
      else if (c.remark === 'retake') statusCounts.retake++
    } else if (c.status === 'in_progress') {
      statusCounts.in_progress++
    }
    weighted.push({ label: c.title, value: c.weightedAverage, status: c.status, remark: c.remark })
    midFinal.push({ label: c.title, midterm: c.periodPercents.midterm, finals: c.periodPercents.finals })
    for (const r of c.rows) {
      if (!filterRow(r)) continue
      if (!categoryCounts[r.category]) categoryCounts[r.category] = 0
      categoryCounts[r.category] += 1
    }
  }

  const totalCat = Object.values(categoryCounts).reduce((a,b)=>a+b,0) || 1
  const categories = Object.entries(categoryCounts).map(([label, count])=>({ label, percent: Math.round(count/totalCat*100) }))

  return { statusCounts, weighted, midFinal, categories }
})

const overallSummary = computed(() => {
  const list = filteredCourses.value
  const total = list.length
  const completed = list.filter(c => c.status==='completed')
  const inProgress = list.filter(c => c.status==='in_progress')
  const pending = list.filter(c => c.status==='pending')
  const passed = completed.filter(c => c.remark==='passed')
  const retake = completed.filter(c => c.remark==='retake')
  const avg = completed.length ? (completed.reduce((s,c)=> s + (c.weightedAverage||0),0)/completed.length) : null
  return { total, completed: completed.length, inProgress: inProgress.length, pending: pending.length, passed: passed.length, retake: retake.length, overallWeightedAvg: avg }
})

function barWidthFromAvg(avg) {
  if (avg == null) return '0%'
  // Map 0-100 percent to width 0-100%
  const clamped = Math.max(0, Math.min(100, avg))
  return clamped + '%'
}

function pieBackground(categories) {
  // Build a CSS conic-gradient pie from categories
  if (!categories.length) return 'conic-gradient(#e5e7eb 0 360deg)'
  const colors = ['#10b981','#ef4444','#f59e0b','#3b82f6','#8b5cf6','#14b8a6','#f97316']
  let start = 0
  const parts = []
  categories.forEach((c, i) => {
    const end = start + (c.percent/100)*360
    parts.push(`${colors[i % colors.length]} ${start}deg ${end}deg`)
    start = end
  })
  if (start < 360) parts.push(`#e5e7eb ${start}deg 360deg`)
  return `conic-gradient(${parts.join(',')})`
}

// --- Auto-refresh logic ---
// Keep the student progress page fresh when data changes on the server (e.g., teacher syncs/deletes).
// We avoid heavy realtime; use a few low-cost triggers:
// - When the tab regains focus (visibilitychange)
// - When the browser comes online
// - Gentle polling while the tab is visible
const lastReloadAt = ref(0)
let refreshTimer = null
function handleClassworksUpdated() { safeReloadCourses(0) }

function safeReloadCourses(throttleMs = 10000) {
  const now = Date.now()
  if (!navigator.onLine) return
  if (document.hidden) return
  if (now - lastReloadAt.value < throttleMs) return
  try {
    router.reload({ only: ['courses'] })
    lastReloadAt.value = now
  } catch (e) {
    // ignore
  }
}

function onVisibilityChange() {
  if (!document.hidden) {
    safeReloadCourses(5000)
  }
}

function onCameOnline() {
  safeReloadCourses(0)
}

onMounted(() => {
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('online', onCameOnline)
  // Gentle polling every 45s while visible
  refreshTimer = setInterval(() => {
    if (!document.hidden) safeReloadCourses(20000)
  }, 45000)

  // If the same-tab just synced classworks (unlikely for student), still listen for it
  // to refresh quickly without waiting for the interval.
  window.addEventListener('app:classworks-updated', handleClassworksUpdated)
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', onVisibilityChange)
  window.removeEventListener('online', onCameOnline)
  window.removeEventListener('app:classworks-updated', handleClassworksUpdated)
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>

<template>
  <StudentLayout>
    <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-blue-100 border-2 border-blue-300 rounded-lg flex items-center justify-center">
          <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Detailed Progress Tracking</h1>
          <p class="text-sm text-gray-500">Monitor your academic performance across all courses</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
      <div class="flex items-center gap-2 mb-4">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Period
            <InfoTooltip 
              title="Period Filter"
              content="Filter your grades by grading period. Choose 'Midterm' to see first-half results, 'Finals' for second-half, or 'All' to view the complete academic period."
              position="top"
            />
          </label>
          <select v-model="state.period" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option v-for="p in props.filterOptions.periods" :key="p">{{ p }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
            Category
            <InfoTooltip 
              title="Category Filter"
              content="Filter by assignment category (Async, Sync, Exams, etc.). This helps you see performance in specific types of work."
              position="top"
            />
          </label>
          <select v-model="state.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option v-for="p in props.filterOptions.categories" :key="p">{{ p }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Column
            <InfoTooltip 
              title="Column Filter"
              content="Filter by specific grading columns within categories. This allows detailed analysis of individual assessment types."
              position="top"
            />
          </label>
          <select v-model="state.column" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option v-for="p in props.filterOptions.columns" :key="p">{{ p }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Status
            <InfoTooltip 
              title="Status Filter"
              content="Filter courses by completion status: Completed (finished courses), In Progress (ongoing), or Pending (not yet started)."
              position="top"
            />
          </label>
          <select v-model="state.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option v-for="p in props.filterOptions.statuses" :key="p">{{ p }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Course Cards -->
    <div v-if="filteredCourses.length === 0" class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-12">
      <div class="text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">No Courses Found</h3>
        <p class="mt-2 text-sm text-gray-500">You don't have any enrolled courses yet, or no courses match your current filters.</p>
        <p class="mt-1 text-xs text-gray-400">Try adjusting your filters or join a course to get started.</p>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-6">
      <div v-for="c in filteredCourses" :key="c.id" class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
        <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 border-2 border-blue-300 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">{{ c.title }}</h3>
                  <div class="mt-2 space-y-1">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Instructor: {{ c.instructor.first_name }} {{ c.instructor.last_name }}
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      Semester: {{ c.semester || 'Not specified' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex-shrink-0">
              <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border-2 font-semibold text-sm" :class="{
                'border-green-400 bg-green-50 text-green-700': c.status==='completed' && c.remark==='passed',
                'border-red-400 bg-red-50 text-red-700': c.status==='completed' && c.remark==='retake',
                'border-yellow-400 bg-yellow-50 text-yellow-700': c.status==='in_progress',
                'border-gray-400 bg-gray-50 text-gray-700': c.status==='pending',
              }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="c.status==='completed' && c.remark==='passed'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  <path v-else-if="c.status==='completed' && c.remark==='retake'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  <path v-else-if="c.status==='in_progress'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                {{ statusText(c.status) }}
                <span v-if="c.status==='completed' && c.remark==='passed'" class="text-xs">(Passed)</span>
                <span v-else-if="c.status==='completed' && c.remark==='retake'" class="text-xs">(Retake)</span>
              </span>
            </div>
          </div>
        </div>

        <!-- Grade table -->
        <div class="p-5">
          <div v-if="c.rows.filter(filterRow).length === 0" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm">No grade data available for this course</p>
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm border border-gray-200 rounded-lg">
              <thead class="bg-gray-50">
                <tr class="text-left text-gray-700 border-b-2 border-gray-300">
                  <th class="py-3 px-4 font-semibold">Period</th>
                  <th class="py-3 px-4 font-semibold">Category</th>
                  <th class="py-3 px-4 font-semibold">Column</th>
                  <th class="py-3 px-4 text-right font-semibold">Total Items</th>
                  <th class="py-3 px-4 text-right font-semibold">Score</th>
                  <th class="py-3 px-4 text-right font-semibold">Percentage</th>
                  <th class="py-3 px-4 text-right font-semibold">Equivalent</th>
                </tr>
              </thead>
              <tbody class="bg-white">
                <tr v-for="(r, idx) in c.rows.filter(filterRow)" :key="idx" 
                    class="border-b border-gray-200 last:border-0 hover:bg-blue-50 transition">
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                      {{ r.period }}
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium">
                      {{ r.category }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-gray-900">{{ r.column }}</td>
                  <td class="py-3 px-4 text-right font-semibold text-gray-700">{{ r.total ?? '—' }}</td>
                  <td class="py-3 px-4 text-right font-bold text-blue-600">{{ r.score?.toFixed(2) }}</td>
                  <td class="py-3 px-4 text-right">
                    <span v-if="r.percent != null" class="inline-flex items-center gap-1 px-2 py-1 rounded font-semibold text-xs"
                          :class="{
                            'bg-green-100 text-green-700': r.percent >= 90,
                            'bg-blue-100 text-blue-700': r.percent >= 75 && r.percent < 90,
                            'bg-yellow-100 text-yellow-700': r.percent >= 60 && r.percent < 75,
                            'bg-red-100 text-red-700': r.percent < 60
                          }">
                      {{ r.percent.toFixed(2) }}%
                    </span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="py-3 px-4 text-right text-gray-500">—</td>
                </tr>
              </tbody>
            </table>
            
            <!-- Summary Footer -->
            <div class="mt-5 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg">
              <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-700">Weighted Average (Midterm × Finals)</p>
                    <p class="text-2xl font-bold text-blue-700">{{ c.weightedAverage != null ? c.weightedAverage.toFixed(2) : 'N/A' }}</p>
                  </div>
                </div>
                <div v-if="c.status==='completed'" class="flex items-center gap-2">
                  <span class="text-sm font-medium text-gray-700">Final Remarks:</span>
                  <span v-if="c.remark === 'passed'" class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 border-2 border-green-400 text-green-700 rounded-lg font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    PASSED
                  </span>
                  <span v-else-if="c.remark === 'retake'" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 border-2 border-red-400 text-red-700 rounded-lg font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    RETAKE
                  </span>
                  <span v-else class="text-gray-500">—</span>
                </div>
                <div v-else class="text-sm text-gray-500 italic">Course in progress...</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts / Visuals -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Overall Status Donut -->
      <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <h3 class="font-semibold text-gray-900">Overall Courses Status</h3>
          <InfoTooltip 
            title="Overall Courses Status"
            content="Visual representation of your course completion status. Green indicates passed courses, red shows courses requiring retake, and yellow represents ongoing courses."
            position="right"
          />
        </div>
        <div class="flex items-center justify-center gap-8">
          <div class="w-32 h-32 rounded-full border-4 border-gray-200 shadow-inner" :style="{ background: pieBackground(chartData.categories) }"></div>
          <div class="space-y-2">
            <div class="flex items-center gap-2 text-sm">
              <div class="w-4 h-4 bg-green-500 rounded-full border-2 border-green-600"></div>
              <span class="text-gray-700">Passed:</span>
              <span class="font-bold text-green-600">{{ chartData.statusCounts.passed }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <div class="w-4 h-4 bg-red-500 rounded-full border-2 border-red-600"></div>
              <span class="text-gray-700">Retake:</span>
              <span class="font-bold text-red-600">{{ chartData.statusCounts.retake }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <div class="w-4 h-4 bg-yellow-500 rounded-full border-2 border-yellow-600"></div>
              <span class="text-gray-700">In Progress:</span>
              <span class="font-bold text-yellow-600">{{ chartData.statusCounts.in_progress }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Weighted Average per Course -->
      <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="font-semibold text-gray-900">Weighted Average per Course</h3>
          <InfoTooltip 
            title="Weighted Average Chart"
            content="Shows your weighted average for each course. The bar length represents your score (0-100%). Green bars indicate passed courses, red shows failed/retake, yellow indicates in-progress courses."
            position="right"
          />
        </div>
        <div v-if="chartData.weighted.length === 0" class="text-center py-8 text-gray-400">
          <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
          </svg>
          <p class="mt-2 text-sm">No course data available</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="w in chartData.weighted" :key="w.label" class="text-sm">
            <div class="flex justify-between items-center mb-1.5">
              <span class="truncate font-medium text-gray-700" :title="w.label">{{ w.label }}</span>
              <span class="flex items-center gap-1.5 font-bold">
                <span :class="{
                  'text-green-600': w.remark==='passed',
                  'text-red-600': w.remark==='retake',
                  'text-yellow-600': w.status!=='completed',
                  'text-gray-500': w.value == null
                }">
                  {{ w.value != null ? w.value.toFixed(2) : 'N/A' }}
                </span>
                <svg v-if="w.status==='completed' && w.remark==='passed'" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="w.status==='completed' && w.remark==='retake'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="w.status!=='completed'" class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </span>
            </div>
            <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden border border-gray-300">
              <div class="h-3 rounded-full transition-all duration-500" :class="{
                'bg-gradient-to-r from-green-400 to-green-600': w.remark==='passed',
                'bg-gradient-to-r from-red-400 to-red-600': w.remark==='retake',
                'bg-gradient-to-r from-yellow-400 to-yellow-600': w.status!=='completed'
              }" :style="{ width: barWidthFromAvg(w.value) }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Midterm vs Finals Comparison -->
      <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <h3 class="font-semibold text-gray-900">Midterm vs Finals</h3>
          <InfoTooltip 
            title="Midterm vs Finals Comparison"
            content="Compare your midterm and finals performance for each course. Blue bars represent midterm grades, purple bars show finals grades. This helps identify improvement trends or areas needing attention."
            position="right"
          />
        </div>
        <div v-if="chartData.midFinal.length === 0" class="text-center py-8 text-gray-400">
          <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
          </svg>
          <p class="mt-2 text-sm">No comparison data available</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="m in chartData.midFinal" :key="m.label" class="border border-gray-200 rounded-lg p-3 hover:border-purple-300 transition">
            <div class="mb-2 font-medium text-gray-900 truncate" :title="m.label">{{ m.label }}</div>
            <div class="space-y-2">
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 w-20">
                  <div class="w-3 h-3 bg-blue-500 rounded-full border-2 border-blue-600"></div>
                  <span class="text-xs font-medium text-gray-600">Midterm</span>
                </div>
                <div class="flex-1 bg-gray-200 h-3 rounded-full overflow-hidden border border-gray-300">
                  <div class="h-3 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all duration-500" :style="{ width: barWidthFromAvg(m.midterm) }"></div>
                </div>
                <span class="w-14 text-xs text-right font-bold text-blue-600">{{ m.midterm != null ? m.midterm.toFixed(0)+'%' : 'N/A' }}</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 w-20">
                  <div class="w-3 h-3 bg-purple-500 rounded-full border-2 border-purple-600"></div>
                  <span class="text-xs font-medium text-gray-600">Finals</span>
                </div>
                <div class="flex-1 bg-gray-200 h-3 rounded-full overflow-hidden border border-gray-300">
                  <div class="h-3 rounded-full bg-gradient-to-r from-purple-400 to-purple-600 transition-all duration-500" :style="{ width: barWidthFromAvg(m.finals) }"></div>
                </div>
                <span class="w-14 text-xs text-right font-bold text-purple-600">{{ m.finals != null ? m.finals.toFixed(0)+'%' : 'N/A' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Distribution -->
      <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
          <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
          </svg>
          <h3 class="font-semibold text-gray-900">Category Distribution</h3>
          <InfoTooltip 
            title="Category Distribution"
            content="Shows the percentage breakdown of your activities across different categories (Async, Sync, Exams, etc.). This helps you understand where most of your coursework is concentrated."
            position="right"
          />
        </div>
        <div v-if="chartData.categories.length === 0" class="text-center py-8 text-gray-400">
          <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
          </svg>
          <p class="mt-2 text-sm">No category data available</p>
        </div>
        <div v-else class="flex items-center justify-center gap-8">
          <div class="w-32 h-32 rounded-full border-4 border-gray-200 shadow-inner" :style="{ background: pieBackground(chartData.categories) }"></div>
          <div class="space-y-2">
            <div v-for="(c, idx) in chartData.categories" :key="c.label" class="flex items-center gap-2 text-sm">
              <div class="w-4 h-4 rounded-sm border-2" :style="{ 
                backgroundColor: ['#10b981','#ef4444','#f59e0b','#3b82f6','#8b5cf6','#14b8a6','#f97316'][idx % 7],
                borderColor: ['#059669','#dc2626','#d97706','#2563eb','#7c3aed','#0f766e','#ea580c'][idx % 7]
              }"></div>
              <span class="text-gray-700">{{ c.label }}:</span>
              <span class="font-bold text-gray-900">{{ c.percent }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Section -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg shadow-sm p-6">
      <div class="flex items-center gap-2 mb-5">
        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-bold text-gray-900">Overall Academic Summary</h3>
        <InfoTooltip 
          title="Overall Academic Summary"
          content="Complete overview of your academic performance across all courses. Shows total courses, completion status, pass/retake counts, and your overall weighted average across all completed courses."
          position="right"
        />
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-5">
        <!-- Total Courses -->
        <div class="bg-white border-2 border-gray-300 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="text-xs font-medium text-gray-600">Total Courses</span>
          </div>
          <div class="text-2xl font-bold text-gray-900">{{ overallSummary.total }}</div>
        </div>

        <!-- Completed -->
        <div class="bg-white border-2 border-green-300 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-medium text-green-600">Completed</span>
          </div>
          <div class="text-2xl font-bold text-green-700">{{ overallSummary.completed }}</div>
        </div>

        <!-- In Progress -->
        <div class="bg-white border-2 border-yellow-300 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-medium text-yellow-600">In Progress</span>
          </div>
          <div class="text-2xl font-bold text-yellow-700">{{ overallSummary.inProgress }}</div>
        </div>

        <!-- Pending -->
        <div class="bg-white border-2 border-gray-300 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <span class="text-xs font-medium text-gray-600">Pending</span>
          </div>
          <div class="text-2xl font-bold text-gray-700">{{ overallSummary.pending }}</div>
        </div>

        <!-- Passed -->
        <div class="bg-white border-2 border-green-400 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-medium text-green-600">Passed</span>
          </div>
          <div class="text-2xl font-bold text-green-700">{{ overallSummary.passed }}</div>
        </div>

        <!-- Retake -->
        <div class="bg-white border-2 border-red-300 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-medium text-red-600">Retake</span>
          </div>
          <div class="text-2xl font-bold text-red-700">{{ overallSummary.retake }}</div>
        </div>
      </div>

      <!-- Overall Weighted Average -->
      <div class="bg-white border-2 border-blue-300 rounded-lg p-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-100 border-2 border-blue-400 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Overall Weighted Average</p>
              <p class="text-xs text-gray-500">Across all completed courses</p>
            </div>
          </div>
          <div class="text-right">
            <div class="text-4xl font-bold text-blue-600">
              {{ overallSummary.overallWeightedAvg != null ? overallSummary.overallWeightedAvg.toFixed(2) : 'N/A' }}
            </div>
            <div v-if="overallSummary.overallWeightedAvg != null" class="text-xs text-gray-500 mt-1">
              {{ overallSummary.overallWeightedAvg >= 75 ? 'Passing Grade' : 'Below Passing' }}
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </StudentLayout>
</template>
