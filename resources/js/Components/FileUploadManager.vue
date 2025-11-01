<template>
    <div class="file-upload-manager">
        <!-- Upload Area -->
        <div
            class="upload-dropzone"
            :class="{ 'dragover': isDragging, 'disabled': uploading }"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @click="triggerFileInput"
        >
            <input
                ref="fileInput"
                type="file"
                :multiple="multiple"
                :accept="acceptedTypes"
                @change="handleFileSelect"
                class="hidden"
            />

            <div class="upload-content">
                <svg v-if="!uploading" class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                
                <div v-if="uploading" class="upload-spinner">
                    <svg class="animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <p class="upload-text">
                    <span v-if="!uploading">
                        <strong>Click to upload</strong> or drag and drop
                    </span>
                    <span v-else>Uploading {{ uploadProgress }}%...</span>
                </p>
                
                <p class="upload-hint" v-if="!uploading">
                    {{ acceptText }} (Max {{ maxSizeText }})
                </p>
            </div>
        </div>

        <!-- Storage Provider Selection -->
        <div class="storage-provider-selector" v-if="showCloudOptions">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Storage Provider
            </label>
            <div class="flex gap-3">
                <button
                    v-for="provider in storageProviders"
                    :key="provider.value"
                    @click="selectedProvider = provider.value"
                    :class="[
                        'provider-btn',
                        selectedProvider === provider.value ? 'active' : ''
                    ]"
                    type="button"
                >
                    <span>{{ provider.label }}</span>
                </button>
            </div>
        </div>

        <!-- File List -->
        <div v-if="files.length > 0" class="file-list">
            <h3 class="file-list-title">Uploaded Files</h3>
            
            <div class="file-items">
                <div
                    v-for="file in files"
                    :key="file.id"
                    class="file-item"
                >
                    <!-- Thumbnail or Icon -->
                    <div class="file-preview">
                        <img
                            v-if="file.is_image && file.thumbnail_url"
                            :src="file.thumbnail_url"
                            :alt="file.name"
                            class="file-thumbnail"
                        />
                        <div v-else class="file-icon" :class="getFileIconClass(file)">
                            <span>{{ file.extension.toUpperCase() }}</span>
                        </div>
                    </div>

                    <!-- File Info -->
                    <div class="file-info">
                        <p class="file-name">{{ file.name }}</p>
                        <p class="file-meta">
                            {{ file.size }} • {{ file.storage_provider }}
                            <span v-if="file.uploaded_by"> • {{ file.uploaded_by.name }}</span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="file-actions">
                        <a
                            :href="file.url"
                            target="_blank"
                            class="file-action-btn view"
                            title="View file"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </a>
                        
                        <a
                            :href="file.url"
                            download
                            class="file-action-btn download"
                            title="Download file"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                        
                        <button
                            v-if="canDelete"
                            @click="deleteFile(file)"
                            class="file-action-btn delete"
                            title="Delete file"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    uploadableType: {
        type: String,
        required: true,
    },
    uploadableId: {
        type: [Number, String],
        required: true,
    },
    multiple: {
        type: Boolean,
        default: true,
    },
    acceptedFileTypes: {
        type: String,
        default: 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt',
    },
    maxSize: {
        type: Number,
        default: 50, // MB
    },
    showCloudOptions: {
        type: Boolean,
        default: true,
    },
    canDelete: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['uploaded', 'deleted', 'error']);

// State
const files = ref([]);
const isDragging = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const errorMessage = ref('');
const fileInput = ref(null);
const config = ref(null);
const selectedProvider = ref('local');

// Storage providers
const storageProviders = ref([
    { value: 'local', label: 'Local Storage' },
    { value: 'google_drive', label: 'Google Drive' },
    { value: 'onedrive', label: 'OneDrive' },
]);

// Computed
const acceptedTypes = computed(() => props.acceptedFileTypes);
const maxSizeText = computed(() => `${props.maxSize}MB`);
const acceptText = computed(() => {
    if (props.acceptedFileTypes.includes('image')) return 'Images, Videos, Documents';
    return 'All supported files';
});

// Methods
const triggerFileInput = () => {
    if (!uploading.value) {
        fileInput.value.click();
    }
};

const handleFileSelect = (event) => {
    const selectedFiles = Array.from(event.target.files);
    uploadFiles(selectedFiles);
};

const handleDrop = (event) => {
    isDragging.value = false;
    const droppedFiles = Array.from(event.dataTransfer.files);
    uploadFiles(droppedFiles);
};

