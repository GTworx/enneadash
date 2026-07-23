<?php
/**
 * Dynamic Animated Enneagram Triad Generator
 * Uses Imagick library to dynamically render GIF frames,
 * showing the fade pulse core node, stress arrow, and growth arrow.
 */

// Global shutdown error handler to prevent crashing in web page environments
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        outputFallbackPixel();
    }
});

// Clear output buffers to ensure binary consistency
if (ob_get_level() > 0) {
    ob_clean();
}

// 1. Input Validation and Fallback Setup
$dominantType = filter_input(INPUT_GET, 'dominant_type', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$dominantType = ($dominantType !== false && $dominantType !== null) ? $dominantType : 9;

$stressType = filter_input(INPUT_GET, 'stress_type', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$stressType = ($stressType !== false && $stressType !== null) ? $stressType : 6;

$growthType = filter_input(INPUT_GET, 'growth_type', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9]
]);
$growthType = ($growthType !== false && $growthType !== null) ? $growthType : 3;

// 2. Validate Imagick Library or Trigger Fallback
if (!class_exists('Imagick')) {
    // If Imagick is not loaded, stream the static base image as a safe fallback
    $basePath = __DIR__ . '/enneagram_base.png';
    if (file_exists($basePath)) {
        if (!headers_sent() && php_sapi_name() !== 'cli') {
            header('Content-Type: image/png');
            header('Cache-Control: max-age=604800, public');
        }
        readfile($basePath);
    } else {
        outputFallbackPixel();
    }
    exit;
}

// 3. Node Coordinates Map representing centers on a 630x614 canvas
$nodeCoords = [
    1 => ['x' => 433, 'y' => 215],
    2 => ['x' => 503, 'y' => 310],
    3 => ['x' => 480, 'y' => 427],
    4 => ['x' => 370, 'y' => 482],
    5 => ['x' => 259, 'y' => 482],
    6 => ['x' => 150, 'y' => 427],
    7 => ['x' => 127, 'y' => 310],
    8 => ['x' => 197, 'y' => 215],
    9 => ['x' => 315, 'y' => 170]
];

// Load Custom TTF Font path for labels rendering
$fontFile = null;
$possibleFonts = [
    'C:\\Windows\\Fonts\\arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf'
];
foreach ($possibleFonts as $path) {
    if (file_exists($path)) {
        $fontFile = $path;
        break;
    }
}

try {
    $gif = new Imagick();
    $gif->setFormat('gif');

    $basePath = __DIR__ . '/enneagram_base.png';
    if (!file_exists($basePath)) {
        throw new Exception("Enneagram base image not found.");
    }

    // Coordinates of Center and Sector radius
    $cx = 315;
    $cy = 330;
    $r = 145;

    // Define colors
    $pinkStressColor = '#e7236e';
    $whiteGrowthColor = '#ffffff';

    // Total animation frames loop array building
    // Frame indices:
    // 0 - 4: Dominant Node Pulse Focus
    // 5 - 9: Progressive Stress Line Draw (20% to 100%)
    // 10 - 14: Progressive Growth Line Draw (20% to 100%)
    // 15: Last frame showing complete dynamic network
    $totalFramesCount = 16;

    for ($frameIdx = 0; $frameIdx < $totalFramesCount; $frameIdx++) {
        // Load clean base for this frame
        $frame = new Imagick($basePath);
        $draw = new ImagickDraw();

        // A. Erase original arrows by drawing clean color sectors
        drawCleanSectors($draw, $cx, $cy, $r);

        // B. Render sector annotations
        if ($fontFile) {
            $draw->setFont($fontFile);
        }
        $draw->setFontSize(14);
        $draw->setFillColor(new ImagickPixel('#ffffff'));
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        // Power and Control Label (upper sector)
        $draw->annotation($cx, $cy - 65, "Power");
        $draw->annotation($cx, $cy - 48, "and");
        $draw->annotation($cx, $cy - 31, "Control");

        // Affirmation and Esteem Label (lower right sector)
        $draw->annotation($cx + 55, $cy + 25, "Affirmation");
        $draw->annotation($cx + 55, $cy + 42, "and");
        $draw->annotation($cx + 55, $cy + 59, "Esteem");

        // Safety and Security Label (lower left sector)
        $draw->annotation($cx - 55, $cy + 25, "Safety");
        $draw->annotation($cx - 55, $cy + 42, "and");
        $draw->annotation($cx - 55, $cy + 59, "Security");

        // C. Render Node Animations based on frame step
        
        // C1. Dominant Node Focus Pulse Effect (Frames 0 to 4)
        $pulseFactor = 0;
        if ($frameIdx >= 0 && $frameIdx <= 4) {
            $pulseFactor = $frameIdx + 1; // expanding ring
        } elseif ($frameIdx > 4) {
            $pulseFactor = 5; // keep fully drawn state highlight
        }

        if ($pulseFactor > 0) {
            // Draw expanding focus ring around dominant node
            $domCoord = $nodeCoords[$dominantType];
            $draw->setStrokeColor(new ImagickPixel($whiteGrowthColor));
            $draw->setFillColor(new ImagickPixel('none'));
            $draw->setStrokeWidth(2);
            $ringRad = 25 + $pulseFactor * 3;
            $draw->ellipse($domCoord['x'], $domCoord['y'], $ringRad, $ringRad, 0, 360);
        }

        // C2. Draw Stress Line (Dynamic drawing index 5 to 9)
        if ($frameIdx >= 5) {
            $stressProgress = 1.0;
            if ($frameIdx >= 5 && $frameIdx <= 9) {
                // progressive drawing shares: 20%, 40%, 60%, 80%, 100%
                $stressProgress = ($frameIdx - 4) / 5.0;
            }
            $orig = $nodeCoords[$dominantType];
            $dest = $nodeCoords[$stressType];
            drawArrow($draw, $orig['x'], $orig['y'], $dest['x'], $dest['y'], $pinkStressColor, $stressProgress);
        }

        // C3. Draw Growth Line (Dynamic drawing index 10 to 14)
        if ($frameIdx >= 10) {
            $growthProgress = 1.0;
            if ($frameIdx >= 10 && $frameIdx <= 14) {
                // progressive drawing shares: 20%, 40%, 60%, 80%, 100%
                $growthProgress = ($frameIdx - 9) / 5.0;
            }
            $orig = $nodeCoords[$dominantType];
            $dest = $nodeCoords[$growthType];
            drawArrow($draw, $orig['x'], $orig['y'], $dest['x'], $dest['y'], $whiteGrowthColor, $growthProgress);
        }

        // Draw elements onto frame canvas
        $frame->drawImage($draw);
        $draw->clear();

        // D. Configure frame settings & delays (1/100ths of a second)
        $delay = 12; // 120ms standard drawing frame delay
        if ($frameIdx === 4) {
            $delay = 25; // Slight pause after pulse node before drawing arrows
        } elseif ($frameIdx === $totalFramesCount - 1) {
            $delay = 250; // Hold final frame showing completed paths for 2.5 seconds
        }

        $frame->setImageDelay($delay);
        $gif->addImage($frame);
        $frame->clear();
    }

    // 4. Quantize colors and Optimize layers to keep file size under 500KB
    $gif->optimizeImageLayers();
    $gif->quantizeImages(64, Imagick::COLORSPACE_RGB, 0, false, false);

    // 5. Output Dynamic Animated Stream
    if (ob_get_level() > 0) {
        ob_clean();
    }
    if (!headers_sent() && php_sapi_name() !== 'cli') {
        header('Content-Type: image/gif');
        header('Cache-Control: max-age=604800, public');
    }
    echo $gif->getImagesBlob();
    
    // Clear Imagick Memory allocation
    $gif->clear();
    $gif->destroy();

} catch (Exception $e) {
    outputFallbackPixel();
}

