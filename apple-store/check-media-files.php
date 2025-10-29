<?php
/**
 * Media Files Checker
 * 
 * Simple tool to verify logo and video files are properly uploaded
 */

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Media Files Checker - Apple Store</title>\n";
echo "    <style>\n";
echo "        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "        h1 { color: #333; border-bottom: 3px solid #D4AF37; padding-bottom: 10px; }\n";
echo "        .check-item { margin: 20px 0; padding: 15px; border-radius: 5px; }\n";
echo "        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }\n";
echo "        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }\n";
echo "        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }\n";
echo "        .file-info { margin: 10px 0; font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 3px; }\n";
echo "        .preview { margin: 15px 0; }\n";
echo "        .logo-preview { max-width: 100px; height: auto; border: 1px solid #ddd; padding: 10px; background: #000; }\n";
echo "        .video-preview { max-width: 100%; height: 200px; }\n";
echo "        .icon { margin-right: 8px; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<div class='container'>\n";
echo "<h1>📁 Media Files Checker</h1>\n";
echo "<p>This tool checks if your logo and video files are properly uploaded and configured.</p>\n";

// Check logo file
echo "<div class='check-item'>\n";
echo "<h3>🏷️ Logo File Check</h3>\n";

$logo_path = __DIR__ . '/assets/images/logo.png';
$logo_url = './assets/images/logo.png';

if (file_exists($logo_path)) {
    $logo_size = filesize($logo_path);
    $logo_info = getimagesize($logo_path);
    
    if ($logo_info !== false) {
        echo "<div class='success'>\n";
        echo "<span class='icon'>✅</span><strong>Logo file found and valid!</strong>\n";
        echo "<div class='file-info'>\n";
        echo "📍 Path: assets/images/logo.png<br>\n";
        echo "📏 Dimensions: {$logo_info[0]} x {$logo_info[1]} pixels<br>\n";
        echo "📦 File Size: " . round($logo_size / 1024, 2) . " KB<br>\n";
        echo "🎨 Type: " . $logo_info['mime'] . "\n";
        echo "</div>\n";
        echo "<div class='preview'>\n";
        echo "<strong>Preview:</strong><br>\n";
        echo "<img src='{$logo_url}' alt='Logo Preview' class='logo-preview'>\n";
        echo "</div>\n";
        echo "</div>\n";
    } else {
        echo "<div class='error'>\n";
        echo "<span class='icon'>❌</span><strong>Logo file exists but is not a valid image!</strong>\n";
        echo "<p>Please ensure the file is a valid PNG image.</p>\n";
        echo "</div>\n";
    }
} else {
    echo "<div class='error'>\n";
    echo "<span class='icon'>❌</span><strong>Logo file not found!</strong>\n";
    echo "<p>Please upload your logo file to: <code>assets/images/logo.png</code></p>\n";
    echo "</div>\n";
}

// Check video file
echo "</div>\n";
echo "<div class='check-item'>\n";
echo "<h3>🎬 Video File Check</h3>\n";

$video_path = __DIR__ . '/assets/videos/apple-showcase.mp4';
$video_url = './assets/videos/apple-showcase.mp4';

if (file_exists($video_path)) {
    $video_size = filesize($video_path);
    $video_size_mb = round($video_size / (1024 * 1024), 2);
    
    echo "<div class='success'>\n";
    echo "<span class='icon'>✅</span><strong>Video file found!</strong>\n";
    echo "<div class='file-info'>\n";
    echo "📍 Path: assets/videos/apple-showcase.mp4<br>\n";
    echo "📦 File Size: {$video_size_mb} MB<br>\n";
    
    if ($video_size_mb > 10) {
        echo "<span style='color: #856404;'>⚠️ Warning: File size is large ({$video_size_mb} MB). Consider compressing for better performance.</span><br>\n";
    }
    
    echo "</div>\n";
    echo "<div class='preview'>\n";
    echo "<strong>Preview:</strong><br>\n";
    echo "<video controls class='video-preview'>\n";
    echo "    <source src='{$video_url}' type='video/mp4'>\n";
    echo "    Your browser does not support the video tag.\n";
    echo "</video>\n";
    echo "</div>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'>\n";
    echo "<span class='icon'>❌</span><strong>Video file not found!</strong>\n";
    echo "<p>Please upload your video file to: <code>assets/videos/apple-showcase.mp4</code></p>\n";
    echo "</div>\n";
}

// Check homepage integration
echo "</div>\n";
echo "<div class='check-item'>\n";
echo "<h3>🔗 Homepage Integration Check</h3>\n";

$index_content = file_get_contents(__DIR__ . '/index.php');
$header_content = file_get_contents(__DIR__ . '/includes/header.php');
$footer_content = file_get_contents(__DIR__ . '/includes/footer.php');

$checks = [
    'Video section in homepage' => strpos($index_content, 'video-section') !== false,
    'Video file reference' => strpos($index_content, 'apple-showcase.mp4') !== false,
    'Logo in navbar' => strpos($header_content, 'logo.png') !== false,
    'Logo in footer' => strpos($footer_content, 'logo.png') !== false,
    'Video overlay text' => strpos($index_content, 'Luxury in Every Detail') !== false,
];

$all_good = true;
foreach ($checks as $check_name => $result) {
    if ($result) {
        echo "<div class='success'>\n";
        echo "<span class='icon'>✅</span>{$check_name}: OK\n";
        echo "</div>\n";
    } else {
        echo "<div class='error'>\n";
        echo "<span class='icon'>❌</span>{$check_name}: Missing\n";
        echo "</div>\n";
        $all_good = false;
    }
}

echo "</div>\n";

// Overall status
echo "<div class='check-item'>\n";
echo "<h3>📊 Overall Status</h3>\n";

$logo_exists = file_exists($logo_path);
$video_exists = file_exists($video_path);

if ($logo_exists && $video_exists && $all_good) {
    echo "<div class='success'>\n";
    echo "<span class='icon'>🎉</span><strong>Everything looks great!</strong>\n";
    echo "<p>Your logo and video are properly uploaded and integrated. Visit your homepage to see the results!</p>\n";
    echo "<p><a href='index.php' style='color: #D4AF37; font-weight: bold;'>→ View Homepage</a></p>\n";
    echo "</div>\n";
} else {
    echo "<div class='warning'>\n";
    echo "<span class='icon'>⚠️</span><strong>Setup incomplete</strong>\n";
    echo "<p>Please address the issues above to complete your homepage setup.</p>\n";
    echo "</div>\n";
}

echo "</div>\n";

// Instructions
echo "<div class='check-item'>\n";
echo "<h3>📋 Next Steps</h3>\n";
echo "<ol>\n";
if (!$logo_exists) {
    echo "<li>Upload your logo file to <code>assets/images/logo.png</code></li>\n";
}
if (!$video_exists) {
    echo "<li>Upload your video file to <code>assets/videos/apple-showcase.mp4</code></li>\n";
}
echo "<li>Refresh this page to verify the files</li>\n";
echo "<li>Visit your homepage to see the luxury enhancements</li>\n";
echo "<li>Test on mobile devices for responsiveness</li>\n";
echo "</ol>\n";

echo "<p><strong>Need help?</strong> Check the <code>ADD_MEDIA_FILES_GUIDE.md</code> for detailed instructions.</p>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>