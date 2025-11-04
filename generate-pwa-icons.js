const fs = require('fs');
const { createCanvas } = require('canvas');

// ElevateGS Brand Colors
const MAROON = '#7f1d1d';
const MAROON_DARK = '#5f1616';
const WHITE = '#ffffff';
const GOLD = '#fbbf24';

// Convert hex to RGB
function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

// Draw rounded rectangle
function roundRect(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
}

// Generate icon
function generateIcon(size, filename, isMaskable = false) {
    const canvas = createCanvas(size, size);
    const ctx = canvas.getContext('2d');
    
    const centerX = size / 2;
    const centerY = size / 2;
    const padding = isMaskable ? size * 0.1 : 0;
    const safeSize = size - (padding * 2);
    
    // Background gradient
    const maroonRgb = hexToRgb(MAROON);
    const maroonDarkRgb = hexToRgb(MAROON_DARK);
    
    if (isMaskable) {
        // Full bleed for maskable
        const gradient = ctx.createLinearGradient(0, 0, size, size);
        gradient.addColorStop(0, MAROON);
        gradient.addColorStop(1, MAROON_DARK);
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, size, size);
    } else {
        // Rounded square
        const gradient = ctx.createLinearGradient(0, 0, size, size);
        gradient.addColorStop(0, MAROON);
        gradient.addColorStop(1, MAROON_DARK);
        ctx.fillStyle = gradient;
        
        const radius = size * 0.15;
        roundRect(ctx, 0, 0, size, size, radius);
        ctx.fill();
    }
    
    // Add shine effect
    const shineGradient = ctx.createRadialGradient(
        centerX, centerY * 0.4, 0,
        centerX, centerY, size * 0.7
    );
    shineGradient.addColorStop(0, 'rgba(255, 255, 255, 0.2)');
    shineGradient.addColorStop(1, 'rgba(0, 0, 0, 0.2)');
    ctx.fillStyle = shineGradient;
    ctx.fillRect(0, 0, size, size);
    
    // EGS Text
    ctx.fillStyle = WHITE;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    const fontSize = isMaskable ? safeSize * 0.35 : size * 0.4;
    ctx.font = `bold ${fontSize}px Arial`;
    ctx.fillText('EGS', centerX, centerY - (fontSize * 0.05));
    
    // Gold underline
    const underlineY = centerY + (fontSize * 0.4);
    const underlineWidth = fontSize * 1.2;
    ctx.fillStyle = GOLD;
    ctx.fillRect(centerX - underlineWidth / 2, underlineY, underlineWidth, fontSize * 0.08);
    
    // Small "ELEVATE" text for larger icons
    if (size >= 192) {
        ctx.fillStyle = WHITE;
        const smallFontSize = size * 0.08;
        ctx.font = `${smallFontSize}px Arial`;
        ctx.fillText('ELEVATE', centerX, centerY + (fontSize * 0.6));
    }
    
    // Save file
    const buffer = canvas.toBuffer('image/png');
    fs.writeFileSync(filename, buffer);
    console.log(`✅ Generated: ${filename}`);
}

// Generate all icons
console.log('🎨 Generating ElevateGS PWA Icons...\n');

try {
    generateIcon(64, 'public/pwa-64x64.png');
    generateIcon(192, 'public/pwa-192x192.png');
    generateIcon(512, 'public/pwa-512x512.png');
    generateIcon(512, 'public/pwa-maskable-512x512.png', true);
    
    console.log('\n🎉 All icons generated successfully!');
    console.log('📁 Location: public/');
    console.log('🔧 Next: Update vite.config.js if needed');
} catch (error) {
    console.error('❌ Error generating icons:', error.message);
    console.log('\n💡 Install canvas package: npm install canvas');
}
