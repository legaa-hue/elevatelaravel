<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTeacher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teacher:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a teacher account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::updateOrCreate(
            ['email' => 'teacher@elevategs.com'],
            [
                'name' => 'John Teacher',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'first_name' => 'John',
                'last_name' => 'Teacher',
                'email_verified_at' => now(),
            ]
        );

        $this->info('Teacher account created successfully!');
        $this->info('Email: teacher@elevategs.com');
        $this->info('Password: password123');
    }
}
