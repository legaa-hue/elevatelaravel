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
        Schema::table('course_templates', function (Blueprint $table) {
            $table->enum('course_type', ['Major Course', 'Basic Course', 'Thesis'])->after('course_name')->default('Major Course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_templates', function (Blueprint $table) {
            $table->dropColumn('course_type');
        });
    }
};
