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
        Schema::table('classwork', function (Blueprint $table) {
            // Gradebook integration columns
            $table->string('grading_period')->nullable()->after('show_correct_answers'); // 'midterm' or 'finals'
            $table->string('grade_table_name')->nullable()->after('grading_period'); // e.g., 'Quizzes', 'Activities'
            $table->string('grade_main_column')->nullable()->after('grade_table_name'); // e.g., 'Written Works', 'Performance Tasks'
            $table->string('grade_sub_column')->nullable()->after('grade_main_column'); // Auto-generated sub-column name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classwork', function (Blueprint $table) {
            $table->dropColumn(['grading_period', 'grade_table_name', 'grade_main_column', 'grade_sub_column']);
        });
    }
};
