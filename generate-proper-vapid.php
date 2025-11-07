<?php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

echo "\n===========================================\n";
echo "VAPID Keys Generator (Proper EC Keys)\n";
echo "===========================================\n\n";

try {
    $keys = VAPID::createVapidKeys();
    
    echo "✅ VAPID keys generated successfully!\n\n";
    echo "Copy these to your .env file:\n\n";
    echo "VAPID_PUBLIC_KEY=" . $keys['publicKey'] . "\n";
    echo "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n";
    echo "VAPID_SUBJECT=mailto:elevategs24@gmail.com\n\n";
    echo "===========================================\n";
    echo "⚠️  Public Key Length: " . strlen($keys['publicKey']) . " chars\n";
    echo "⚠️  Private Key Length: " . strlen($keys['privateKey']) . " chars\n";
    echo "===========================================\n\n";
    
} catch (Exception $e) {
    echo "❌ Error generating keys: " . $e->getMessage() . "\n\n";
}
