<script setup>
// Example placeholder component to satisfy build; not used in production routes.
import { Head } from '@inertiajs/vue3';
const title = 'Offline Course View (Example)';
</script>

<template>
	<Head :title="title" />
	<div class="p-6 text-gray-700">
		<h1 class="text-2xl font-semibold mb-2">{{ title }}</h1>
		<p class="text-sm text-gray-500">This is a placeholder example component. It exists to keep the build healthy.</p>
	</div>
  
</template>

<style scoped>
/* Minimal styling only */
</style>
