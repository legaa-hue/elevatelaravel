<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class FileUploadController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload single file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:' . (FileUpload::MAX_FILE_SIZE * 1024),
                'uploadable_type' => 'required|string',
                'uploadable_id' => 'required|integer',
                'disk' => 'sometimes|string|in:public,local',
                'storage_provider' => 'sometimes|string|in:local,google_drive,onedrive',
            ]);

            $file = $request->file('file');
            $uploadableType = $request->input('uploadable_type');
            $uploadableId = $request->input('uploadable_id');
            $disk = $request->input('disk', 'public');
            $storageProvider = $request->input('storage_provider', 'local');

            $fileUpload = $this->fileUploadService->upload(
                $file,
                $uploadableType,
                $uploadableId,
                Auth::user(),
                $disk,
                $storageProvider
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => [
                    'id' => $fileUpload->id,
                    'name' => $fileUpload->original_name,
                    'url' => $fileUpload->url,
                    'thumbnail_url' => $fileUpload->thumbnail_url,
                    'size' => $fileUpload->formatted_size,
                    'mime_type' => $fileUpload->mime_type,
                    'extension' => $fileUpload->extension,
                    'is_image' => $fileUpload->isImage(),
                    'is_video' => $fileUpload->isVideo(),
                    'is_document' => $fileUpload->isDocument(),
                    'storage_provider' => $fileUpload->storage_provider,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload multiple files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'file|max:' . (FileUpload::MAX_FILE_SIZE * 1024),
                'uploadable_type' => 'required|string',
                'uploadable_id' => 'required|integer',
                'disk' => 'sometimes|string|in:public,local',
                'storage_provider' => 'sometimes|string|in:local,google_drive,onedrive',
            ]);

            $files = $request->file('files');
            $uploadableType = $request->input('uploadable_type');
            $uploadableId = $request->input('uploadable_id');
            $disk = $request->input('disk', 'public');
            $storageProvider = $request->input('storage_provider', 'local');

            $uploadedFiles = $this->fileUploadService->uploadMultiple(
                $files,
                $uploadableType,
                $uploadableId,
                Auth::user(),
                $disk,
                $storageProvider
            );

            $filesData = array_map(function ($fileUpload) {
                return [
                    'id' => $fileUpload->id,
                    'name' => $fileUpload->original_name,
                    'url' => $fileUpload->url,
                    'thumbnail_url' => $fileUpload->thumbnail_url,
                    'size' => $fileUpload->formatted_size,
                    'mime_type' => $fileUpload->mime_type,
                    'extension' => $fileUpload->extension,
                    'is_image' => $fileUpload->isImage(),
                    'is_video' => $fileUpload->isVideo(),
                    'is_document' => $fileUpload->isDocument(),
                    'storage_provider' => $fileUpload->storage_provider,
                ];
            }, $uploadedFiles);

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'files' => $filesData,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file details.
     *
     * @param FileUpload $file
     * @return JsonResponse
     */
    public function show(FileUpload $file): JsonResponse
    {
        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'name' => $file->original_name,
                'url' => $file->url,
                'thumbnail_url' => $file->thumbnail_url,
                'size' => $file->formatted_size,
                'mime_type' => $file->mime_type,
                'extension' => $file->extension,
                'is_image' => $file->isImage(),
                'is_video' => $file->isVideo(),
                'is_document' => $file->isDocument(),
                'storage_provider' => $file->storage_provider,
                'uploaded_at' => $file->created_at->format('Y-m-d H:i:s'),
                'uploaded_by' => [
                    'id' => $file->user->id,
                    'name' => $file->user->name,
                ],
            ],
        ]);
    }

    /**
     * Get files for a specific uploadable.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'uploadable_type' => 'required|string',
            'uploadable_id' => 'required|integer',
        ]);

        $files = FileUpload::where('uploadable_type', $request->uploadable_type)
            ->where('uploadable_id', $request->uploadable_id)
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'url' => $file->url,
                    'thumbnail_url' => $file->thumbnail_url,
                    'size' => $file->formatted_size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'is_image' => $file->isImage(),
                    'is_video' => $file->isVideo(),
                    'is_document' => $file->isDocument(),
                    'storage_provider' => $file->storage_provider,
                    'uploaded_at' => $file->created_at->format('Y-m-d H:i:s'),
                    'uploaded_by' => [
                        'id' => $file->user->id,
                        'name' => $file->user->name,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    /**
     * Delete file.
     *
     * @param FileUpload $file
     * @return JsonResponse
     */
    public function destroy(FileUpload $file): JsonResponse
    {
        try {
            // Check authorization
            if ($file->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this file',
                ], 403);
            }

            $this->fileUploadService->delete($file);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get allowed file types and size limits.
     *
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'config' => [
                'max_sizes' => [
                    'image' => FileUpload::MAX_IMAGE_SIZE . 'MB',
                    'video' => FileUpload::MAX_VIDEO_SIZE . 'MB',
                    'document' => FileUpload::MAX_DOCUMENT_SIZE . 'MB',
                    'general' => FileUpload::MAX_FILE_SIZE . 'MB',
                ],
                'allowed_types' => [
                    'images' => FileUpload::ALLOWED_IMAGE_TYPES,
                    'videos' => FileUpload::ALLOWED_VIDEO_TYPES,
                    'documents' => FileUpload::ALLOWED_DOCUMENT_TYPES,
                ],
                'storage_providers' => [
                    'local' => 'Local Storage',
                    'google_drive' => 'Google Drive',
                    'onedrive' => 'OneDrive',
                ],
            ],
        ]);
    }
}
