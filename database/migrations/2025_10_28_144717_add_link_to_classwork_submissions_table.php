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
        Schema::table('classwork_submissions', function (Blueprint $table) {
            $table->string('link')->nullable()->after('submission_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classwork_submissions', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
};
