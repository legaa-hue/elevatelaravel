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
        // Add version column to courses table for conflict detection
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('updated_at');
        });

        // Add version column to classwork table for conflict detection
        Schema::table('classwork', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('updated_at');
        });

        // Add version column to classwork_submissions table for conflict detection
        Schema::table('classwork_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('updated_at');
        });

        // Add version column to events table for conflict detection
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('updated_at');
        });

        // Add version column to programs table for conflict detection
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('classwork', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('classwork_submissions', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
