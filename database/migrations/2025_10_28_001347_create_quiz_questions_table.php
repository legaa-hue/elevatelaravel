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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classwork_id')->constrained('classwork')->onDelete('cascade');
            $table->enum('type', ['multiple_choice', 'true_false', 'short_answer', 'essay', 'identification', 'enumeration']);
            $table->text('question');
            $table->json('options')->nullable(); // For multiple choice
            $table->string('correct_answer')->nullable(); // For identification and true/false
            $table->json('correct_answers')->nullable(); // For enumeration (multiple answers)
            $table->integer('points');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
