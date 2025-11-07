import webpush from 'web-push';

console.log('\n===========================================');
console.log('VAPID Keys Generator (Proper EC Keys)');
console.log('===========================================\n');

const vapidKeys = webpush.generateVAPIDKeys();

console.log('✅ VAPID keys generated successfully!\n');
console.log('Copy these to your .env file:\n');
console.log(`VAPID_PUBLIC_KEY=${vapidKeys.publicKey}`);
console.log(`VAPID_PRIVATE_KEY=${vapidKeys.privateKey}`);
console.log('VAPID_SUBJECT=mailto:elevategs24@gmail.com\n');
console.log('===========================================');
console.log(`⚠️  Public Key Length: ${vapidKeys.publicKey.length} chars`);
console.log(`⚠️  Private Key Length: ${vapidKeys.privateKey.length} chars`);
console.log('===========================================\n');