exit;

/**
 * Draw raw colorful sectors programmatically over base image diagram lines.
 */
function drawCleanSectors($draw, $cx, $cy, $r) {
    $yellowColor = new ImagickPixel('#e2aa21');
    $greenColor  = new ImagickPixel('#53a135');
    $blueColor   = new ImagickPixel('#3b5fa9');
    
    // Draw Yellow Sector (-170 deg to -10 deg)
    $draw->setFillColor($yellowColor);
    $draw->setStrokeColor(new ImagickPixel('none'));
    $points = [['x' => $cx, 'y' => $cy]];
    for ($a = -170; $a <= -10; $a += 5) {
        $rad = deg2rad($a);
        $points[] = ['x' => $cx + $r * cos($rad), 'y' => $cy + $r * sin($rad)];
    }
    $draw->polygon($points);

    // Draw Green Sector (-10 deg to 110 deg)
    $draw->setFillColor($greenColor);
    $points = [['x' => $cx, 'y' => $cy]];
    for ($a = -10; $a <= 110; $a += 5) {
        $rad = deg2rad($a);
        $points[] = ['x' => $cx + $r * cos($rad), 'y' => $cy + $r * sin($rad)];
    }
    $draw->polygon($points);

    // Draw Blue Sector (110 deg to 190 deg)
    $draw->setFillColor($blueColor);
    $points = [['x' => $cx, 'y' => $cy]];
    for ($a = 110; $a <= 190; $a += 5) {
        $rad = deg2rad($a);
        $points[] = ['x' => $cx + $r * cos($rad), 'y' => $cy + $r * sin($rad)];
    }
    $draw->polygon($points);
}

/**
 * Draw custom arrow from node origin to destination with progressive values.
 */
function drawArrow($draw, $x1, $y1, $x2, $y2, $colorHex, $progress) {
    if ($progress <= 0) return;
    
    $dx = $x2 - $x1;
    $dy = $y2 - $y1;
    $dist = sqrt($dx * $dx + $dy * $dy);
    if ($dist < 1.0) return;

    $ux = $dx / $dist;
    $uy = $dy / $dist;

    $nodeRadius = 25; // Keep arrow from overlapping circle node center
    $startX = $x1 + $nodeRadius * $ux;
    $startY = $y1 + $nodeRadius * $uy;

    $endX = $x2 - $nodeRadius * $ux;
    $endY = $y2 - $nodeRadius * $uy;

    // Line drawing progression calculation
    $currEndX = $startX + $progress * ($endX - $startX);
    $currEndY = $startY + $progress * ($endY - $startY);

    $color = new ImagickPixel($colorHex);
    $draw->setStrokeColor($color);
    $draw->setFillColor(new ImagickPixel('none'));
    $draw->setStrokeWidth(3);

    // Render path lines
    $draw->line($startX, $startY, $currEndX, $currEndY);

    // Render arrowhead only when path drawing finishes
    if ($progress >= 1.0) {
        $arrowLength = 15;
        $arrowAngle = deg2rad(25);
        $theta = atan2($dy, $dx);

        $p1x = $endX - $arrowLength * cos($theta - $arrowAngle);
        $p1y = $endY - $arrowLength * sin($theta - $arrowAngle);
        $p2x = $endX - $arrowLength * cos($theta + $arrowAngle);
        $p2y = $endY - $arrowLength * sin($theta + $arrowAngle);

        $draw->line($endX, $endY, $p1x, $p1y);
        $draw->line($endX, $endY, $p2x, $p2y);
    }
}

/**
 * Render blank 1x1 transparent placeholder pixel.
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
