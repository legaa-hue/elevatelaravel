<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                File Upload Demo
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Upload Files</h3>
                        
                        <FileUploadManager
                            uploadable-type="App\Models\Classwork"
                            :uploadable-id="1"
                            :multiple="true"
                            :show-cloud-options="true"
                            :can-delete="true"
                            @uploaded="handleUploaded"
                            @deleted="handleDeleted"
                            @error="handleError"
                        />

                        <div v-if="message" class="mt-4 p-4 rounded" :class="messageClass">
                            {{ message }}
                        </div>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">📖 Usage Instructions</h3>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <h4 class="font-semibold text-blue-600">✅ Supported File Types:</h4>
                                <ul class="list-disc list-inside mt-2 text-gray-700">
                                    <li>Images: JPEG, PNG, GIF, WebP, SVG (Max 10MB)</li>
                                    <li>Videos: MP4, MPEG, MOV, AVI, WebM (Max 100MB)</li>
                                    <li>Documents: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT (Max 25MB)</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-blue-600">📤 Upload Methods:</h4>
                                <ul class="list-disc list-inside mt-2 text-gray-700">
                                    <li>Click the upload area to select files</li>
                                    <li>Drag and drop files directly</li>
                                    <li>Select multiple files at once</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-blue-600">☁️ Storage Options:</h4>
                                <ul class="list-disc list-inside mt-2 text-gray-700">
                                    <li><strong>Local Storage:</strong> Files stored on server (default)</li>
                                    <li><strong>Google Drive:</strong> Upload to Google Drive (requires configuration)</li>
                                    <li><strong>OneDrive:</strong> Upload to OneDrive (requires configuration)</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-blue-600">🔧 Features:</h4>
                                <ul class="list-disc list-inside mt-2 text-gray-700">
                                    <li>Automatic thumbnail generation for images</li>
                                    <li>Progress indicator during upload</li>
                                    <li>View, download, and delete files</li>
                                    <li>File metadata tracking (size, type, uploader)</li>
                                </ul>
                            </div>

                            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                                <h4 class="font-semibold text-yellow-800">⚠️ Cloud Storage Setup Required:</h4>
                                <p class="text-yellow-700 mt-2">
                                    To use Google Drive or OneDrive, you need to configure API credentials in your .env file. 
                                    See <code class="bg-yellow-100 px-2 py-1 rounded">FILE_UPLOAD_IMPLEMENTATION.md</code> for details.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FileUploadManager from '@/Components/FileUploadManager.vue';

const message = ref('');
const messageClass = ref('');

const handleUploaded = (file) => {
    message.value = `✅ File "${file.name || 'File'}" uploaded successfully!`;
    messageClass.value = 'bg-green-100 border border-green-400 text-green-700';
    
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const handleDeleted = (file) => {
    message.value = `🗑️ File "${file.name}" deleted successfully!`;
    messageClass.value = 'bg-blue-100 border border-blue-400 text-blue-700';
    
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const handleError = (error) => {
    message.value = `❌ Error: ${error.response?.data?.message || 'Upload failed'}`;
    messageClass.value = 'bg-red-100 border border-red-400 text-red-700';
};
</script>
