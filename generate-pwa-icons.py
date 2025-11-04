from PIL import Image, ImageDraw, ImageFont
import os

# ElevateGS Brand Colors
MAROON = (127, 29, 29)  # #7f1d1d
MAROON_DARK = (95, 22, 22)  # #5f1616
WHITE = (255, 255, 255)
GOLD = (251, 191, 36)  # #fbbf24

def create_gradient(draw, width, height, color1, color2):
    """Create a gradient background"""
    for i in range(height):
        ratio = i / height
        r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
        g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
        b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
        draw.rectangle([(0, i), (width, i + 1)], fill=(r, g, b))

def generate_icon(size, filename, is_maskable=False):
    """Generate a PWA icon"""
    # Create image
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    padding = int(size * 0.1) if is_maskable else 0
    safe_size = size - (padding * 2)
    center_x = size // 2
    center_y = size // 2
    
    # Background gradient
    create_gradient(draw, size, size, MAROON, MAROON_DARK)
    
    if not is_maskable:
        # Create rounded rectangle mask
        mask = Image.new('L', (size, size), 0)
        mask_draw = ImageDraw.Draw(mask)
        radius = int(size * 0.15)
        mask_draw.rounded_rectangle([(0, 0), (size, size)], radius=radius, fill=255)
        
        # Apply mask
        img_temp = Image.new('RGBA', (size, size), (0, 0, 0, 0))
        img_temp.paste(img, (0, 0))
        img = Image.composite(img_temp, Image.new('RGBA', (size, size), (0, 0, 0, 0)), mask)
        draw = ImageDraw.Draw(img)
    
    # Draw "EGS" text
    try:
        # Try to use Arial Bold
        font_size = int((safe_size * 0.35) if is_maskable else (size * 0.4))
        try:
            font = ImageFont.truetype("arialbd.ttf", font_size)
        except:
            try:
                font = ImageFont.truetype("Arial Bold.ttf", font_size)
            except:
                try:
                    font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
                except:
                    font = ImageFont.load_default()
        
        # Get text size for centering
        text = "EGS"
        bbox = draw.textbbox((0, 0), text, font=font)
        text_width = bbox[2] - bbox[0]
        text_height = bbox[3] - bbox[1]
        
        text_x = center_x - text_width // 2
        text_y = center_y - text_height // 2 - int(font_size * 0.05)
        
        # Draw text
        draw.text((text_x, text_y), text, fill=WHITE, font=font)
        
        # Draw gold underline
        underline_y = center_y + int(font_size * 0.35)
        underline_width = int(font_size * 1.2)
        underline_height = int(font_size * 0.08)
        underline_x = center_x - underline_width // 2
        
        draw.rectangle(
            [(underline_x, underline_y), 
             (underline_x + underline_width, underline_y + underline_height)],
            fill=GOLD
        )
        
        # Add "ELEVATE" text for larger icons
        if size >= 192:
            small_font_size = int(size * 0.08)
            try:
                small_font = ImageFont.truetype("arial.ttf", small_font_size)
            except:
                try:
                    small_font = ImageFont.truetype("Arial.ttf", small_font_size)
                except:
                    try:
                        small_font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", small_font_size)
                    except:
                        small_font = ImageFont.load_default()
            
            small_text = "ELEVATE"
            small_bbox = draw.textbbox((0, 0), small_text, font=small_font)
            small_width = small_bbox[2] - small_bbox[0]
            small_x = center_x - small_width // 2
            small_y = center_y + int(font_size * 0.55)
            
            draw.text((small_x, small_y), small_text, fill=WHITE, font=small_font)
    
    except Exception as e:
        print(f"⚠️  Warning: Font rendering issue - {e}")
        # Fallback: Draw simple circles
        circle_size = size // 3
        draw.ellipse(
            [(center_x - circle_size, center_y - circle_size),
             (center_x + circle_size, center_y + circle_size)],
            fill=WHITE
        )
    
    # Save
    img.save(filename, 'PNG')
    print(f"✅ Generated: {filename} ({size}x{size})")

def main():
    print("🎨 Generating ElevateGS PWA Icons...\n")
    
    # Create public directory if it doesn't exist
    os.makedirs('public', exist_ok=True)
    
    try:
        # Generate all icons
        generate_icon(64, 'public/pwa-64x64.png')
        generate_icon(192, 'public/pwa-192x192.png')
        generate_icon(512, 'public/pwa-512x512.png')
        generate_icon(512, 'public/pwa-maskable-512x512.png', is_maskable=True)
        
        print("\n🎉 All icons generated successfully!")
        print("📁 Location: public/")
        print("🔧 Next: Icons are ready to use!")
        
    except Exception as e:
        print(f"\n❌ Error: {e}")
        print("💡 Install Pillow: pip install Pillow")

if __name__ == "__main__":
    main()
