<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use updateOrCreate to avoid duplicates
        \App\Models\User::updateOrCreate(
            ['email' => 'teacher@elevategs.com'],
            [
                'name' => 'John Doe',
                'password' => bcrypt('teacher123'),
                'role' => 'teacher',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'teacher2@elevategs.com'],
            [
                'name' => 'Jane Smith',
                'password' => bcrypt('teacher123'),
                'role' => 'teacher',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
