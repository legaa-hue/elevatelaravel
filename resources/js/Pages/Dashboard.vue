<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-medium leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome Card -->
                <div class="overflow-hidden bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="h-2 bg-red-900"></div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-medium mb-2">Welcome to ElevateGS</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Your Learning Management System for USANT GradSchool
                        </p>
                    </div>
                </div>

                <!-- Quick Stats Grid -->
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-yellow-500"></div>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-yellow-50 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-medium text-gray-900 dark:text-gray-100">0</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Courses</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-red-900"></div>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-50 dark:bg-red-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-red-900 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-medium text-gray-900 dark:text-gray-100">0</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Completed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-yellow-500"></div>
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-yellow-50 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-medium text-gray-900 dark:text-gray-100">0</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">In Progress</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
