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
        Schema::create('classwork', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['lesson', 'assignment', 'quiz', 'activity']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('points')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('has_submission')->default(false);
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');
            $table->string('color_code', 7)->default('#3b82f6'); // Tailwind blue-500
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classwork');
    }
};
