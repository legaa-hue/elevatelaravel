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
        Schema::table('events', function (Blueprint $table) {
            // Drop the old target_audience column
            $table->dropColumn('target_audience');
        });

        Schema::table('events', function (Blueprint $table) {
            // Add new target_audience column with proper values for announcements
            $table->enum('target_audience', ['teachers', 'students', 'both'])->default('both')->after('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->enum('target_audience', ['all_courses', 'specific_course'])->default('all_courses')->after('visibility');
        });
    }
};
