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
            $table->enum('visibility', ['admin_only', 'teachers', 'all'])->default('admin_only')->after('color');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade')->after('user_id');
            $table->enum('target_audience', ['all_courses', 'specific_course'])->default('all_courses')->after('visibility');
            $table->boolean('is_deadline')->default(false)->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'course_id', 'target_audience', 'is_deadline']);
        });
    }
};
