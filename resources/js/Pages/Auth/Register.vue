<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Get flash messages
const page = usePage();
const errorMessage = computed(() => page.props.error);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const emailVerified = ref(false);
const sendingCode = ref(false);
const checkingVerification = ref(false);
let verificationCheckInterval = null;
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Save form data to localStorage
const saveFormData = () => {
    const formData = {
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
        role: form.role,
        emailVerified: emailVerified.value,
    };
    localStorage.setItem('registrationForm', JSON.stringify(formData));
};

// Restore form data from localStorage
const restoreFormData = () => {
    const savedData = localStorage.getItem('registrationForm');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            form.first_name = data.first_name || '';
            form.last_name = data.last_name || '';
            form.email = data.email || '';
            form.password = data.password || '';
            form.password_confirmation = data.password_confirmation || '';
            form.role = data.role || '';
            emailVerified.value = data.emailVerified || false;
        } catch (e) {
            // Invalid data, ignore
        }
    }
};

// Check if email is already verified
const checkEmailVerification = async () => {
    if (!form.email) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;

        const response = await fetch(route('email.check-verified'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: form.email,
            }),
        });

        const data = await response.json();
        if (data.verified) {
            emailVerified.value = true;
            saveFormData();
            // Stop checking once verified
            if (verificationCheckInterval) {
                clearInterval(verificationCheckInterval);
                verificationCheckInterval = null;
            }
        }
    } catch (error) {
        // Silently fail - not critical
    }
};

// Auto-save form data when fields change
watch([() => form.first_name, () => form.last_name, () => form.email, 
       () => form.password, () => form.password_confirmation, () => form.role], () => {
    saveFormData();
});

// Check verification when email changes
watch(() => form.email, () => {
    if (form.email && !checkingVerification.value) {
        checkingVerification.value = true;
        setTimeout(() => {
            checkEmailVerification();
            checkingVerification.value = false;
        }, 500);
    }
});

// Restore form data on mount
onMounted(() => {
    restoreFormData();
    
    // Check verification status on load
    if (form.email) {
        checkEmailVerification();
    }
});

const clearVerification = () => {
    if (confirm('Are you sure you want to change your email? You will need to verify the new email address.')) {
        emailVerified.value = false;
        
        // Stop auto-checking if running
        if (verificationCheckInterval.value) {
            clearInterval(verificationCheckInterval.value);
            verificationCheckInterval.value = null;
        }
        
        // Clear the saved verification status
        saveFormData();
        
        // Focus on email input
        setTimeout(() => {
            document.getElementById('email')?.focus();
        }, 100);
    }
};

const handleGoogleSignUp = () => {
    // Save role selection if exists
    if (form.role) {
        localStorage.setItem('selectedRole', form.role);
    }
    window.location.href = route('auth.google');
};

const sendVerificationLink = async () => {
    if (!form.email || !form.first_name) {
        alert('Please enter your first name and email first.');
        return;
    }

    // Save form data before sending
    saveFormData();
    
    sendingCode.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            alert('❌ CSRF token not found. Please refresh the page.');
            sendingCode.value = false;
            return;
        }
        
        const response = await fetch(route('email.send-code'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: form.email,
                first_name: form.first_name,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            alert('✅ Verification link sent to ' + form.email + '.\n\nPlease check your email and click the link to verify.\n\nYour form data is saved - you can stay on this page and complete the remaining fields while waiting.');
            
            // Start auto-checking verification status every 3 seconds
            if (!verificationCheckInterval) {
                verificationCheckInterval = setInterval(() => {
                    checkEmailVerification();
                }, 3000);
            }
        } else {
            const errorMsg = data.message || 'Failed to send verification link.';
            alert('❌ ' + errorMsg);
        }
    } catch (error) {
        console.error('[ElevateGS] Verification error:', error);
        alert('❌ Error: ' + error.message);
    } finally {
        sendingCode.value = false;
    }
};

const submit = () => {
    // Check if role is selected
    if (!form.role) {
        alert('⚠️ Please select your role (Teacher or Student) before creating an account.');
        return;
    }
    
    // Check if email is verified
    if (!emailVerified.value) {
        alert('⚠️ Please verify your email address first.');
        return;
    }
    
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            // Clear saved form data after successful registration
            localStorage.removeItem('registrationForm');
            // Stop checking verification
            if (verificationCheckInterval) {
                clearInterval(verificationCheckInterval);
                verificationCheckInterval = null;
            }
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-medium text-gray-900">Create Your Account</h2>
            <p class="mt-2 text-sm text-gray-600">Join ElevateGS to start learning</p>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 border border-red-200">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ errorMessage }}</span>
            </div>
        </div>

        <!-- Google Sign-Up Button -->
        <button
            @click="handleGoogleSignUp"
            type="button"
            class="w-full flex items-center justify-center gap-3 rounded border-2 border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-900 focus:ring-offset-2 transition-colors mb-6"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
        </button>

        <div class="mb-6 flex items-center">
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
                <div class="flex gap-2 mt-1">
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <button
                        v-if="!emailVerified"
                        @click.prevent="sendVerificationLink"
                        type="button"
                        :disabled="sendingCode || !form.email || !form.first_name"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                    >
                        {{ sendingCode ? 'Sending...' : 'Verify Email' }}
                    </button>
                    <span
                        v-else
                        class="px-4 py-2 bg-green-100 text-green-700 text-sm font-medium rounded-md flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Verified
                    </span>
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
                <p v-if="verificationCheckInterval && !emailVerified" class="mt-2 text-xs text-blue-600 flex items-center gap-1">
                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Waiting for email verification... (checking automatically)
                </p>
                <p v-if="emailVerified" class="mt-2 text-xs text-gray-500">
                    Need to change email? 
                    <button 
                        @click.prevent="clearVerification" 
                        type="button"
                        class="text-blue-600 hover:text-blue-800 underline"
                    >
                        Click here to reset
                    </button>
                </p>
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pr-10"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-0 top-0 mt-3 mr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                    >
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <div class="relative">
                    <TextInput
                        id="password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        class="mt-1 block w-full pr-10"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                        class="absolute right-0 top-0 mt-3 mr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                    >
                        <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing || !form.role || !emailVerified }"
                    :disabled="form.processing || !form.role || !emailVerified"
                >
                    Create Account
                </PrimaryButton>
                
                <p v-if="!form.role" class="mt-2 text-xs text-center text-amber-600 font-medium">
                    ⚠️ Please select your role above to continue
                </p>
                <p v-else-if="!emailVerified" class="mt-2 text-xs text-center text-amber-600 font-medium">
                    ⚠️ Please verify your email address to continue
                </p>
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