const uploadFiles = async (filesToUpload) => {
    errorMessage.value = '';
    
    if (filesToUpload.length === 0) return;

    // Validate files
    for (const file of filesToUpload) {
        if (file.size > props.maxSize * 1024 * 1024) {
            errorMessage.value = `File "${file.name}" exceeds maximum size of ${props.maxSize}MB`;
            return;
        }
    }

    uploading.value = true;
    uploadProgress.value = 0;

    try {
        const formData = new FormData();
        
        if (props.multiple && filesToUpload.length > 1) {
            // Multiple files upload
            filesToUpload.forEach(file => {
                formData.append('files[]', file);
            });
            formData.append('uploadable_type', props.uploadableType);
            formData.append('uploadable_id', props.uploadableId);
            formData.append('storage_provider', selectedProvider.value);

            const response = await axios.post('/api/files/upload-multiple', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                },
            });

            if (response.data.success) {
                files.value.unshift(...response.data.files);
                emit('uploaded', response.data.files);
            }
        } else {
            // Single file upload
            formData.append('file', filesToUpload[0]);
            formData.append('uploadable_type', props.uploadableType);
            formData.append('uploadable_id', props.uploadableId);
            formData.append('storage_provider', selectedProvider.value);

            const response = await axios.post('/api/files/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                },
            });

            if (response.data.success) {
                files.value.unshift(response.data.file);
                emit('uploaded', response.data.file);
            }
        }

        // Reset file input
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Upload failed. Please try again.';
        emit('error', error);
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

const deleteFile = async (file) => {
    if (!confirm(`Are you sure you want to delete "${file.name}"?`)) {
        return;
    }

    try {
        const response = await axios.delete(`/api/files/${file.id}`);
        
        if (response.data.success) {
            files.value = files.value.filter(f => f.id !== file.id);
            emit('deleted', file);
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Delete failed. Please try again.';
        emit('error', error);
    }
};

const loadFiles = async () => {
    try {
        const response = await axios.get('/api/files', {
            params: {
                uploadable_type: props.uploadableType,
                uploadable_id: props.uploadableId,
            },
        });

        if (response.data.success) {
            files.value = response.data.files;
        }
    } catch (error) {
        console.error('Failed to load files:', error);
    }
};

const loadConfig = async () => {
    try {
        const response = await axios.get('/api/files/config');
        if (response.data.success) {
            config.value = response.data.config;
        }
    } catch (error) {
        console.error('Failed to load config:', error);
    }
};

const getFileIconClass = (file) => {
    if (file.is_image) return 'icon-image';
    if (file.is_video) return 'icon-video';
    if (file.is_document) return 'icon-document';
    return 'icon-file';
};

// Lifecycle
onMounted(() => {
    loadFiles();
    loadConfig();
});
</script>

<style scoped>
.file-upload-manager {
    width: 100%;
}

.upload-dropzone {
    border: 2px dashed #cbd5e0;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background-color: #f7fafc;
}

.upload-dropzone:hover {
    border-color: #4299e1;
    background-color: #ebf8ff;
}

.upload-dropzone.dragover {
    border-color: #4299e1;
    background-color: #ebf8ff;
}

.upload-dropzone.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.upload-icon {
    width: 3rem;
    height: 3rem;
    color: #4299e1;
}

.upload-spinner svg {
    width: 2rem;
    height: 2rem;
    color: #4299e1;
}

.upload-text {
    font-size: 0.875rem;
    color: #4a5568;
}

.upload-hint {
    font-size: 0.75rem;
    color: #718096;
}

.hidden {
    display: none;
}

.storage-provider-selector {
    margin-top: 1rem;
}

.provider-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #cbd5e0;
    border-radius: 0.375rem;
    background-color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.provider-btn:hover {
    border-color: #4299e1;
}

.provider-btn.active {
    background-color: #4299e1;
    color: white;
    border-color: #4299e1;
}

.file-list {
    margin-top: 1.5rem;
}

.file-list-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.75rem;
}

.file-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    background-color: white;
}

.file-preview {
    flex-shrink: 0;
}

.file-thumbnail {
    width: 3rem;
    height: 3rem;
    object-fit: cover;
    border-radius: 0.25rem;
}

.file-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
}

.icon-image {
    background-color: #c3dafe;
    color: #2c5282;
}

.icon-video {
    background-color: #fed7d7;
    color: #742a2a;
}

.icon-document {
    background-color: #fefcbf;
    color: #744210;
}

.icon-file {
    background-color: #e2e8f0;
    color: #2d3748;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2d3748;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-meta {
    font-size: 0.75rem;
    color: #718096;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
}

.file-action-btn {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
    cursor: pointer;
}

.file-action-btn svg {
    width: 1.25rem;
    height: 1.25rem;
}

.file-action-btn.view {
    color: #4299e1;
}

.file-action-btn.view:hover {
    background-color: #ebf8ff;
}

.file-action-btn.download {
    color: #48bb78;
}

.file-action-btn.download:hover {
    background-color: #f0fff4;
}

.file-action-btn.delete {
    color: #f56565;
    background: none;
    border: none;
}

.file-action-btn.delete:hover {
    background-color: #fff5f5;
}

.error-message {
    margin-top: 1rem;
    padding: 0.75rem;
    background-color: #fff5f5;
    border: 1px solid #fc8181;
    border-radius: 0.375rem;
    color: #c53030;
    font-size: 0.875rem;
}
</style>
