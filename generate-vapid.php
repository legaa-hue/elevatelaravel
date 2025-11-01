<?php

/**
 * Standalone VAPID Key Generator
 * Run: php generate-vapid.php
 */

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Generate random private key (32 bytes)
$privateKey = random_bytes(32);
$privateKeyBase64 = base64url_encode($privateKey);

// For simplicity, we'll generate a public key placeholder
// In production, use proper EC key generation
$publicKey = random_bytes(65); // Uncompressed public key format
$publicKeyBase64 = base64url_encode($publicKey);

echo "\n===========================================\n";
echo "VAPID Keys Generated\n";
echo "===========================================\n\n";
echo "Add these to your .env file:\n\n";
echo "VAPID_PUBLIC_KEY=" . $publicKeyBase64 . "\n";
echo "VAPID_PRIVATE_KEY=" . $privateKeyBase64 . "\n";
echo "VAPID_SUBJECT=mailto:admin@elevategs.com\n\n";
echo "===========================================\n\n";
echo "⚠️  IMPORTANT: These are placeholder keys.\n";
echo "For production, use proper VAPID keys from:\n";
echo "https://web-push-codelab.glitch.me/\n";
echo "===========================================\n\n";
