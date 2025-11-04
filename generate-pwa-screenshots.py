from PIL import Image, ImageDraw, ImageFont, ImageFilter
import os

# ElevateGS Brand Colors
MAROON = (127, 29, 29)  # #7f1d1d
MAROON_LIGHT = (153, 27, 27)  # #991b1b
WHITE = (255, 255, 255)
GRAY_100 = (243, 244, 246)
GRAY_200 = (229, 231, 235)
GRAY_600 = (75, 85, 99)
GOLD = (251, 191, 36)
GREEN = (34, 197, 94)
BLUE = (59, 130, 246)

def create_gradient(draw, x, y, width, height, color1, color2, direction='vertical'):
    """Create a gradient"""
    if direction == 'vertical':
        for i in range(height):
            ratio = i / height
            r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
            g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
            b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
            draw.rectangle([(x, y + i), (x + width, y + i + 1)], fill=(r, g, b))
    else:  # horizontal
        for i in range(width):
            ratio = i / width
            r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
            g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
            b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
            draw.rectangle([(x + i, y), (x + i + 1, y + height)], fill=(r, g, b))

def draw_card(draw, x, y, width, height, title, subtitle, color=BLUE):
    """Draw a card component"""
    # Card background
    draw.rounded_rectangle([(x, y), (x + width, y + height)], radius=8, fill=WHITE)
    
    # Color accent on left
    draw.rectangle([(x, y), (x + 4, y + height)], fill=color)
    
    # Title
    try:
        font_title = ImageFont.truetype("arialbd.ttf", 20)
        font_subtitle = ImageFont.truetype("arial.ttf", 14)
    except:
        font_title = ImageFont.load_default()
        font_subtitle = ImageFont.load_default()
    
    draw.text((x + 20, y + 15), title, fill=GRAY_600, font=font_title)
    draw.text((x + 20, y + 45), subtitle, fill=GRAY_600, font=font_subtitle)

def generate_desktop_screenshot():
    """Generate desktop screenshot (1280x720)"""
    width, height = 1280, 720
    img = Image.new('RGB', (width, height), GRAY_100)
    draw = ImageDraw.Draw(img)
    
    # Header with gradient
    create_gradient(draw, 0, 0, width, 80, MAROON, MAROON_LIGHT, 'horizontal')
    
    # Logo/Title
    try:
        font_logo = ImageFont.truetype("arialbd.ttf", 32)
        font_nav = ImageFont.truetype("arial.ttf", 16)
    except:
        font_logo = ImageFont.load_default()
        font_nav = ImageFont.load_default()
    
    draw.text((40, 24), "ElevateGS", fill=WHITE, font=font_logo)
    draw.text((width - 300, 32), "Dashboard | Courses | Profile", fill=WHITE, font=font_nav)
    
    # Main content area
    content_y = 100
    
    # Welcome section
    try:
        font_title = ImageFont.truetype("arialbd.ttf", 28)
        font_text = ImageFont.truetype("arial.ttf", 16)
    except:
        font_title = ImageFont.load_default()
        font_text = ImageFont.load_default()
    
    draw.text((40, content_y), "Welcome back, Teacher!", fill=MAROON, font=font_title)
    draw.text((40, content_y + 40), "Your dashboard overview for today", fill=GRAY_600, font=font_text)
    
    # Stats cards
    card_y = content_y + 90
    card_width = 280
    card_height = 120
    gap = 20
    
    # Card 1 - Active Courses
    draw_card(draw, 40, card_y, card_width, card_height, "12 Active Courses", "All courses running", BLUE)
    
    # Card 2 - Students
    draw_card(draw, 40 + card_width + gap, card_y, card_width, card_height, "245 Students", "Enrolled this semester", GREEN)
    
    # Card 3 - Pending Tasks
    draw_card(draw, 40 + (card_width + gap) * 2, card_y, card_width, card_height, "18 Pending", "Tasks to review", GOLD)
    
    # Card 4 - Completion Rate
    draw_card(draw, 40 + (card_width + gap) * 3, card_y, card_width, card_height, "94% Complete", "Average completion", GREEN)
    
    # Recent activity section
    activity_y = card_y + card_height + 40
    draw.text((40, activity_y), "Recent Activity", fill=MAROON, font=font_title)
    
    # Activity list
    activity_items_y = activity_y + 50
    draw.rounded_rectangle([(40, activity_items_y), (width - 40, activity_items_y + 180)], radius=8, fill=WHITE)
    
    draw.text((60, activity_items_y + 20), "• New submission in Web Development", fill=GRAY_600, font=font_text)
    draw.text((60, activity_items_y + 55), "• Grade completed for Database Management", fill=GRAY_600, font=font_text)
    draw.text((60, activity_items_y + 90), "• 3 new messages from students", fill=GRAY_600, font=font_text)
    draw.text((60, activity_items_y + 125), "• Assignment deadline tomorrow", fill=GRAY_600, font=font_text)
    
    # Footer badge
    draw.rounded_rectangle([(width - 200, height - 60), (width - 20, height - 20)], radius=20, fill=MAROON)
    draw.text((width - 170, height - 48), "PWA Enabled ✓", fill=WHITE, font=font_text)
    
    img.save('public/screenshot-desktop.png', 'PNG')
    print("✅ Generated: public/screenshot-desktop.png (1280x720)")

