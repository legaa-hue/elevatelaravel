<?php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing WebPush Encryption...\n\n";

// Load VAPID keys from config
$vapidPublic = config('webpush.vapid.public_key');
$vapidPrivate = config('webpush.vapid.private_key');
$vapidSubject = config('webpush.vapid.subject');

echo "VAPID Public: " . substr($vapidPublic, 0, 20) . "...\n";
echo "VAPID Private: " . substr($vapidPrivate, 0, 20) . "...\n";
echo "VAPID Subject: " . $vapidSubject . "\n\n";

// Check OpenSSL
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n";
echo "prime256v1 available: " . (in_array('prime256v1', openssl_get_curve_names()) ? 'YES' : 'NO') . "\n\n";

// Try to create WebPush instance
try {
    $auth = [
        'VAPID' => [
            'subject' => $vapidSubject,
            'publicKey' => $vapidPublic,
            'privateKey' => $vapidPrivate,
        ],
    ];
    
    $webPush = new WebPush($auth);
    echo "✅ WebPush instance created successfully!\n\n";
    
    // Try to send a test notification
    $subscription = Subscription::create([
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        'publicKey' => 'BNmX1hvQZ3C27bh62eXd9-tJp5PbB1SxblIgI-R6Dzx_e5FAJql16n1nCqDH32k2PDHe8NGMTLCgUpPvBXt2j0E',
        'authToken' => 'test_auth_token',
    ]);
    
    $webPush->queueNotification(
        $subscription,
        json_encode(['title' => 'Test', 'body' => 'Test message'])
    );
    
    echo "✅ Notification queued!\n";
    echo "Attempting to flush...\n\n";
    
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        
        if ($report->isSuccess()) {
            echo "✅ Message sent successfully to {$endpoint}\n";
        } else {
            echo "❌ Message failed to sent to {$endpoint}: {$report->getReason()}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
