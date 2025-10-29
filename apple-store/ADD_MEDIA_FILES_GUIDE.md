# 📁 Adding Video and Logo Files - Step-by-Step Guide

## 🎯 **Quick Overview**
You need to replace 2 placeholder files with your actual media:
1. **Logo:** `assets/images/logo.png`
2. **Video:** `assets/videos/apple-showcase.mp4`

---

## 🏷️ **Step 1: Add Your Logo**

### **File Location:**
```
apple-store/assets/images/logo.png
```

### **How to Add:**
1. **Prepare your logo file:**
   - Save as PNG format with transparent background
   - Recommended size: 200x200px or larger
   - Ensure it looks good on black background

2. **Replace the file:**
   - Navigate to `apple-store/assets/images/`
   - Delete the existing `logo.png` placeholder
   - Upload/copy your actual logo file
   - Name it exactly `logo.png`

### **Logo Design Tips:**
- **Colors:** Should work well with black background and gold accents
- **Style:** Clean, minimal, Apple-inspired aesthetic
- **Format:** PNG with transparency for best results
- **Size:** Will automatically scale to 50px (navbar) and 40px (footer)

---

## 🎬 **Step 2: Add Your Video**

### **File Location:**
```
apple-store/assets/videos/apple-showcase.mp4
```

### **How to Add:**
1. **Prepare your video file:**
   - Convert to MP4 format (H.264 codec)
   - Optimize for web (compress to under 10MB if possible)
   - Ensure it's high quality but web-friendly

2. **Replace the file:**
   - Navigate to `apple-store/assets/videos/`
   - Delete the existing `apple-showcase.mp4` placeholder
   - Upload/copy your actual video file
   - Name it exactly `apple-showcase.mp4`

### **Video Specifications:**
- **Format:** MP4 (H.264 codec)
- **Resolution:** 1920x1080 (Full HD) or higher
- **Aspect Ratio:** 16:9 recommended
- **Duration:** 30-60 seconds ideal
- **File Size:** Under 10MB for fast loading
- **Audio:** Optional (video plays muted by default)

---

## 🛠️ **Video Optimization Tools**

### **Free Online Tools:**
1. **HandBrake** (Desktop): https://handbrake.fr/
   - Free, powerful video compression
   - Preset for web optimization

2. **CloudConvert** (Online): https://cloudconvert.com/
   - Online video conversion and compression
   - No software installation needed

3. **FFmpeg** (Command Line):
   ```bash
   ffmpeg -i input.mov -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k apple-showcase.mp4
   ```

### **Compression Settings:**
- **Video Codec:** H.264
- **Quality:** CRF 23-28 (lower = better quality)
- **Preset:** Medium or Fast
- **Audio:** AAC, 128kbps (if needed)

---

## 🎨 **Logo Creation Tips**

### **Design Guidelines:**
1. **Apple-Inspired Style:**
   - Clean, minimal design
   - Avoid complex details
   - Use simple, elegant shapes

2. **Color Considerations:**
   - Works on black background
   - Complements gold (#D4AF37) accents
   - Consider white or gold elements

3. **Format Requirements:**
   - PNG with transparent background
   - High resolution (200x200px minimum)
   - Vector-based design preferred

### **Free Logo Tools:**
1. **Canva**: https://canva.com/
2. **LogoMaker**: https://logomaker.com/
3. **GIMP** (Free): https://gimp.org/

---

## 📱 **Testing After Upload**

### **1. Test the Homepage:**
1. Open your website in a browser
2. Navigate to the homepage
3. Check if the logo appears in navbar and footer
4. Verify the video plays automatically in the video section

### **2. Check Responsiveness:**
1. Test on mobile devices
2. Verify logo scales properly
3. Ensure video displays correctly on different screen sizes

### **3. Performance Check:**
1. Monitor page load time
2. Ensure video doesn't slow down the site
3. Test on slower internet connections

---

## 🔧 **Troubleshooting**

### **Logo Issues:**
- **Logo not showing:** Check file name is exactly `logo.png`
- **Logo too small/large:** File will auto-scale, but ensure original is high quality
- **Logo looks bad:** Ensure transparent background and appropriate colors

### **Video Issues:**
- **Video not playing:** Check file name is exactly `apple-showcase.mp4`
- **Video too slow:** Compress the file size (aim for under 10MB)
- **Video not loading:** Ensure MP4 format with H.264 codec

### **Browser Compatibility:**
- **Modern browsers:** Chrome, Firefox, Safari, Edge (latest versions)
- **Video autoplay:** Works in modern browsers (muted videos only)
- **Fallback:** Text message shows if video not supported

---

## 📋 **Quick Checklist**

### **Before Upload:**
- [ ] Logo is PNG format with transparency
- [ ] Logo is high resolution (200x200px+)
- [ ] Video is MP4 format (H.264 codec)
- [ ] Video is under 10MB file size
- [ ] Video is 30-60 seconds duration

### **After Upload:**
- [ ] Logo appears in navbar (top-left)
- [ ] Logo appears in footer (center)
- [ ] Video plays automatically on homepage
- [ ] Hover effects work on logo and video
- [ ] Site loads quickly
- [ ] Mobile version works correctly

---

## 🎯 **File Paths Summary**

```
apple-store/
├── assets/
│   ├── images/
│   │   └── logo.png          ← Replace this file
│   └── videos/
│       └── apple-showcase.mp4 ← Replace this file
```

---

## 🚀 **Ready to Go!**

Once you've replaced both files:
1. **Clear browser cache** (Ctrl+F5 or Cmd+Shift+R)
2. **Visit your homepage** to see the changes
3. **Test on mobile** to ensure responsiveness
4. **Enjoy your luxury Apple Store website!**

The implementation is complete and ready - you just need to add your actual media files to bring it to life! 🎉