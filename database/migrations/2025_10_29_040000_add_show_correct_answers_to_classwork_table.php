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
            $table->boolean('show_correct_answers')->default(false)->after('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classwork', function (Blueprint $table) {
            $table->dropColumn('show_correct_answers');
        });
    }
};
