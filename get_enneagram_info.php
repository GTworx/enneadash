<?php
$uploaded = 'C:/Users/GarimaAgrawal/.gemini/antigravity/brain/b9e21230-eca2-4939-a916-517b983d9d05/uploaded_image_1784541494154.png';
$dest = __DIR__ . '/enneagram_base.png';

if (file_exists($uploaded)) {
    copy($uploaded, $dest);
    echo "Copied/Saved base image successfully.\n";
} else {
    echo "Base image not found.\n";
}

if (file_exists($dest)) {
    $s = getimagesize($dest);
    echo "enneagram_base.png: {$s[0]}x{$s[1]}\n";
}
?>
