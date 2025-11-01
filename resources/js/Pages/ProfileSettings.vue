<template>
    <component :is="layout" v-if="user && user.email">
        <Head title="Profile Settings" />
        
        <div class="py-6 sm:py-8 lg:py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Profile Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your account information and preferences</p>
                </div>

                <!-- Success Message -->
                <div v-if="showSuccessMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm font-medium text-green-800">Profile updated successfully!</p>
                    </div>
                </div>

                <!-- Error Messages -->
                <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-800 mb-1">Please correct the following errors:</p>
                            <ul class="list-disc list-inside text-sm text-red-700">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Profile Picture Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Picture</h2>
                        
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                            <!-- Current Profile Picture -->
                            <div class="relative">
                                <img 
                                    v-if="previewUrl || currentProfilePicture"
                                    :src="previewUrl || currentProfilePicture" 
                                    alt="Profile"
                                    class="w-32 h-32 rounded-full object-cover border-4 border-gray-100"
                                />
                                <div 
                                    v-else
                                    class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold border-4 border-gray-100"
                                >
                                    {{ userInitials }}
                                </div>
                                
                                <!-- Remove button overlay -->
                                <button
                                    v-if="(previewUrl || currentProfilePicture) && !isGoogleProfilePicture"
                                    @click="removeProfilePicture"
                                    type="button"
                                    class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-colors shadow-lg"
                                    title="Remove profile picture"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Upload Controls -->
                            <div class="flex-1 w-full sm:w-auto">
                                <div class="space-y-3">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        @change="handleFileSelect"
                                        class="hidden"
                                    />
                                    
                                    <button
                                        @click="$refs.fileInput.click()"
                                        type="button"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Choose Photo
                                    </button>

                                    <button
                                        v-if="isCameraAvailable"
                                        @click="openCamera"
                                        type="button"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Take Photo
                                    </button>

                                    <p class="text-xs text-gray-500">
                                        JPG, PNG or GIF. Max size 2MB. Recommended: 400x400px
                                    </p>
                                    
                                    <p v-if="isGoogleProfilePicture" class="text-xs text-blue-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        Linked to Google Account
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h2>
                        
                        <form @submit.prevent="updateProfile" class="space-y-6">
                            <!-- Name Fields Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                                <!-- First Name -->
                                <div class="sm:col-span-3">
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                                        :class="{ 'border-red-500': form.errors.first_name }"
                                    />
                                </div>

                                <!-- Middle Initial -->
                                <div class="sm:col-span-1">
                                    <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">
                                        M.I.
                                    </label>
                                    <input
                                        id="middle_initial"
                                        v-model="form.middle_initial"
                                        type="text"
                                        maxlength="1"
                                        @input="form.middle_initial = $event.target.value.toUpperCase()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                                        :class="{ 'border-red-500': form.errors.middle_initial }"
                                    />
                                </div>

                                <!-- Last Name -->
                                <div class="sm:col-span-2">
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                                        :class="{ 'border-red-500': form.errors.last_name }"
                                    />
                                </div>
                            </div>

                            <!-- Email (Read-only) -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <input
                                        id="email"
                                        :value="$page.props.auth.user.email"
                                        type="email"
                                        disabled
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                                    />
                                    <span class="absolute right-3 top-2.5 text-xs text-gray-400">Read-only</span>
                                </div>
                            </div>

                            <!-- Role (Read-only) -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                    Role
                                </label>
                                <div class="relative">
                                    <input
                                        id="role"
                                        :value="formatRole($page.props.auth.user.role)"
                                        type="text"
                                        disabled
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                                    />
                                    <span class="absolute right-3 top-2.5 text-xs text-gray-400">Read-only</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                                
                                <!-- Only show Change Password button for non-Google users -->
                                <button
                                    v-if="!user.google_id"
                                    type="button"
                                    @click="showPasswordModal = true"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors font-medium"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Change Password
                                </button>

                                <!-- Info message for Google users -->
                                <div v-else class="flex-1 sm:flex-none flex items-center px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Signed in with Google - Password not required</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Modal -->
        <Teleport to="body">
            <div v-if="showCameraModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeCameraModal">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeCameraModal"></div>

                    <!-- Modal panel -->
                    <div class="relative inline-block w-full max-w-2xl p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Take Profile Picture</h3>
                            <button @click="closeCameraModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="relative bg-gray-900 rounded-lg overflow-hidden">
                            <video
                                ref="video"
                                autoplay
                                playsinline
                                class="w-full h-auto"
                            ></video>
                            <canvas ref="canvas" class="hidden"></canvas>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <button
                                @click="capturePhoto"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Capture
                            </button>
                            <button
                                @click="closeCameraModal"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Password Change Modal -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showPasswordModal = false">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showPasswordModal = false"></div>

                    <!-- Modal panel -->
                    <div class="relative inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                            <button @click="showPasswordModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Password Error Messages -->
                        <div v-if="Object.keys(passwordForm.errors).length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                <li v-for="(error, key) in passwordForm.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="updatePassword" class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Current Password
                                </label>
                                <div class="relative">
                                    <input
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        required
                                        class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': passwordForm.errors.current_password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                        class="absolute right-0 top-0 mt-2.5 mr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                                    >
                                        <svg v-if="!showCurrentPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <div class="relative">
                                    <input
                                        id="new_password"
                                        v-model="passwordForm.new_password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        required
                                        class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': passwordForm.errors.new_password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showNewPassword = !showNewPassword"
                                        class="absolute right-0 top-0 mt-2.5 mr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                                    >
                                        <svg v-if="!showNewPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                            </div>

                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm New Password
                                </label>
                                <div class="relative">
                                    <input
                                        id="new_password_confirmation"
                                        v-model="passwordForm.new_password_confirmation"
                                        :type="showNewPasswordConfirmation ? 'text' : 'password'"
                                        required
                                        class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <button
                                        type="button"
                                        @click="showNewPasswordConfirmation = !showNewPasswordConfirmation"
                                        class="absolute right-0 top-0 mt-2.5 mr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                                    >
                                        <svg v-if="!showNewPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                                >
                                    {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                                </button>
                                <button
                                    type="button"
                                    @click="showPasswordModal = false"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// Determine which layout to use based on user role
const layout = computed(() => {
    const role = user.value?.role;
    if (role === 'teacher') {
        return TeacherLayout;
    } else if (role === 'student') {
        return StudentLayout;
    }
    return TeacherLayout; // Default fallback
});

// Profile form
const form = useForm({
    first_name: user.value?.first_name || '',
    middle_initial: user.value?.middle_initial || '',
    last_name: user.value?.last_name || '',
    profile_picture: null,
    remove_profile_picture: false
});

// Password form
const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
});

