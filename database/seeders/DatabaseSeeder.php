<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        
        // Call seeders in order
        $this->call([
            AdminSeeder::class,
            TeacherSeeder::class,
            ProgramSeeder::class,
        ]);
        
        $this->command->info('✅ Database seeding completed successfully!');
    }
}
