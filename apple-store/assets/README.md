# Assets Directory

## Required Files

### Logo
- **File**: `images/logo.png`
- **Specifications**: 
  - Format: PNG with transparency
  - Recommended size: 200x200px minimum
  - Usage: Navbar (50px height) and Footer (40px height)

### Video
- **File**: `videos/apple-showcase.mp4`
- **Specifications**:
  - Format: MP4 (H.264 codec recommended)
  - Resolution: 1920x1080 or higher
  - Duration: 30-60 seconds recommended
  - Size: Optimized for web (under 10MB)
  - Content: Apple products showcase or luxury brand video

## Implementation Notes

### Logo Implementation
The logo is automatically scaled and includes hover effects:
- Navbar: 50px height with scale and rotation animation
- Footer: 40px height with scale animation
- Mobile: Automatically scales down (35-40px)

### Video Implementation
The video includes:
- Autoplay (muted for browser compliance)
- Loop for continuous playback
- Responsive sizing (max-height: 500px)
- Performance optimization (pauses when not visible)
- Fallback message for unsupported browsers

### File Placement
Simply replace the placeholder files with your actual assets:
1. Replace `images/logo.png` with your logo file
2. Replace `videos/apple-showcase.mp4` with your video file

The system will automatically use these files without any code changes needed.