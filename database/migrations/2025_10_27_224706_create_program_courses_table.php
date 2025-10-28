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
        Schema::create('program_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->string('course_code'); // e.g., EDUCN 204
            $table->string('course_title'); // e.g., Statistics in Education
            $table->integer('units')->default(3);
            $table->enum('course_type', ['basic', 'major', 'thesis']); // Basic, Major, or Thesis
            $table->integer('order')->default(0); // For sorting courses
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_courses');
    }
};
