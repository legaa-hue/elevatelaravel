<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    email: props.user.email || '',
    role: '',
});

const submit = () => {
    form.post(route('auth.complete-profile'), {
        onFinish: () => {},
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Complete Your Profile" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-medium text-gray-900">Complete Your Profile</h2>
            <p class="mt-2 text-sm text-gray-600">
                Just one more step to get started with ElevateGS
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Role Selection - Required -->
            <div class="mb-6">
                <InputLabel value="I am a:" class="mb-3 text-base font-medium" />
                <p class="text-sm text-gray-600 mb-3">
                    Please select your role to continue
                </p>
                
                <div class="grid grid-cols-2 gap-3">
                    <label
                        :class="[
                            'flex items-center justify-center p-6 border-2 rounded-lg cursor-pointer transition-all',
                            form.role === 'teacher' 
                                ? 'border-red-900 bg-red-50 ring-2 ring-red-900' 
                                : 'border-gray-300 hover:border-red-900'
                        ]"
                    >
                        <input
                            type="radio"
                            name="role"
                            value="teacher"
                            v-model="form.role"
                            class="sr-only"
                            required
                        />
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-3" :class="form.role === 'teacher' ? 'text-red-900' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-base font-medium block" :class="form.role === 'teacher' ? 'text-red-900' : 'text-gray-700'">Teacher</span>
                            <span class="text-xs text-gray-500 mt-1 block">Create and manage courses</span>
                        </div>
                    </label>

                    <label
                        :class="[
                            'flex items-center justify-center p-6 border-2 rounded-lg cursor-pointer transition-all',
                            form.role === 'student' 
                                ? 'border-yellow-600 bg-yellow-50 ring-2 ring-yellow-600' 
                                : 'border-gray-300 hover:border-yellow-600'
                        ]"
                    >
                        <input
                            type="radio"
                            name="role"
                            value="student"
                            v-model="form.role"
                            class="sr-only"
                            required
                        />
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-3" :class="form.role === 'student' ? 'text-yellow-600' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="text-base font-medium block" :class="form.role === 'student' ? 'text-yellow-600' : 'text-gray-700'">Student</span>
                            <span class="text-xs text-gray-500 mt-1 block">Enroll and learn</span>
                        </div>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <!-- Pre-filled Information from Google -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Your Information</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="first_name" value="First Name" class="text-xs" />
                        <TextInput
                            id="first_name"
                            type="text"
                            class="mt-1 block w-full bg-white"
                            v-model="form.first_name"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.first_name" />
                    </div>

                    <div>
                        <InputLabel for="last_name" value="Last Name" class="text-xs" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="mt-1 block w-full bg-white"
                            v-model="form.last_name"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="email" value="Email" class="text-xs" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full bg-gray-100"
                        v-model="form.email"
                        disabled
                    />
                    <p class="mt-1 text-xs text-gray-500">Email cannot be changed</p>
                </div>
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing || !form.role }"
                    :disabled="form.processing || !form.role"
                >
                    Continue to Dashboard
                </PrimaryButton>
            </div>

            <p class="mt-4 text-xs text-center text-gray-500">
                By continuing, you agree to ElevateGS Terms of Service and Privacy Policy
            </p>
        </form>
    </GuestLayout>
</template>
