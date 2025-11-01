<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update admin account
        User::updateOrCreate(
            ['email' => 'admin@elevategs.com'],
            [
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create or update teacher account
        User::updateOrCreate(
            ['email' => 'teacher@elevategs.com'],
            [
                'name' => 'John Doe',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => Hash::make('teacher123'),
                'role' => 'teacher',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create or update student account
        User::updateOrCreate(
            ['email' => 'student@elevategs.com'],
            [
                'name' => 'Jane Smith',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'password' => Hash::make('student123'),
                'role' => 'student',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
