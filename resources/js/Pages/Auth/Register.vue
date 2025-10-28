<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const handleGoogleSignUp = () => {
    // TODO: Implement Google Sign-Up
    console.log('Google Sign-Up clicked');
    // window.location.href = route('auth.google.register');
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-medium text-gray-900">Create Your Account</h2>
            <p class="mt-2 text-sm text-gray-600">Join ElevateGS to start learning</p>
        </div>

        <!-- Google Sign-Up Button -->
        <button
            @click="handleGoogleSignUp"
            type="button"
            class="w-full flex items-center justify-center gap-3 rounded border-2 border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-900 focus:ring-offset-2 transition-colors"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign up with Google
        </button>

        <div class="my-6 flex items-center">
            <div class="flex-1 border-t border-gray-300"></div>
            <span class="px-4 text-sm text-gray-500">OR</span>
            <div class="flex-1 border-t border-gray-300"></div>
        </div>

        <form @submit.prevent="submit">
            <!-- Role Selection -->
            <div class="mb-6">
                <InputLabel value="I am a:" class="mb-3" />
                <div class="grid grid-cols-2 gap-3">
                    <label
                        :class="[
                            'flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer transition-all',
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
                        />
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto mb-2" :class="form.role === 'teacher' ? 'text-red-900' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm font-medium" :class="form.role === 'teacher' ? 'text-red-900' : 'text-gray-700'">Teacher</span>
                        </div>
                    </label>

                    <label
                        :class="[
                            'flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer transition-all',
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
                        />
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto mb-2" :class="form.role === 'student' ? 'text-yellow-600' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="text-sm font-medium" :class="form.role === 'student' ? 'text-yellow-600' : 'text-gray-700'">Student</span>
                        </div>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name" />
                    <TextInput
                        id="first_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.first_name"
                        required
                        autofocus
                        autocomplete="given-name"
                    />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Last Name" />
                    <TextInput
                        id="last_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.last_name"
                        required
                        autocomplete="family-name"
                    />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create Account
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center text-sm">
                <span class="text-gray-600">Already have an account? </span>
                <Link
                    :href="route('login')"
                    class="text-red-900 underline hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-900 focus:ring-offset-2 rounded font-medium"
                >
                    Sign in
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