def generate_mobile_screenshot():
    """Generate mobile screenshot (750x1334)"""
    width, height = 750, 1334
    img = Image.new('RGB', (width, height), GRAY_100)
    draw = ImageDraw.Draw(img)
    
    # Header with gradient
    create_gradient(draw, 0, 0, width, 120, MAROON, MAROON_LIGHT, 'horizontal')
    
    # Logo/Title
    try:
        font_logo = ImageFont.truetype("arialbd.ttf", 36)
        font_text = ImageFont.truetype("arial.ttf", 18)
        font_title = ImageFont.truetype("arialbd.ttf", 24)
        font_small = ImageFont.truetype("arial.ttf", 16)
    except:
        font_logo = ImageFont.load_default()
        font_text = ImageFont.load_default()
        font_title = ImageFont.load_default()
        font_small = ImageFont.load_default()
    
    draw.text((30, 40), "ElevateGS", fill=WHITE, font=font_logo)
    draw.text((30, 85), "Learning Management System", fill=WHITE, font=font_small)
    
    # Main content
    content_y = 150
    
    # Welcome
    draw.text((30, content_y), "Dashboard", fill=MAROON, font=font_title)
    
    # Stats cards (stacked)
    card_y = content_y + 60
    card_width = width - 60
    card_height = 100
    gap = 20
    
    # Card 1
    draw.rounded_rectangle([(30, card_y), (30 + card_width, card_y + card_height)], radius=8, fill=WHITE)
    draw.rectangle([(30, card_y), (34, card_y + card_height)], fill=BLUE)
    draw.text((50, card_y + 25), "Active Courses", fill=GRAY_600, font=font_text)
    draw.text((50, card_y + 55), "12 courses", fill=GRAY_600, font=font_small)
    
    # Card 2
    card_y += card_height + gap
    draw.rounded_rectangle([(30, card_y), (30 + card_width, card_y + card_height)], radius=8, fill=WHITE)
    draw.rectangle([(30, card_y), (34, card_y + card_height)], fill=GREEN)
    draw.text((50, card_y + 25), "Total Students", fill=GRAY_600, font=font_text)
    draw.text((50, card_y + 55), "245 enrolled", fill=GRAY_600, font=font_small)
    
    # Card 3
    card_y += card_height + gap
    draw.rounded_rectangle([(30, card_y), (30 + card_width, card_y + card_height)], radius=8, fill=WHITE)
    draw.rectangle([(30, card_y), (34, card_y + card_height)], fill=GOLD)
    draw.text((50, card_y + 25), "Pending Tasks", fill=GRAY_600, font=font_text)
    draw.text((50, card_y + 55), "18 to review", fill=GRAY_600, font=font_small)
    
    # Card 4
    card_y += card_height + gap
    draw.rounded_rectangle([(30, card_y), (30 + card_width, card_y + card_height)], radius=8, fill=WHITE)
    draw.rectangle([(30, card_y), (34, card_y + card_height)], fill=GREEN)
    draw.text((50, card_y + 25), "Completion Rate", fill=GRAY_600, font=font_text)
    draw.text((50, card_y + 55), "94% average", fill=GRAY_600, font=font_small)
    
    # Recent Activity
    activity_y = card_y + card_height + 40
    draw.text((30, activity_y), "Recent Activity", fill=MAROON, font=font_title)
    
    # Activity list
    activity_items_y = activity_y + 50
    draw.rounded_rectangle([(30, activity_items_y), (width - 30, activity_items_y + 250)], radius=8, fill=WHITE)
    
    draw.text((50, activity_items_y + 20), "• New submission", fill=GRAY_600, font=font_small)
    draw.text((50, activity_items_y + 50), "  Web Development", fill=GRAY_600, font=font_small)
    draw.text((50, activity_items_y + 90), "• Grade completed", fill=GRAY_600, font=font_small)
    draw.text((50, activity_items_y + 120), "  Database Management", fill=GRAY_600, font=font_small)
    draw.text((50, activity_items_y + 160), "• 3 new messages", fill=GRAY_600, font=font_small)
    draw.text((50, activity_items_y + 200), "• Deadline tomorrow", fill=GRAY_600, font=font_small)
    
    # PWA Badge
    badge_y = height - 100
    draw.rounded_rectangle([(width // 2 - 100, badge_y), (width // 2 + 100, badge_y + 50)], radius=25, fill=MAROON)
    draw.text((width // 2 - 75, badge_y + 15), "Works Offline ✓", fill=WHITE, font=font_text)
    
    img.save('public/screenshot-mobile.png', 'PNG')
    print("✅ Generated: public/screenshot-mobile.png (750x1334)")

def main():
    print("📸 Generating ElevateGS PWA Screenshots...\n")
    
    try:
        generate_desktop_screenshot()
        generate_mobile_screenshot()
        
        print("\n🎉 All screenshots generated successfully!")
        print("📁 Location: public/")
        print("🔧 Next: Update vite.config.js to use new screenshots")
        
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()
