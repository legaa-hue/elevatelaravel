<template>
  <div>
    <h1 class="text-xl font-bold mb-4">Notifications</h1>
    <div v-if="loading" class="text-gray-500">Loading...</div>
    <div v-else-if="notifications.length === 0" class="text-gray-500">No notifications.</div>
    <ul v-else class="space-y-4">
      <li v-for="n in notifications" :key="n.id" class="bg-white rounded shadow p-4 border border-gray-100">
        <div class="flex items-center justify-between mb-1">
          <span class="font-semibold text-blue-700">{{ n.title }}</span>
          <span class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</span>
        </div>
        <div class="mb-1">{{ n.message }}</div>
        <div v-if="n.type === 'feedback' && n.data">
          <div class="text-xs text-gray-600 mt-1">
            <span v-if="n.data.course_title">Course: <b>{{ n.data.course_title }}</b></span>
            <span v-if="n.data.teacher_name" class="ml-2">Teacher: <b>{{ n.data.teacher_name }}</b></span>
          </div>
        </div>
        <div v-if="n.data && n.data.url" class="mt-2">
          <a :href="n.data.url" class="text-blue-600 hover:underline text-xs">View Course</a>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const notifications = ref([]);
const loading = ref(true);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString();
};

onMounted(async () => {
  try {
    const res = await axios.get('/notifications');
    notifications.value = res.data.notifications || [];
  } catch (e) {
    notifications.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
