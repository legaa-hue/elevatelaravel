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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('academic_year_id')->constrained('programs')->onDelete('set null');
            $table->foreignId('course_template_id')->nullable()->after('program_id')->constrained('course_templates')->onDelete('set null');
            $table->integer('units')->nullable()->after('description'); // Credit units
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['course_template_id']);
            $table->dropColumn(['program_id', 'course_template_id', 'units']);
        });
    }
};