// State
const previewUrl = ref(null);
const showCameraModal = ref(false);
const showPasswordModal = ref(false);
const showSuccessMessage = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showNewPasswordConfirmation = ref(false);
const video = ref(null);
const canvas = ref(null);
const stream = ref(null);
const fileInput = ref(null);

// Computed properties
const currentProfilePicture = computed(() => {
    const picture = user.value.profile_picture;
    if (!picture) return null;
    return picture.startsWith('http') ? picture : `/storage/${picture}`;
});

const isGoogleProfilePicture = computed(() => {
    return user.value.profile_picture && user.value.profile_picture.startsWith('http');
});

const userInitials = computed(() => {
    const first = user.value.first_name?.[0] || '';
    const last = user.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || user.value.email?.[0]?.toUpperCase() || '?';
});

const isCameraAvailable = computed(() => {
    return typeof navigator !== 'undefined' && 
           navigator.mediaDevices && 
           typeof navigator.mediaDevices.getUserMedia === 'function';
});

// Methods
const formatRole = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            return;
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }

        form.profile_picture = file;
        form.remove_profile_picture = false;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeProfilePicture = () => {
    if (confirm('Are you sure you want to remove your profile picture?')) {
        form.profile_picture = null;
        form.remove_profile_picture = true;
        previewUrl.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const openCamera = async () => {
    try {
        showCameraModal.value = true;
        await new Promise(resolve => setTimeout(resolve, 100)); // Wait for modal to render
        
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' },
            audio: false 
        });
        
        if (video.value) {
            video.value.srcObject = stream.value;
        }
    } catch (error) {
        console.error('Error accessing camera:', error);
        alert('Unable to access camera. Please check your permissions.');
        closeCameraModal();
    }
};

const closeCameraModal = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
    showCameraModal.value = false;
};

const capturePhoto = () => {
    if (!video.value || !canvas.value) return;

    const context = canvas.value.getContext('2d');
    canvas.value.width = video.value.videoWidth;
    canvas.value.height = video.value.videoHeight;
    context.drawImage(video.value, 0, 0);

    canvas.value.toBlob((blob) => {
        const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
        form.profile_picture = file;
        form.remove_profile_picture = false;
        previewUrl.value = URL.createObjectURL(blob);
        closeCameraModal();
    }, 'image/jpeg', 0.95);
};

const updateProfile = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
            
            // Clear preview after successful upload
            previewUrl.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    });
};

const updatePassword = () => {
    passwordForm.post(route('profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showPasswordModal.value = false;
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
        }
    });
};

// Cleanup
onUnmounted(() => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
});
</script>
