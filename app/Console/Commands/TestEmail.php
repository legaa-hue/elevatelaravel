<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {email?}';
    protected $description = 'Test email configuration';

    public function handle()
    {
        $email = $this->argument('email') ?? 'elevategs24@gmail.com';
        
        try {
            Mail::raw('This is a test email from ElevateGS.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email - ElevateGS');
            });
            
            $this->info('✅ Test email sent successfully to: ' . $email);
            $this->info('📬 Check your inbox!');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email');
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
