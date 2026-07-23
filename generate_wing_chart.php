<?php
/**
 * Dynamic Enneagram Wing Chart Generator
 * Uses GD library to render core type, wings, and motivations. 
 * Supports dynamic text wrapping and platform-independent TTF path fallback.
 */

// Global fallback handler in case of severe execution crash
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        outputFallbackPixel();
    }
});

// Clear output buffer immediately to protect the image binary format
if (ob_get_level() > 0) {
    ob_clean();
}

// 1. Strict Input Validation, Filtering, and Sanitization
// Enneagram type numbers must be between 1 and 9 inclusive.
$core = filter_input(INPUT_GET, 'core', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$core = ($core !== false && $core !== null) ? $core : 6;

$leftWing = filter_input(INPUT_GET, 'left_wing', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$leftWing = ($leftWing !== false && $leftWing !== null) ? $leftWing : 5;

$rightWing = filter_input(INPUT_GET, 'right_wing', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$rightWing = ($rightWing !== false && $rightWing !== null) ? $rightWing : 7;

// Sanitize strings with standard alphanumeric filter, stripping HTML templates
$coreDesire  = sanitizeString($_GET['core_desire'] ?? 'secure');
$leftDesire  = sanitizeString($_GET['left_desire'] ?? 'capable');
$rightDesire = sanitizeString($_GET['right_desire'] ?? 'fulfilled');

/**
 * Filter out unwanted character sequences and secure query input.
 */
function sanitizeString($str) {
    $str = strip_tags(trim($str));
    // Retain only word characters, spaces, and mild punctuation
    return preg_replace('/[^\w\s\-\!\?\'\"]/u', '', $str);
}

// 2. Validate GD Library Installation
if (!extension_loaded('gd')) {
    $baseJpgPath = __DIR__ . '/wings_base.jpg';
    if (file_exists($baseJpgPath)) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        if (!headers_sent() && php_sapi_name() !== 'cli') {
            header('Content-Type: image/jpeg');
            header('Cache-Control: max-age=604800, public');
        }
        echo file_get_contents($baseJpgPath);
        exit;
    }
    outputFallbackPixel();
}

// 3. Load Base Canvas (Autodetect if template exists, otherwise autogenerate)
$im = null;
$bgPath = __DIR__ . '/wings_blank.png';
$baseJpgPath = __DIR__ . '/wings_base.jpg';

if (file_exists($bgPath)) {
    $im = @imagecreatefrompng($bgPath);
}

// Self-healing block: dynamically clean wings_base.jpg if the blank file is missing
if (!$im && file_exists($baseJpgPath)) {
    $im = @imagecreatefromjpeg($baseJpgPath);
    if ($im) {
        $bgColorIdx = imagecolorat($im, 10, 10);
        $bgRGB = imagecolorsforindex($im, $bgColorIdx);
        $bgColor = imagecolorallocate($im, $bgRGB['red'], $bgRGB['green'], $bgRGB['blue']);
        
        // Paint over text to clean it
        imagefilledrectangle($im, 120, 172, 520, 215, $bgColor);
        imagefilledrectangle($im, 245, 222, 395, 458, $bgColor);
        
        // Clean left wing circle (sage green)
        $leftWingColor = imagecolorallocate($im, 149, 197, 181);
        imagefilledellipse($im, 202, 388, 50, 50, $leftWingColor);
        
        // Clean right wing circle (coral)
        $rightWingColor = imagecolorallocate($im, 232, 172, 151);
        imagefilledellipse($im, 438, 388, 50, 50, $rightWingColor);
        
        // Clean bottom description texts
        imagefilledrectangle($im, 20, 480, 280, 560, $bgColor);
        imagefilledrectangle($im, 360, 480, 620, 560, $bgColor);
    }
}

// Dynamic fallback canvas generator if all image assets are missing
$canvasGeneratedFromScratch = false;
if (!$im) {
    $im = imagecreatetruecolor(640, 640);
    $canvasGeneratedFromScratch = true;
    $bgColor = imagecolorallocate($im, 251, 248, 243); // Warm cream cream background
    imagefilledrectangle($im, 0, 0, 640, 640, $bgColor);
    
    // Draw layout outlines for visual consistency
    $leftWingColor = imagecolorallocate($im, 149, 197, 181);
    $rightWingColor = imagecolorallocate($im, 232, 172, 151);
    
    // Simple shape outlines representing wings & flow
    imagefilledellipse($im, 202, 388, 62, 62, $leftWingColor);
    imagefilledellipse($im, 438, 388, 62, 62, $rightWingColor);
}

// 4. Color System Setup (Allocates color variables matching visual elements)
$colorBg          = imagecolorallocate($im, 251, 248, 243);
$colorCoreYellow  = imagecolorallocate($im, 217, 193, 88);   // Core 6 Mustard
$colorWingGreen   = imagecolorallocate($im, 115, 164, 146);  // Wing 5 Sage
$colorWingCoral   = imagecolorallocate($im, 206, 138, 118);  // Wing 7 Coral
$colorMainText    = imagecolorallocate($im, 143, 127, 112);  // Muted Taupe Text
$colorWhite       = imagecolorallocate($im, 255, 255, 255);  // White for numbers inside wings

// 5. Typography and Font Configurations
// Check multiple possible locations for TTF font files (Windows vs Linux)
$fontFile = null;
$possibleFonts = [
    __DIR__ . '/fonts/Outfit-Regular.ttf',
    'C:\\Windows\\Fonts\\comic.ttf', // Hand-drawn comic font matching Enneagram organic layout
    'C:\\Windows\\Fonts\\arial.ttf',  // General standard sans-serif
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf'
];

foreach ($possibleFonts as $path) {
    if (file_exists($path)) {
        $fontFile = $path;
        break;
    }
}

// Disable TTF rendering if GD compilation lacks support
if (!function_exists('imagettftext')) {
    $fontFile = null;
}

// 6. Draw Content Components

// A. Title Text (Only render if canvas was generated from scratch)
if ($canvasGeneratedFromScratch) {
    drawTextCenteredAt($im, 24, 0, 320, 110, $colorMainText, $fontFile, "Understanding Enneagram Wings");
}

// B. Top "I want to be..." subtitle
$coreLabel = "I want to be " . $coreDesire;
drawTextCenteredAt($im, 18, 0, 320, 196, $colorCoreYellow, $fontFile, $coreLabel);

// C. Large Central Core Number (Drawing with absolute precision)
// Positioned dynamically at the middle of the image canvas
drawTextCenteredAt($im, 130, 0, 320, 412, $colorCoreYellow, $fontFile, (string)$core);

// D. Wing Numbers inside Wing Circles (Coordinates: Left=202, Right=438)
drawTextCenteredAt($im, 24, 0, 202, 399, $colorWhite, $fontFile, (string)$leftWing);
drawTextCenteredAt($im, 24, 0, 438, 399, $colorWhite, $fontFile, (string)$rightWing);

// E. Bottom Description Texts matching the Wing archetype color scheme
$leftDescText  = "but I want to be " . $leftDesire;
$rightDescText = "but I want to be " . $rightDesire;

drawTextCenteredAt($im, 14, 0, 150, 515, $colorWingGreen, $fontFile, $leftDescText);
drawTextCenteredAt($im, 14, 0, 490, 515, $colorWingCoral, $fontFile, $rightDescText);

// F. Footer label (Only render if canvas was generated from scratch)
if ($canvasGeneratedFromScratch) {
    drawTextCenteredAt($im, 10, 0, 320, 610, $colorMainText, $fontFile, "@enneadash.voice");
}

// 7. Output Result directly to the Browser Stream
// Clear output buffer to ensure correct image stream format
if (ob_get_level() > 0) {
    ob_clean();
}
if (!headers_sent() && php_sapi_name() !== 'cli') {
    header('Content-Type: image/png');
    header('Cache-Control: max-age=604800, public'); // Leverages client caching for premium performance
}

imagepng($im);
imagedestroy($im);
exit;

/**
 * Draw text string centered horizontally around an X coordinate.
 * Automatically falls back to built-in fonts if TTF configuration is not active.
 */
function drawTextCenteredAt($image, $size, $angle, $centerX, $y, $color, $fontFile, $text) {
    if ($fontFile) {
        $bbox = imagettfbbox($size, $angle, $fontFile, $text);
        if ($bbox) {
            $textWidth = abs($bbox[4] - $bbox[0]);
            $x = $centerX - ($textWidth / 2);
            imagettftext($image, $size, $angle, $x, $y, $color, $fontFile, $text);
            return;
        }
    }
    
    // Fallback drawing using basic imagestring()
    // Standard font width for font 5 is 9 pixels, height is 15 pixels.
    // Approximate scaling factor based on size parameter
    $fontId = 5;
    if ($size < 12) {
        $fontId = 2; // smaller font
    }
    $charWidth = imagefontwidth($fontId);
    $charHeight = imagefontheight($fontId);
    $textWidth = strlen($text) * $charWidth;
    $x = $centerX - ($textWidth / 2);
    
    // Draw background outline shadow for font-fallback legibility
    imagestring($image, $fontId, $x + 1, $y - ($charHeight / 2) + 1, $text, imagecolorallocate($image, 255, 255, 255));
    imagestring($image, $fontId, $x, $y - ($charHeight / 2), $text, $color);
}

/**
 * Handle server error states by outputting a blank 1x1 transparent pixel.
 */
function outputFallbackPixel() {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    if (!headers_sent() && php_sapi_name() !== 'cli') {
        header('Content-Type: image/png');
    }
    echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=");
    exit;
}
?>
