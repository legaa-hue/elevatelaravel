<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table with the new enum values
        // First, check if we're using SQLite
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support modifying enum columns directly
            // We need to recreate the table
            Schema::table('classwork', function (Blueprint $table) {
                $table->dropColumn('type');
            });
            
            Schema::table('classwork', function (Blueprint $table) {
                $table->string('type')->default('lesson')->after('course_id');
            });
            
            // Add CHECK constraint for SQLite
            DB::statement("CREATE TRIGGER IF NOT EXISTS check_classwork_type_insert 
                BEFORE INSERT ON classwork 
                BEGIN 
                    SELECT CASE 
                        WHEN NEW.type NOT IN ('lesson', 'assignment', 'quiz', 'activity', 'essay', 'project') 
                        THEN RAISE(ABORT, 'Invalid type value') 
                    END; 
                END");
                
            DB::statement("CREATE TRIGGER IF NOT EXISTS check_classwork_type_update 
                BEFORE UPDATE ON classwork 
                BEGIN 
                    SELECT CASE 
                        WHEN NEW.type NOT IN ('lesson', 'assignment', 'quiz', 'activity', 'essay', 'project') 
                        THEN RAISE(ABORT, 'Invalid type value') 
                    END; 
                END");
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE classwork MODIFY COLUMN type ENUM('lesson', 'assignment', 'quiz', 'activity', 'essay', 'project') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            DB::statement("DROP TRIGGER IF EXISTS check_classwork_type_insert");
            DB::statement("DROP TRIGGER IF EXISTS check_classwork_type_update");
            
            Schema::table('classwork', function (Blueprint $table) {
                $table->dropColumn('type');
            });
            
            Schema::table('classwork', function (Blueprint $table) {
                $table->string('type')->after('course_id');
            });
        } else {
            DB::statement("ALTER TABLE classwork MODIFY COLUMN type ENUM('lesson', 'assignment', 'quiz', 'activity') NOT NULL");
        }
    }
};
