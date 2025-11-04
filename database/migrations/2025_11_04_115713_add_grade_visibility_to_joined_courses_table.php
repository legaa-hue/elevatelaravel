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
        Schema::table('joined_courses', function (Blueprint $table) {
            $table->boolean('grade_access_requested')->default(false);
            $table->boolean('grade_access_granted')->default(false);
            $table->timestamp('grade_access_requested_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('joined_courses', function (Blueprint $table) {
            $table->dropColumn(['grade_access_requested', 'grade_access_granted', 'grade_access_requested_at']);
        });
    }
};
