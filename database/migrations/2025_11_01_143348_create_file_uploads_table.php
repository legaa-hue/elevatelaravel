<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('uploadable_type'); // Polymorphic type (Classwork, ClassworkSubmission, etc.)
            $table->unsignedBigInteger('uploadable_id'); // Polymorphic ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Uploader
            $table->string('original_name'); // Original filename
            $table->string('stored_name'); // Stored filename (hashed)
            $table->string('file_path'); // Storage path
            $table->string('disk')->default('public'); // Storage disk (local, public, google, onedrive)
            $table->string('mime_type'); // File MIME type
            $table->string('extension', 10); // File extension
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('storage_provider')->nullable(); // google_drive, onedrive, local
            $table->string('cloud_file_id')->nullable(); // Cloud provider file ID
            $table->text('cloud_url')->nullable(); // Cloud provider URL
            $table->boolean('is_public')->default(false); // Public access
            $table->string('thumbnail_path')->nullable(); // Thumbnail for images/videos
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, duration, etc.)
            $table->timestamps();
            $table->softDeletes(); // Soft delete support
            
            // Indexes
            $table->index(['uploadable_type', 'uploadable_id']);
            $table->index('user_id');
            $table->index('storage_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
