<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class FileUploadService
{
    /**
     * Upload file to storage.
     *
     * @param UploadedFile $file
     * @param string $uploadableType
     * @param int $uploadableId
     * @param User $user
     * @param string $disk
     * @param string|null $storageProvider
     * @return FileUpload
     * @throws Exception
     */
    public function upload(
        UploadedFile $file,
        string $uploadableType,
        int $uploadableId,
        User $user,
        string $disk = 'public',
        ?string $storageProvider = null
    ): FileUpload {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;
        
        // Determine folder based on uploadable type
        $folder = $this->getFolderPath($uploadableType, $uploadableId);
        $filePath = $folder . '/' . $storedName;

        // Store file based on provider
        if ($storageProvider === 'google_drive') {
            return $this->uploadToGoogleDrive($file, $uploadableType, $uploadableId, $user);
        } elseif ($storageProvider === 'onedrive') {
            return $this->uploadToOneDrive($file, $uploadableType, $uploadableId, $user);
        } else {
            // Local storage
            $file->storeAs($folder, $storedName, $disk);
        }

        // Create thumbnail for images
        $thumbnailPath = null;
        if ($this->isImage($file)) {
            $thumbnailPath = $this->createThumbnail($file, $folder, $storedName, $disk);
        }

        // Extract metadata
        $metadata = $this->extractMetadata($file);

        // Create database record
        return FileUpload::create([
            'uploadable_type' => $uploadableType,
            'uploadable_id' => $uploadableId,
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize(),
            'storage_provider' => $storageProvider ?? 'local',
            'thumbnail_path' => $thumbnailPath,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Upload multiple files.
     *
     * @param array $files
     * @param string $uploadableType
     * @param int $uploadableId
     * @param User $user
     * @param string $disk
     * @param string|null $storageProvider
     * @return array
     */
    public function uploadMultiple(
        array $files,
        string $uploadableType,
        int $uploadableId,
        User $user,
        string $disk = 'public',
        ?string $storageProvider = null
    ): array {
        $uploadedFiles = [];

        foreach ($files as $file) {
            try {
                $uploadedFiles[] = $this->upload(
                    $file,
                    $uploadableType,
                    $uploadableId,
                    $user,
                    $disk,
                    $storageProvider
                );
            } catch (Exception $e) {
                // Log error but continue with other files
                logger()->error('File upload failed: ' . $e->getMessage());
            }
        }

        return $uploadedFiles;
    }

    /**
     * Validate uploaded file.
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Check if file type is allowed
        $allowedTypes = array_merge(
            FileUpload::ALLOWED_IMAGE_TYPES,
            FileUpload::ALLOWED_VIDEO_TYPES,
            FileUpload::ALLOWED_DOCUMENT_TYPES
        );

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("File type {$mimeType} is not allowed.");
        }

        // Check file size based on type
        $maxSize = $this->getMaxSizeForType($mimeType);
        if ($size > $maxSize) {
            $maxSizeMB = $maxSize / 1024 / 1024;
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB.");
        }
    }

    /**
     * Get maximum file size for MIME type.
     *
     * @param string $mimeType
     * @return int Size in bytes
     */
    protected function getMaxSizeForType(string $mimeType): int
    {
        if (in_array($mimeType, FileUpload::ALLOWED_IMAGE_TYPES)) {
            return FileUpload::MAX_IMAGE_SIZE * 1024 * 1024;
        }

        if (in_array($mimeType, FileUpload::ALLOWED_VIDEO_TYPES)) {
            return FileUpload::MAX_VIDEO_SIZE * 1024 * 1024;
        }

        if (in_array($mimeType, FileUpload::ALLOWED_DOCUMENT_TYPES)) {
            return FileUpload::MAX_DOCUMENT_SIZE * 1024 * 1024;
        }

        return FileUpload::MAX_FILE_SIZE * 1024 * 1024;
    }

    /**
     * Get folder path for uploaded file.
     *
     * @param string $uploadableType
     * @param int $uploadableId
     * @return string
     */
    protected function getFolderPath(string $uploadableType, int $uploadableId): string
    {
        $type = class_basename($uploadableType);
        return strtolower($type) . 's/' . $uploadableId;
    }

    /**
     * Check if file is an image.
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Create thumbnail for image.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string $filename
     * @param string $disk
     * @return string|null
     */
    protected function createThumbnail(
        UploadedFile $file,
        string $folder,
        string $filename,
        string $disk
    ): ?string {
        try {
            // Skip SVG files
            if ($file->getMimeType() === 'image/svg+xml') {
                return null;
            }

            $thumbnailName = 'thumb_' . $filename;
            $thumbnailPath = $folder . '/' . $thumbnailName;
            
            // Create thumbnail directory
            $storagePath = Storage::disk($disk)->path($folder);
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Create thumbnail using GD
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getPathname());
            
            // Resize to 300x300 maintaining aspect ratio
            $image->scale(width: 300);
            
            // Save thumbnail
            $image->save(Storage::disk($disk)->path($thumbnailPath));

            return $thumbnailPath;
        } catch (Exception $e) {
            logger()->error('Thumbnail creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract file metadata.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function extractMetadata(UploadedFile $file): array
    {
        $metadata = [];

        // Extract image dimensions
        if ($this->isImage($file)) {
            try {
                [$width, $height] = getimagesize($file->getPathname());
                $metadata['width'] = $width;
                $metadata['height'] = $height;
            } catch (Exception $e) {
                // Ignore errors
            }
        }

        // Extract video duration (requires ffmpeg - optional)
        if (str_starts_with($file->getMimeType(), 'video/')) {
            $metadata['type'] = 'video';
            // Add video duration extraction if ffmpeg is available
        }

        return $metadata;
    }

    /**
     * Upload file to Google Drive.
     *
     * @param UploadedFile $file
     * @param string $uploadableType
     * @param int $uploadableId
     * @param User $user
     * @return FileUpload
     * @throws Exception
     */
    protected function uploadToGoogleDrive(
        UploadedFile $file,
        string $uploadableType,
        int $uploadableId,
        User $user
    ): FileUpload {
        // This requires Google Drive API setup
        // For now, we'll create a placeholder that can be implemented later
        
        try {
            // TODO: Implement Google Drive upload using Google API Client
            // 1. Authenticate with service account or OAuth
            // 2. Upload file to Drive
            // 3. Get file ID and shareable link
            // 4. Return FileUpload record with cloud details

            throw new Exception('Google Drive integration not yet configured. Please use local storage or configure Google Drive API credentials.');
        } catch (Exception $e) {
            throw new Exception('Google Drive upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to OneDrive.
     *
     * @param UploadedFile $file
     * @param string $uploadableType
     * @param int $uploadableId
     * @param User $user
     * @return FileUpload
     * @throws Exception
     */
    protected function uploadToOneDrive(
        UploadedFile $file,
        string $uploadableType,
        int $uploadableId,
        User $user
    ): FileUpload {
        // This requires OneDrive API setup
        // For now, we'll create a placeholder that can be implemented later
        
        try {
            // TODO: Implement OneDrive upload using Microsoft Graph API
            // 1. Authenticate with Microsoft Graph
            // 2. Upload file to OneDrive
            // 3. Get file ID and shareable link
            // 4. Return FileUpload record with cloud details

            throw new Exception('OneDrive integration not yet configured. Please use local storage or configure OneDrive API credentials.');
        } catch (Exception $e) {
            throw new Exception('OneDrive upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete file from storage.
     *
     * @param FileUpload $fileUpload
     * @return bool
     */
    public function delete(FileUpload $fileUpload): bool
    {
        try {
            // Delete from cloud if applicable
            if ($fileUpload->storage_provider === 'google_drive') {
                $this->deleteFromGoogleDrive($fileUpload);
            } elseif ($fileUpload->storage_provider === 'onedrive') {
                $this->deleteFromOneDrive($fileUpload);
            }

            // Delete from local storage
            $fileUpload->deleteFile();

            // Soft delete record
            $fileUpload->delete();

            return true;
        } catch (Exception $e) {
            logger()->error('File deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete file from Google Drive.
     *
     * @param FileUpload $fileUpload
     */
    protected function deleteFromGoogleDrive(FileUpload $fileUpload): void
    {
        // TODO: Implement Google Drive deletion
        // Use Google Drive API to delete file by ID
    }

    /**
     * Delete file from OneDrive.
     *
     * @param FileUpload $fileUpload
     */
    protected function deleteFromOneDrive(FileUpload $fileUpload): void
    {
        // TODO: Implement OneDrive deletion
        // Use Microsoft Graph API to delete file by ID
    }
}
