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
        \App\Models\User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@elevategs.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Jane Smith',
            'email' => 'teacher2@elevategs.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email_verified_at' => now(),
        ]);
    }
}
