<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue'
import InfoTooltip from '@/Components/InfoTooltip.vue'
import { computed, reactive } from 'vue'

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
</script>

<template>
  <StudentLayout>
    <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">📊 Detailed Progress Tracking</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm text-gray-600 mb-1 flex items-center gap-1">
          📅 Period
          <InfoTooltip 
            title="Period Filter"
            content="Filter your grades by grading period. Choose 'Midterm' to see first-half results, 'Finals' for second-half, or 'All' to view the complete academic period."
            position="top"
          />
        </label>
        <select v-model="state.period" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option v-for="p in props.filterOptions.periods" :key="p">{{ p }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1 flex items-center gap-1">
          📚 Category
          <InfoTooltip 
            title="Category Filter"
            content="Filter by assignment category (Async, Sync, Exams, etc.). This helps you see performance in specific types of work."
            position="top"
          />
        </label>
        <select v-model="state.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option v-for="p in props.filterOptions.categories" :key="p">{{ p }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1 flex items-center gap-1">
          📄 Column
          <InfoTooltip 
            title="Column Filter"
            content="Filter by specific grading columns within categories. This allows detailed analysis of individual assessment types."
            position="top"
          />
        </label>
        <select v-model="state.column" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option v-for="p in props.filterOptions.columns" :key="p">{{ p }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1 flex items-center gap-1">
          ⚡ Status
          <InfoTooltip 
            title="Status Filter"
            content="Filter courses by completion status: Completed (finished courses), In Progress (ongoing), or Pending (not yet started)."
            position="top"
          />
        </label>
        <select v-model="state.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option v-for="p in props.filterOptions.statuses" :key="p">{{ p }}</option>
        </select>
      </div>
    </div>

    <!-- Course Cards -->
    <div class="grid grid-cols-1 gap-6">
      <div v-for="c in filteredCourses" :key="c.id" class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <div class="text-lg font-medium">📘 Course: <span class="font-semibold">{{ c.title }}</span></div>
              <div class="text-sm text-gray-600">👩‍🏫 Instructor: {{ c.instructor.first_name }} {{ c.instructor.last_name }}</div>
              <div class="text-sm text-gray-600">🗓 Semester: {{ c.semester || '—' }}</div>
            </div>
            <div class="text-sm">
              <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full border" :class="{
                'border-green-500 text-green-600': c.status==='completed' && c.remark==='passed',
                'border-red-500 text-red-600': c.status==='completed' && c.remark==='retake',
                'border-yellow-500 text-yellow-600': c.status==='in_progress',
                'border-gray-400 text-gray-600': c.status==='pending',
              }">
                ⚡ Status: {{ statusText(c.status) }} {{ statusIcon(c.status) }}<template v-if="c.status==='completed'"> <span v-if="c.remark==='passed'">✅</span><span v-else-if="c.remark==='retake'">❌</span></template>
              </span>
            </div>
          </div>
        </div>

        <!-- Grade table -->)
        <div class="p-4 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-gray-600 border-b">
                <th class="py-2 pr-4">Period</th>
                <th class="py-2 pr-4">Category</th>
                <th class="py-2 pr-4">Column</th>
                <th class="py-2 pr-4 text-right">Total Items</th>
                <th class="py-2 pr-4 text-right">Score</th>
                <th class="py-2 pr-4 text-right">%</th>
                <th class="py-2 pr-4 text-right">Equivalent Grade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in c.rows.filter(filterRow)" :key="idx" class="border-b last:border-0">
                <td class="py-2 pr-4">{{ r.period }}</td>
                <td class="py-2 pr-4">{{ r.category }}</td>
                <td class="py-2 pr-4">{{ r.column }}</td>
                <td class="py-2 pr-4 text-right">{{ r.total ?? '—' }}</td>
                <td class="py-2 pr-4 text-right">{{ r.score?.toFixed(2) }}</td>
                <td class="py-2 pr-4 text-right">{{ r.percent != null ? (r.percent.toFixed(2) + '%') : '—' }}</td>
                <td class="py-2 pr-4 text-right">—</td>
              </tr>
            </tbody>
          </table>
          <div class="mt-3 text-sm text-gray-700 flex items-center gap-4">
            <div>Weighted Average (Midterm × Finals): <strong>{{ c.weightedAverage != null ? c.weightedAverage.toFixed(2) : '—' }}</strong></div>
            <div>Remarks: <strong><span v-if="c.status==='completed'">{{ c.remark === 'passed' ? '✅ PASSED' : (c.remark === 'retake' ? '❌ RETAKE' : '—') }}</span><span v-else>—</span></strong></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts / Visuals -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Overall Status Donut (text-based) -->
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-medium mb-3 flex items-center gap-2">
          📊 Overall Courses Status
          <InfoTooltip 
            title="Overall Courses Status"
            content="Visual representation of your course completion status. Green indicates passed courses, red shows courses requiring retake, and yellow represents ongoing courses."
            position="right"
          />
        </h3>
        <div class="flex items-center gap-6">
          <div class="w-32 h-32 rounded-full" :style="{ background: pieBackground(chartData.categories) }"></div>
          <div class="space-y-1 text-sm">
            <div>🟢 Passed {{ chartData.statusCounts.passed }}</div>
            <div>🔴 Retake {{ chartData.statusCounts.retake }}</div>
            <div>⏳ In Progress {{ chartData.statusCounts.in_progress }}</div>
          </div>
        </div>
      </div>

      <!-- Weighted Average per Course (bar) -->
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-medium mb-3 flex items-center gap-2">
          📈 Weighted Avg per Course
          <InfoTooltip 
            title="Weighted Average Chart"
            content="Shows your weighted average for each course. The bar length represents your score (0-100%). Green bars indicate passed courses, red shows failed/retake, yellow indicates in-progress courses."
            position="right"
          />
        </h3>
        <div class="space-y-2">
          <div v-for="w in chartData.weighted" :key="w.label" class="text-sm">
            <div class="flex justify-between mb-1">
              <span class="truncate" :title="w.label">{{ w.label }}</span>
              <span>{{ w.value != null ? w.value.toFixed(2) : '—' }} <span v-if="w.status==='completed'">{{ w.remark==='passed' ? '✅' : (w.remark==='retake' ? '❌' : '') }}</span><span v-else>⏳</span></span>
            </div>
            <div class="w-full bg-gray-100 h-2 rounded">
              <div class="h-2 rounded" :class="{
                'bg-green-500': w.remark==='passed',
                'bg-red-500': w.remark==='retake',
                'bg-yellow-500': w.status!=='completed'
              }" :style="{ width: barWidthFromAvg(w.value) }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Midterm vs Finals Grouped Bars -->
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-medium mb-3 flex items-center gap-2">
          📉 Midterm vs Finals
          <InfoTooltip 
            title="Midterm vs Finals Comparison"
            content="Compare your midterm and finals performance for each course. Blue bars represent midterm grades, purple bars show finals grades. This helps identify improvement trends or areas needing attention."
            position="right"
          />
        </h3>
        <div class="space-y-3">
          <div v-for="m in chartData.midFinal" :key="m.label" class="text-sm">
            <div class="mb-1 truncate" :title="m.label">{{ m.label }}</div>
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-gray-100 h-2 rounded">
                <div class="h-2 rounded bg-blue-500" :style="{ width: barWidthFromAvg(m.midterm) }"></div>
              </div>
              <span class="w-12 text-xs text-right">{{ m.midterm != null ? m.midterm.toFixed(0)+'%' : '—' }}</span>
            </div>
            <div class="flex items-center gap-2 mt-1">
              <div class="flex-1 bg-gray-100 h-2 rounded">
                <div class="h-2 rounded bg-purple-500" :style="{ width: barWidthFromAvg(m.finals) }"></div>
              </div>
              <span class="w-12 text-xs text-right">{{ m.finals != null ? m.finals.toFixed(0)+'%' : '—' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Distribution Pie Legend -->
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-medium mb-3 flex items-center gap-2">
          🥧 Category Distribution
          <InfoTooltip 
            title="Category Distribution"
            content="Shows the percentage breakdown of your activities across different categories (Async, Sync, Exams, etc.). This helps you understand where most of your coursework is concentrated."
            position="right"
          />
        </h3>
        <div class="flex items-center gap-6">
          <div class="w-32 h-32 rounded-full" :style="{ background: pieBackground(chartData.categories) }"></div>
          <div class="text-sm space-y-1">
            <div v-for="c in chartData.categories" :key="c.label">{{ c.label }} — {{ c.percent }}%</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Section -->
    <div class="bg-white rounded-lg shadow p-4">
      <h3 class="font-medium mb-3 flex items-center gap-2">
        📝 Overall Summary
        <InfoTooltip 
          title="Overall Academic Summary"
          content="Complete overview of your academic performance across all courses. Shows total courses, completion status, pass/retake counts, and your overall weighted average across all completed courses."
          position="right"
        />
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
        <div class="p-3 rounded bg-gray-50">Total Courses: <strong>{{ overallSummary.total }}</strong></div>
        <div class="p-3 rounded bg-green-50">✅ Completed: <strong>{{ overallSummary.completed }}</strong></div>
        <div class="p-3 rounded bg-yellow-50">⏳ In Progress: <strong>{{ overallSummary.inProgress }}</strong></div>
        <div class="p-3 rounded bg-gray-50">⏸ Pending: <strong>{{ overallSummary.pending }}</strong></div>
        <div class="p-3 rounded bg-green-50">🟢 Courses Passed: <strong>{{ overallSummary.passed }}</strong></div>
        <div class="p-3 rounded bg-red-50">🔴 Courses Retake: <strong>{{ overallSummary.retake }}</strong></div>
      </div>
      <div class="mt-3 text-sm">📊 Overall Weighted Avg: <strong>{{ overallSummary.overallWeightedAvg != null ? overallSummary.overallWeightedAvg.toFixed(2) : '—' }}</strong></div>
    </div>
    </div>
  </StudentLayout>
</template>
