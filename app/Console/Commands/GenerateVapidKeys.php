<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate';
    protected $description = 'Generate VAPID keys for web push notifications (Windows compatible)';

    public function handle()
    {
        // Generate random keys using PHP's random_bytes
        $publicKey = $this->generateKey();
        $privateKey = $this->generateKey();

        $this->info('VAPID keys generated successfully!');
        $this->newLine();

        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line("VAPID_PUBLIC_KEY={$publicKey}");
        $this->line("VAPID_PRIVATE_KEY={$privateKey}");
        $this->line("VAPID_SUBJECT=mailto:admin@elevategs.com");
        $this->newLine();

        // Try to update .env file automatically
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);

            // Check if keys already exist
            if (strpos($envContent, 'VAPID_PUBLIC_KEY') !== false) {
                if ($this->confirm('VAPID keys already exist in .env. Do you want to replace them?', false)) {
                    $envContent = preg_replace('/VAPID_PUBLIC_KEY=.*/', "VAPID_PUBLIC_KEY={$publicKey}", $envContent);
                    $envContent = preg_replace('/VAPID_PRIVATE_KEY=.*/', "VAPID_PRIVATE_KEY={$privateKey}", $envContent);

                    if (strpos($envContent, 'VAPID_SUBJECT') === false) {
                        $envContent .= "\nVAPID_SUBJECT=mailto:admin@elevategs.com\n";
                    }

                    file_put_contents($envPath, $envContent);
                    $this->info('✓ .env file updated with new VAPID keys');
                }
            } else {
                // Append new keys
                $envContent .= "\n# Web Push Notification Keys\n";
                $envContent .= "VAPID_PUBLIC_KEY={$publicKey}\n";
                $envContent .= "VAPID_PRIVATE_KEY={$privateKey}\n";
                $envContent .= "VAPID_SUBJECT=mailto:admin@elevategs.com\n";

                file_put_contents($envPath, $envContent);
                $this->info('✓ .env file updated with VAPID keys');
            }
        }

        return 0;
    }

    private function generateKey(): string
    {
        // Generate 65 random bytes (similar to VAPID spec)
        $bytes = random_bytes(65);

        // Base64 URL-safe encode
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
